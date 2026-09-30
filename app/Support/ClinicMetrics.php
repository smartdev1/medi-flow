<?php

namespace App\Support;

use App\Models\Admission;
use App\Enums\ActCategory;
use App\Models\Consultation;
use App\Models\Exam;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Prescription;
use App\Models\Room;
use App\Models\Visit;
use Carbon\Carbon;

class ClinicMetrics
{
    /**
     * Indicateurs financiers sur une période donnée.
     */
    public static function finance(Carbon $from, Carbon $to): array
    {
        $payments = Payment::whereBetween('created_at', [$from, $to])->get();

        $collected = $payments->where('status', Payment::STATUS_PAID)->sum('amount');
        $pending = $payments->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_PARTIAL])->sum('amount');
        $expected = $collected + $pending;

        $paidPatients = Payment::whereBetween('created_at', [$from, $to])
            ->where('status', Payment::STATUS_PAID)
            ->distinct('patient_id')
            ->count('patient_id');

        $byService = Payment::whereBetween('created_at', [$from, $to])
            ->where('status', Payment::STATUS_PAID)
            ->get()
            ->groupBy('service')
            ->map(fn ($group) => $group->sum('amount'))
            ->sortDesc();

        // Map ActCategory enum values to the labels used in the view
        $categoryLabelMap = [
            ActCategory::Consultation->value => 'Consultation',
            ActCategory::Laboratoire->value => 'Laboratoire',
            ActCategory::Imagerie->value => 'Laboratoire',
            ActCategory::Pharmacie->value => 'Pharmacie',
            ActCategory::Hospitalisation->value => 'Hospitalisation',
            ActCategory::Chirurgie->value => 'Chirurgie',
            ActCategory::Maternite->value => 'Autre',
            ActCategory::Bloc->value => 'Autre',
            ActCategory::Autre->value => 'Autre',
        ];

        $byServiceCategory = [];
        $invoices = Invoice::whereBetween('created_at', [$from, $to])
            ->where('status', Invoice::STATUS_PAID)
            ->get();

        foreach ($invoices as $invoice) {
            foreach ($invoice->breakdown ?? [] as $category => $amount) {
                $label = $categoryLabelMap[$category] ?? 'Autre';
                $byServiceCategory[$label] = ($byServiceCategory[$label] ?? 0) + $amount;
            }
        }

        arsort($byServiceCategory);

        return [
            'collected' => $collected,
            'pending' => $pending,
            'expected' => $expected,
            'recovery_rate' => $expected > 0 ? round($collected / $expected * 100) : 0,
            'unpaid_rate' => $expected > 0 ? round($pending / $expected * 100) : 0,
            'unpaid_count' => $payments->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_PARTIAL])->count(),
            'avg_basket' => $paidPatients > 0 ? round($collected / $paidPatients) : 0,
            'paying_patients' => $paidPatients,
            'by_service' => $byService,
            'by_service_category' => $byServiceCategory,
        ];
    }

    /**
     * Chiffre d'affaires mensuel (12 derniers mois) pour les tendances.
     */
    public static function monthlyRevenue(int $months = 12): array
    {
        $start = now()->subMonths($months - 1)->startOfMonth();
        $payments = Payment::where('status', Payment::STATUS_PAID)
            ->where('created_at', '>=', $start)
            ->get();

        $series = [];
        for ($i = 0; $i < $months; $i++) {
            $month = $start->copy()->addMonths($i);
            $key = $month->format('Y-m');
            $series[$key] = [
                'label' => $month->translatedFormat('M y'),
                'total' => 0,
            ];
        }

        foreach ($payments as $payment) {
            $key = $payment->created_at->format('Y-m');
            if (isset($series[$key])) {
                $series[$key]['total'] += $payment->amount;
            }
        }

        return array_values($series);
    }

    /**
     * Indicateurs opérationnels sur une période donnée.
     */
    public static function operations(Carbon $from, Carbon $to): array
    {
        $rooms = Room::where('maintenance', false)->with('activeAdmissions')->get();
        $capacity = $rooms->sum('capacity');
        $occupied = $rooms->sum('occupied_count');
        $occupancyRate = $capacity > 0 ? round($occupied / $capacity * 100) : 0;

        $discharged = Admission::whereNotNull('discharged_at')
            ->whereBetween('discharged_at', [$from, $to])
            ->get();
        $avgStay = $discharged->isNotEmpty()
            ? round($discharged->avg(fn (Admission $a) => $a->admitted_at->diffInDays($a->discharged_at, true)), 1)
            : 0;

        $visits = Visit::with(['consultations' => fn ($q) => $q->whereNotNull('started_at')])
            ->whereBetween('arrived_at', [$from, $to])
            ->get();
        $waits = [];
        foreach ($visits as $visit) {
            $first = $visit->consultations->sortBy('started_at')->first();
            if ($first) {
                $waits[] = $visit->arrived_at->diffInMinutes($first->started_at);
            }
        }
        $avgWait = $waits !== [] ? round(array_sum($waits) / count($waits)) : 0;

        $acts = [
            'consultations' => Consultation::whereBetween('started_at', [$from, $to])->count(),
            'exams' => Exam::whereBetween('requested_at', [$from, $to])->count(),
            'prescriptions' => Prescription::whereBetween('requested_at', [$from, $to])->count(),
            'admissions' => Admission::whereBetween('admitted_at', [$from, $to])->count(),
        ];
        $acts['total'] = array_sum($acts);

        return [
            'bed_capacity' => $capacity,
            'bed_occupied' => $occupied,
            'occupancy_rate' => $occupancyRate,
            'avg_stay_days' => $avgStay,
            'avg_wait_minutes' => $avgWait,
            'discharged_count' => $discharged->count(),
            'acts' => $acts,
        ];
    }

    /**
     * Actes réalisés par mois (12 derniers mois) pour les tendances.
     */
    public static function monthlyActs(int $months = 12): array
    {
        $start = now()->subMonths($months - 1)->startOfMonth();
        $series = [];
        for ($i = 0; $i < $months; $i++) {
            $month = $start->copy()->addMonths($i);
            $series[$month->format('Y-m')] = [
                'label' => $month->translatedFormat('M y'),
                'total' => 0,
            ];
        }

        $maps = [
            [Consultation::class, 'started_at'],
            [Exam::class, 'requested_at'],
            [Prescription::class, 'requested_at'],
            [Admission::class, 'admitted_at'],
        ];

        foreach ($maps as [$model, $column]) {
            $rows = $model::where($column, '>=', $start)->get();
            foreach ($rows as $row) {
                $key = $row->{$column}->format('Y-m');
                if (isset($series[$key])) {
                    $series[$key]['total']++;
                }
            }
        }

        return array_values($series);
    }

    public static function formatMoney(int $amount): string
    {
        return number_format($amount, 0, ',', ' ').' FCFA';
    }
}
