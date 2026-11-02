<?php

namespace App\Http\Controllers;

use App\Enums\ActCategory;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EtatQuotidienController extends Controller
{
    public function index(Request $request): View
    {
        $date = $request->input('date', today()->toDateString());
        $service = $request->input('service');
        $statut = $request->input('statut');
        $search = $request->input('search');

        $query = Visit::with(['patient', 'act'])
            ->whereDate('arrived_at', $date);

        if ($service) {
            $query->whereHas('act', fn ($q) => $q->where('category', $service));
        }

        if ($statut) {
            $query->where('status', $statut);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('patient', function ($pq) use ($search) {
                    $pq->where('first_name', 'like', "%{$search}%")
                       ->orWhere('last_name', 'like', "%{$search}%")
                       ->orWhere('code', 'like', "%{$search}%");
                });
            });
        }

        $visits = $query->orderBy('arrived_at', 'asc')->paginate(50)->withQueryString();

        $user = Auth::user();
        $userName = $user?->name ?? 'Utilisateur';
        $userRole = $user?->role?->label() ?? 'Personnel clinique';
        $userInitials = $user?->initials ?? $this->initialsFor($userName);

        return view('etat-quotidien.index', [
            'visits' => $visits,
            'date' => $date,
            'service' => $service,
            'statut' => $statut,
            'search' => $search,
            'categories' => ActCategory::cases(),
            'statuses' => [
                Visit::STATUS_WAITING,
                Visit::STATUS_URGENT,
                Visit::STATUS_IN_CONSULTATION,
                Visit::STATUS_CONSULTATION_CLOSED,
                Visit::STATUS_HOSPITALIZATION_REQUESTED,
                Visit::STATUS_OBSERVATION_REQUESTED,
                Visit::STATUS_RETURNED_FROM_OBSERVATION,
                Visit::STATUS_HOSPITALIZED,
                Visit::STATUS_OBSERVATION,
                Visit::STATUS_REFERRED,
            ],
            'userName' => $userName,
            'userRole' => $userRole,
            'userInitials' => $userInitials,
        ]);
    }

    /**
     * Export the filtered visit list as a CSV download.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $date = $request->input('date', today()->toDateString());
        $service = $request->input('service');
        $statut = $request->input('statut');
        $search = $request->input('search');

        $query = Visit::with(['patient', 'act'])
            ->whereDate('arrived_at', $date);

        if ($service) {
            $query->whereHas('act', fn ($q) => $q->where('category', $service));
        }

        if ($statut) {
            $query->where('status', $statut);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('patient', function ($pq) use ($search) {
                    $pq->where('first_name', 'like', "%{$search}%")
                       ->orWhere('last_name', 'like', "%{$search}%")
                       ->orWhere('code', 'like', "%{$search}%");
                });
            });
        }

        $visits = $query->orderBy('arrived_at', 'asc')->get();

        $filename = "etat-quotidien-{$date}.csv";

        return response()->streamDownload(function () use ($visits) {
            $handle = fopen('php://output', 'w');

            // BOM for Excel UTF-8 compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Code patient',
                'Nom complet',
                'Service',
                'Heure d\'arrivee',
                'Heure de depart',
                'Statut',
            ], ';');

            foreach ($visits as $visit) {
                fputcsv($handle, [
                    $visit->patient->code ?? '',
                    $visit->patient->full_name ?? '',
                    $visit->act?->category?->label() ?? '',
                    $visit->arrived_at?->format('H:i') ?? '',
                    $visit->discharged_at?->format('H:i') ?? '',
                    $visit->status ?? '',
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
