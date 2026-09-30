<?php

namespace App\Console\Commands;

use App\Models\Admission;
use App\Models\AdmissionDayLine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillAdmissionDayLines extends Command
{
    protected $signature = 'backfill:admission-day-lines
                            {--force : Skip confirmation prompt}';
    protected $description = 'Backfill admission day lines for all discharged admissions';

    public function handle(): int
    {
        if (! $this->option('force')) {
            if (! $this->confirm('This will regenerate day lines for all discharged admissions. Continue?')) {
                $this->info('Aborted.');
                return self::SUCCESS;
            }
        }

        $admissions = Admission::whereNotNull('discharged_at')->with('room')->get();

        if ($admissions->isEmpty()) {
            $this->info('No discharged admissions found.');
            return self::SUCCESS;
        }

        $this->info("Processing {$admissions->count()} discharged admissions...");

        $bar = $this->output->createProgressBar($admissions->count());
        $bar->start();

        $totalLines = 0;

        DB::transaction(function () use ($admissions, $bar, &$totalLines) {
            foreach ($admissions as $admission) {
                $admission->dayLines()->delete();

                $start = $admission->admitted_at->copy()->startOfDay();
                $end = $admission->discharged_at->copy()->startOfDay();
                $days = $start->diffInDays($end) + 1;
                $dailyRate = $admission->room?->daily_rate ?? 0;

                for ($i = 0; $i < $days; $i++) {
                    AdmissionDayLine::create([
                        'admission_id' => $admission->id,
                        'day_date' => $start->copy()->addDays($i)->toDateString(),
                        'amount' => $dailyRate,
                    ]);
                    $totalLines++;
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();

        $this->info("Backfill complete. {$totalLines} day lines created for {$admissions->count()} admissions.");
        $this->newLine();
        $this->warn('Next step: recalculate invoices manually (e.g. via Tinker: `App\Models\Invoice::all()->each(fn($i) => $i->recalculate());`).');

        return self::SUCCESS;
    }
}
