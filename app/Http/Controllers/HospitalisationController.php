<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Admission;
use App\Models\AdmissionRequest;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Room;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HospitalisationController extends Controller
{
    public function index(): View
    {
        $admissions = Admission::with(['patient', 'room', 'visit'])
            ->active()
            ->get()
            ->reject(fn (Admission $admission) => $admission->unit() === Admission::STATUS_OBSERVATION)
            ->sortBy('admitted_at')
            ->values();

        $patients = $this->present($admissions);

        $averageStayDays = $admissions->avg(fn (Admission $admission) => $admission->admitted_at->diffInHours(now()) / 24);

        $onCallDoctors = User::whereIn('role', [Role::Medecin->value, Role::Pediatre->value])
            ->whereHas('onCallShifts', function ($query) {
                $query->whereDate('date', today());
            })
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (User $doctor) => [$doctor->name => $doctor->name]);

        $user = auth()->user();
        $userName = $user?->name ?? 'Utilisateur';
        $userRole = $user?->role?->label() ?? 'Personnel clinique';
        $userInitials = $user?->initials ?? $this->initialsFor($userName);
        $isAideSoignante = $user->hasRole(Role::AideSoignante);

        // Nursing aide data: admissions needing depiquage validation or room release
        $attenteDepiquage = Admission::with(['patient', 'room'])
            ->whereNotNull('discharged_at')
            ->whereNull('depiquage_valide_par')
            ->orderBy('discharged_at')
            ->get()
            ->map(fn (Admission $a) => [
                'id' => $a->id,
                'name' => $a->patient->full_name,
                'initials' => $a->patient->initials,
                'code' => $a->patient->code,
                'room' => "{$a->room->number} · Lit {$a->bed_number}",
                'discharged_at' => $a->discharged_at->diffForHumans(),
                'can_depique' => $a->canBeDepiqued(),
            ])
            ->all();

        $chambresALiberer = Admission::with(['patient', 'room'])
            ->whereNotNull('depiquage_valide_par')
            ->whereNotNull('discharged_at')
            ->whereNull('room_released_at')
            ->orderBy('depiquage_valide_le')
            ->get()
            ->map(fn (Admission $a) => [
                'id' => $a->id,
                'name' => $a->patient->full_name,
                'initials' => $a->patient->initials,
                'code' => $a->patient->code,
                'room' => "{$a->room->number} · Lit {$a->bed_number}",
                'depiquage_valide_le' => $a->depiquage_valide_le?->diffForHumans(),
                'can_liberer' => $a->canBeLiberee(),
            ])
            ->all();

        $depiquagesAujourdhui = Admission::with(['patient', 'room', 'depiquageValidePar'])
            ->whereDate('depiquage_valide_le', today())
            ->orderByDesc('depiquage_valide_le')
            ->get()
            ->map(fn (Admission $a) => [
                'id' => $a->id,
                'name' => $a->patient->full_name,
                'initials' => $a->patient->initials,
                'code' => $a->patient->code,
                'room' => "{$a->room->number} · Lit {$a->bed_number}",
                'valide_le' => $a->depiquage_valide_le->format('H:i'),
                'valide_par' => $a->depiquageValidePar?->name,
            ])
            ->all();

        return view('hospitalisation.dashboard', [
            'patients' => $patients,
            'plannedDischarges' => $admissions->filter(fn (Admission $a) => $a->isDischargePlanned())->count(),
            'averageStay' => $averageStayDays ? number_format($averageStayDays, 1, ',', ' ').' jour(s)' : '—',
            'patientOptions' => Patient::orderBy('first_name')->get(),
            'roomOptions' => Room::orderBy('ward')->orderBy('number')->get()
                ->filter(fn (Room $room) => ! $room->maintenance && count($room->available_bed_numbers) > 0)
                ->mapWithKeys(fn (Room $room) => [
                    $room->id => "{$room->ward} — {$room->number} (".count($room->available_bed_numbers).' lit(s) libre(s))',
                ]),
            'onCallDoctorOptions' => $onCallDoctors,
            'userName' => $userName,
            'userRole' => $userRole,
            'userInitials' => $userInitials,
            'isAideSoignante' => $isAideSoignante,
            'attenteDepiquage' => $attenteDepiquage,
            'chambresALiberer' => $chambresALiberer,
            'depiquagesAujourdhui' => $depiquagesAujourdhui,
        ]);
    }

    /**
     * Formate une collection d'admissions pour les vues.
     */
    private function present($admissions): array
    {
        return $admissions->map(fn (Admission $admission) => [
            'id' => $admission->id,
            'patient_id' => $admission->patient->id,
            'name' => $admission->patient->full_name,
            'initials' => $admission->patient->initials,
            'code' => $admission->patient->code,
            'room' => "{$admission->room->number} · Lit {$admission->bed_number}",
            'ward' => $admission->room->ward,
            'doctor' => $admission->attending ?? '—',
            'admitted' => $admission->admitted_at->isToday()
                ? "Aujourd'hui, {$admission->admitted_at->format('H:i')}"
                : $admission->admitted_at->diffForHumans(),
            'reason' => $admission->reason,
            'status' => $admission->status,
            'status_tone' => $admission->status_tone,
            'is_observation' => $admission->unit() === Admission::STATUS_OBSERVATION,
            'discharge_planned' => $admission->isDischargePlanned(),
            'billed_days' => $admission->dayLines->count(),
        ])->all();
    }

    public function observation(): View
    {
        $admissions = Admission::with(['patient', 'room', 'visit'])
            ->active()
            ->get()
            ->filter(fn (Admission $admission) => $admission->unit() === Admission::STATUS_OBSERVATION)
            ->sortBy('admitted_at')
            ->values();

        $patients = $this->present($admissions);

        $user = auth()->user();
        $userName = $user?->name ?? 'Utilisateur';
        $userRole = $user?->role?->label() ?? 'Personnel clinique';
        $userInitials = $user?->initials ?? $this->initialsFor($userName);
        $isAideSoignante = $user?->hasRole(Role::AideSoignante) ?? false;

        return view('hospitalisation.observation', [
            'patients' => $patients,
            'userName' => $userName,
            'userRole' => $userRole,
            'userInitials' => $userInitials,
            'isAideSoignante' => $isAideSoignante,
        ]);
    }

    public function pendingRequests(): View
    {
        $requests = AdmissionRequest::pending()
            ->with(['patient', 'requester'])
            ->orderBy('created_at')
            ->get();

        $rooms = Room::where('maintenance', false)
            ->orderBy('ward')
            ->orderBy('number')
            ->get()
            ->filter(fn (Room $room) => count($room->available_bed_numbers) > 0)
            ->map(fn (Room $room) => [
                'id' => $room->id,
                'label' => "{$room->ward} — {$room->number} (".count($room->available_bed_numbers).' lit(s))',
                'beds' => $room->available_bed_numbers,
            ])
            ->values()
            ->all();

        $user = auth()->user();
        $userName = $user?->name ?? 'Utilisateur';
        $userRole = $user?->role?->label() ?? 'Personnel clinique';
        $userInitials = $user?->initials ?? $this->initialsFor($userName);

        return view('hospitalisation.requests', [
            'requests' => $requests,
            'rooms' => $rooms,
            'userName' => $userName,
            'userRole' => $userRole,
            'userInitials' => $userInitials,
        ]);
    }

    public function refresh(): JsonResponse
    {
        $admissions = Admission::with(['patient', 'room', 'visit'])
            ->active()
            ->get()
            ->reject(fn (Admission $admission) => $admission->unit() === Admission::STATUS_OBSERVATION)
            ->sortBy('admitted_at')
            ->values();

        $user = auth()->user();
        $isAideSoignante = $user->hasRole(Role::AideSoignante);

        $data = [
            'patients' => $this->present($admissions),
            'plannedDischarges' => $admissions->filter(fn (Admission $a) => $a->isDischargePlanned())->count(),
            'averageStay' => ($avg = $admissions->avg(fn (Admission $a) => $a->admitted_at->diffInHours(now()) / 24))
                ? number_format($avg, 1, ',', ' ').' jour(s)' : '—',
        ];

        if ($isAideSoignante) {
            $data['attenteDepiquage'] = Admission::with(['patient', 'room'])
                ->whereNotNull('discharged_at')
                ->whereNull('depiquage_valide_par')
                ->orderBy('discharged_at')
                ->get()
                ->map(fn (Admission $a) => [
                    'id' => $a->id,
                    'name' => $a->patient->full_name,
                    'room' => "{$a->room->number} · Lit {$a->bed_number}",
                    'discharged_at' => $a->discharged_at->diffForHumans(),
                ])
                ->all();

            $data['chambresALiberer'] = Admission::with(['patient', 'room'])
                ->whereNotNull('depiquage_valide_par')
                ->whereNotNull('discharged_at')
                ->whereNull('room_released_at')
                ->orderBy('depiquage_valide_le')
                ->get()
                ->map(fn (Admission $a) => [
                    'id' => $a->id,
                    'name' => $a->patient->full_name,
                    'room' => "{$a->room->number} · Lit {$a->bed_number}",
                    'depiquage_valide_le' => $a->depiquage_valide_le?->diffForHumans(),
                ])
                ->all();
        }

        return response()->json($data);
    }

    public function observationRefresh(): JsonResponse
    {
        $admissions = Admission::with(['patient', 'room', 'visit'])
            ->active()
            ->get()
            ->filter(fn (Admission $admission) => $admission->unit() === Admission::STATUS_OBSERVATION)
            ->sortBy('admitted_at')
            ->values();

        return response()->json([
            'patients' => $this->present($admissions),
        ]);
    }

    public function approveRequest(Request $request, AdmissionRequest $admissionRequest): RedirectResponse
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'bed_number' => 'required|integer|min:1',
        ]);

        if (! $admissionRequest->isPending()) {
            return back()->with('error', 'Cette demande a déjà été traitée.');
        }

        $room = Room::findOrFail($validated['room_id']);

        if (! in_array($validated['bed_number'], $room->available_bed_numbers ?? [], false)) {
            return back()->with('error', 'Ce lit n\'est plus disponible. Veuillez choisir un autre lit.');
        }

        $isObservation = $admissionRequest->isObservation();
        $visit = $admissionRequest->visit;

        DB::transaction(function () use ($admissionRequest, $room, $validated, $isObservation, $visit, $request) {
            $admission = Admission::create([
                'patient_id' => $admissionRequest->patient_id,
                'visit_id' => $admissionRequest->visit_id,
                'room_id' => $room->id,
                'bed_number' => $validated['bed_number'],
                'reason' => $admissionRequest->reason,
                'attending' => $request->input('attending') ?? $admissionRequest->requester?->name ?? auth()->user()->name,
                'status' => $isObservation ? Admission::STATUS_OBSERVATION : Admission::STATUS_STABLE,
                'admitted_at' => now(),
            ]);

            // La journée d'admission est due dès l'entrée : le séjour est
            // facturé en continu, pas seulement à la sortie.
            $admission->syncDayLines();

            $admissionRequest->approve(auth()->user());

            // La visite suit l'admission : le patient ne peut plus disparaître
            // entre la demande et la prise en charge effective.
            $visit?->update([
                'status' => $isObservation ? Visit::STATUS_OBSERVATION : Visit::STATUS_HOSPITALIZED,
            ]);

            // L'admission est un service facturable : la facture du dossier est
            // créée ou actualisée immédiatement.
            if ($visit) {
                Invoice::forVisit($visit);
            }
        });

        $typeLabel = $isObservation ? 'observation' : 'hospitalisation';

        return redirect()
            ->route('admission-requests.index')
            ->with('success', "Patient {$admissionRequest->patient->full_name} admis en {$typeLabel}.");
    }

    public function rejectRequest(Request $request, AdmissionRequest $admissionRequest): RedirectResponse
    {
        if (! $admissionRequest->isPending()) {
            return back()->with('error', 'Cette demande a déjà été traitée.');
        }

        DB::transaction(function () use ($admissionRequest) {
            $admissionRequest->reject(auth()->user());

            // La demande rejetée ramène la visite dans le circuit médical :
            // le patient n'est jamais laissé dans un état intermédiaire.
            $admissionRequest->visit?->update([
                'status' => Visit::STATUS_IN_CONSULTATION,
            ]);
        });

        return redirect()
            ->route('admission-requests.index')
            ->with('success', "Demande de {$admissionRequest->patient->full_name} rejetée — le patient reste en consultation.");
    }
}
