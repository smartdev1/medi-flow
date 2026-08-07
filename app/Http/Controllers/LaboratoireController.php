<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Patient;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LaboratoireController extends Controller
{
    public function index(): View
    {
        // Charger les examens récents (30 derniers jours) avec leurs patients
        $exams = Exam::with('patient')
            ->whereDate('requested_at', '>=', now()->subDays(30))
            ->orderBy('requested_at', 'desc')
            ->get()
            ->filter(function (Exam $exam) {
                // Filtrer manuellement pour exclure les examens liés à des visites clôturées
                // Nous devons charger la visite associée pour vérifier son statut
                $visit = \App\Models\Visit::where('patient_id', $exam->patient_id)
                    ->whereDate('arrived_at', '=', $exam->requested_at->format('Y-m-d'))
                    ->first();
                
                return $visit ? !$visit->isClosed() : true;
            });

        // Préparer la file d'attente (examens en attente de prélèvement ou en cours)
        $queue = $exams
            ->whereIn('status', [Exam::STATUS_AWAITING_SAMPLE, Exam::STATUS_IN_PROGRESS])
            ->values()
            ->map(fn (Exam $exam, int $index) => [
                'name' => $exam->patient->full_name,
                'initials' => $exam->patient->initials,
                'time' => $exam->requested_at->format('H:i'),
                'meta' => "{$exam->name} — ".lcfirst($exam->status),
                'urgent' => false,
                'selected' => $index === 0,
                'badge' => $exam->status === Exam::STATUS_IN_PROGRESS
                    ? ['label' => 'En analyse', 'tone' => 'brand']
                    : null,
            ]);

        // Calculer les statistiques
        $awaitingCount = $exams->where('status', Exam::STATUS_AWAITING_SAMPLE)->count();
        $inProgressCount = $exams->where('status', Exam::STATUS_IN_PROGRESS)->count();
        $readyCount = $exams->where('status', Exam::STATUS_READY)->count();
        $sentCount = $exams->where('status', Exam::STATUS_SENT)->count();

        $user = Auth::user();
        $userName = $user?->name ?? 'Utilisateur';
        $userRole = $user?->role?->label() ?? 'Personnel clinique';
        $userInitials = $user?->initials ?? $this->initialsFor($userName);

        return view('laboratoire.dashboard', [
            'queue' => $queue,
            'exams' => $exams,
            'awaitingCount' => $awaitingCount,
            'inProgressCount' => $inProgressCount,
            'readyCount' => $readyCount,
            'sentCount' => $sentCount,
            'totalExams' => $exams->count(),
            'patientOptions' => Patient::orderBy('first_name')->get(),
            'userName' => $userName,
            'userRole' => $userRole,
            'userInitials' => $userInitials,
            'showDebugInfo' => $exams->isEmpty(), // Afficher info de debug si pas d'examens
        ]);
    }
}
