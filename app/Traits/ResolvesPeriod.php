<?php

namespace App\Traits;

use Carbon\Carbon;

trait ResolvesPeriod
{
    private function resolvePeriod(string $period): array
    {
        $today = Carbon::today();

        return match ($period) {
            'today' => [$today->copy()->startOfDay(), $today->copy()->endOfDay(), "Aujourd'hui"],
            '7d' => [$today->copy()->subDays(6)->startOfDay(), $today->copy()->endOfDay(), '7 derniers jours'],
            '30d' => [$today->copy()->subDays(29)->startOfDay(), $today->copy()->endOfDay(), '30 derniers jours'],
            'year' => [$today->copy()->startOfYear(), $today->copy()->endOfYear(), 'Cette année'],
            default => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth(), 'Ce mois'],
        };
    }
}
