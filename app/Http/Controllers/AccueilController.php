<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Exceptions\StayAmountRefusedException;
use App\Models\Act;
use App\Models\Admission;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Room;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AccueilController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        $userName = $user?->name ?? 'Réceptionniste';
        $userRole = $user?->role?->label() ?? 'Réception';
        $userInitials = $user?->initials ?? $this->initialsFor($userName);

        $filters = [
            ['label' => 'Tout', 'active' => true],
            ['label' => 'Assuré', 'active' => false],
            ['label' => 'Non assuré', 'active' => false],
        ];

        $todaysVisits = Visit::with(['patient', 'invoice', 'admissions.dayLines', 'admissions.room'])
            ->whereDate('arrived_at', today())
            ->active()
            ->orderBy('arrived_at')
            ->get();

        // Seul l'agent d'accueil arrête le montant du séjour. Le docteur
        // général qui consulte le même écran reste en lecture seule.
        $canEditStayAmount = (bool) $user?->hasRole(Role::Accueil->value);

        $patients = $todaysVisits->map(function (Visit $visit) use ($canEditStayAmount) {
            $admission = $visit->admissions
                ->whereNull('discharged_at')
                ->sortByDesc('admitted_at')
                ->first();

            $isHospitalized = $visit->status === Visit::STATUS_HOSPITALIZED
                || ($admission !== null && ! $admission->isObservation());

            $days = null;
            $automaticAmount = null;
            $agreedAmount = null;

            if ($isHospitalized && $admission) {
                // Même source de vérité que la facture : le montant affiché ici
                // et celui de la caisse ne peuvent pas diverger.
                $admission->syncDayLines();
                $admission->load('dayLines');

                $days = $admission->dayLines->count();
                $automaticAmount = (int) $admission->dayLines->sum('amount');
                $agreedAmount = $visit->invoice?->agreed_stay_amount !== null
                    ? (int) $visit->invoice->agreed_stay_amount
                    : null;
            }

            // Le montant porté sur la facture est le montant convenu s'il existe,
            // sinon le cumul des journées. C'est cette valeur que la caisse
            // encaisse.
            $stayAmount = $agreedAmount ?? $automaticAmount;

            return [
                'visit_id' => $visit->id,
                'record_id' => $visit->patient_id,
                'name' => $visit->patient->full_name,
                'initials' => $visit->patient->initials,
                'id' => $visit->patient->code,
                'arrival' => $visit->arrived_at->format('H:i'),
                'coverage' => $visit->patient->coverage,
                'status' => $visit->status,
                'status_tone' => $visit->status_tone,
                'urgent' => $visit->urgent,
                'is_hospitalized' => $isHospitalized,
                'stay_days' => $days,
                'stay_amount' => $stayAmount,
                'automatic_amount' => $automaticAmount,
                'agreed_amount' => $agreedAmount,
                'can_edit_stay_amount' => $canEditStayAmount && $isHospitalized && $admission !== null,
            ];
        });

        // Calcul du taux d'occupation
        $rooms = Room::where('maintenance', false)->with('activeAdmissions')->get();
        $totalCapacity = $rooms->sum('capacity');
        $totalOccupied = $rooms->sum('occupied_count');
        $occupancyRate = $totalCapacity > 0 ? round(($totalOccupied / $totalCapacity) * 100) : 0;

        return view('accueil.dashboard', [
            'today' => now()->translatedFormat('l j F'),
            'filters' => $filters,
            'patients' => $patients,
            'arrivalsToday' => $todaysVisits->count(),
            'waitingCount' => $todaysVisits->where('status', Visit::STATUS_WAITING)->count(),
            'urgentCount' => $todaysVisits->where('urgent', true)->count(),
            'occupancyRate' => $occupancyRate,
            'coverageOptions' => [
                'Assuré' => 'Assuré',
                'Non assuré' => 'Non assuré',
            ],
            'insuranceCompanies' => \App\Models\InsuranceCompany::orderBy('name')->get(),
            'patientOptions' => Patient::orderBy('first_name')->get(),
            'acts' => Act::orderBy('category')->orderBy('label')->get(),
            'observationPatients' => $this->buildObservationCards(),
            'userName' => $userName,
            'userRole' => $userRole,
            'userInitials' => $userInitials,
        ]);
    }

    /**
     * Arrête le montant du séjour porté sur la facture.
     *
     * Réservé à l'agent d'accueil. Un montant vide ou nul revient au calcul
     * automatique par journées ; un montant saisi s'y substitue et devient la
     * somme due par la caisse. Le montant ne peut pas descendre sous ce qui a
     * déjà été encaissé sur la facture.
     */
    public function updateStayAmount(Request $request, Visit $visit): RedirectResponse
    {
        abort_unless(
            $request->user()?->hasRole(Role::Accueil->value),
            403,
            "Seul l'agent d'accueil peut arrêter le montant d'hospitalisation."
        );

        $data = $request->validate([
            'stay_amount' => ['nullable', 'integer', 'min:0'],
        ]);

        $hasAdmission = $visit->admissions()->whereNull('discharged_at')->exists();

        if (! $hasAdmission) {
            return back()->with('error', 'Ce dossier n\'a pas d\'hospitalisation en cours.');
        }

        $amount = ($data['stay_amount'] ?? null) === null || (int) $data['stay_amount'] <= 0
            ? null
            : (int) $data['stay_amount'];

        try {
            DB::transaction(function () use ($visit, $amount) {
                $invoice = Invoice::forVisit($visit);
                $invoice->agreed_stay_amount = $amount;
                $invoice->recalculate();

                if ((int) $invoice->paid_amount > (int) $invoice->patient_amount) {
                    throw new StayAmountRefusedException(
                        'Le montant du séjour ne peut pas être inférieur aux sommes déjà encaissées.'
                    );
                }
            });
        } catch (StayAmountRefusedException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $amount === null
            ? 'Montant du séjour remis au calcul par journées.'
            : 'Montant du séjour arrêté à '.number_format($amount, 0, ',', ' ').' FCFA — la caisse encaisse ce montant.');
    }

    private function buildObservationCards(): array
    {
        $observations = Admission::where('status', Admission::STATUS_OBSERVATION)
            ->whereNull('discharged_at')
            ->with(['patient', 'room'])
            ->orderBy('admitted_at', 'desc')
            ->get();

        return $observations->map(fn (Admission $admission) => [
            'name' => $admission->patient->full_name,
            'initials' => $admission->patient->initials,
            'code' => $admission->patient->code,
            'room' => $admission->room->number ?? '—',
            'ward' => $admission->room->ward ?? '—',
            'reason' => $admission->reason ?: 'Observation',
            'status' => $admission->status,
            'status_tone' => $admission->status_tone,
            'admitted_at' => $admission->admitted_at,
        ])->all();
    }
}