<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Support\ClinicMetrics;
use App\Traits\ResolvesPeriod;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DocteurGeneralController extends Controller
{
    use ResolvesPeriod;
    public function index(Request $request): View
    {
        $period = $request->string('period')->toString() ?: 'month';
        [$from, $to, $periodLabel] = $this->resolvePeriod($period);

        $finance = ClinicMetrics::finance($from, $to);
        $operations = ClinicMetrics::operations($from, $to);
        $monthlyRevenue = ClinicMetrics::monthlyRevenue();
        $monthlyActs = ClinicMetrics::monthlyActs();

        $periods = [
            ['key' => 'today', 'label' => "Aujourd'hui"],
            ['key' => '7d', 'label' => '7 jours'],
            ['key' => '30d', 'label' => '30 jours'],
            ['key' => 'month', 'label' => 'Ce mois'],
            ['key' => 'year', 'label' => 'Cette année'],
        ];

        $user = Auth::user();
        $userName = $user?->name ?? 'Docteur Général';
        $userRole = $user?->role?->label() ?? 'Supervision';
        $userInitials = $user?->initials ?? $this->initialsFor($userName);

        return view('docteurgeneral.dashboard', [
            'period' => $period,
            'periodLabel' => $periodLabel,
            'periods' => $periods,
            'finance' => $finance,
            'operations' => $operations,
            'monthlyRevenue' => $monthlyRevenue,
            'monthlyActs' => $monthlyActs,
            'userName' => $userName,
            'userRole' => $userRole,
            'userInitials' => $userInitials,
        ]);
    }
}

