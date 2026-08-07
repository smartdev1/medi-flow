<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Admission;
use App\Models\Patient;
use App\Models\Room;
use App\Models\User;
use Illuminate\View\View;

class HospitalisationController extends Controller
{
    public function index(): View
    {
        $admissions = Admission::with(['patient', 'room'])->active()->orderBy('admitted_at')->get();

        $patients = $admissions->map(fn (Admission $admission) => [
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
        ]);

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

        return view('hospitalisation.dashboard', [
            'patients' => $patients,
            'plannedDischarges' => $admissions->where('status', Admission::STATUS_DISCHARGE_PLANNED)->count(),
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
        ]);
    }
}
