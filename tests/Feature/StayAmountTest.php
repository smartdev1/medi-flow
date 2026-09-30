<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Admission;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Room;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le montant du séjour est arrêté par l'agent d'accueil, depuis son tableau.
 * La caisse n'enregistre aucun montant : elle encaisse ce que l'accueil a
 * porté sur la facture.
 */
class StayAmountTest extends TestCase
{
    use RefreshDatabase;

    private function hospitalisedVisit(): array
    {
        $patient = Patient::factory()->uninsured()->create();
        $visit = Visit::factory()->inConsultation()->create([
            'patient_id' => $patient->id,
            'act_id' => null,
            'arrived_at' => now(),
        ]);
        $room = Room::factory()->create(['capacity' => 2, 'daily_rate' => 25_000]);

        $admission = $patient->admissions()->create([
            'visit_id' => $visit->id,
            'room_id' => $room->id,
            'bed_number' => 1,
            'reason' => 'Test',
            'status' => Admission::STATUS_STABLE,
            'admitted_at' => now(),
        ]);

        return [$patient, $visit, $admission];
    }

    public function test_the_accueil_agent_sets_the_stay_amount_on_the_invoice(): void
    {
        $accueil = User::factory()->create([
            'role' => Role::Accueil->value,
            'email_verified_at' => now(),
        ]);

        [$patient, $visit] = $this->hospitalisedVisit();

        $this->actingAs($accueil)
            ->post(route('accueil.visits.stay-amount', $visit), [
                'stay_amount' => '120000',
            ])
            ->assertSessionHasNoErrors();

        $invoice = Invoice::where('visit_id', $visit->id)->firstOrFail();

        $this->assertSame(120_000, (int) $invoice->agreed_stay_amount);
        $this->assertSame(120_000, (int) $invoice->patient_amount, 'La caisse encaisse le montant convenu.');
    }

    public function test_an_empty_amount_returns_to_the_daily_calculation(): void
    {
        $accueil = User::factory()->create([
            'role' => Role::Accueil->value,
            'email_verified_at' => now(),
        ]);

        [$patient, $visit] = $this->hospitalisedVisit();

        $this->actingAs($accueil)->post(route('accueil.visits.stay-amount', $visit), [
            'stay_amount' => '90000',
        ]);

        $this->actingAs($accueil)
            ->post(route('accueil.visits.stay-amount', $visit), ['stay_amount' => ''])
            ->assertSessionHasNoErrors();

        $invoice = Invoice::where('visit_id', $visit->id)->firstOrFail();

        $this->assertNull($invoice->agreed_stay_amount);
        $this->assertSame(25_000, (int) $invoice->patient_amount, 'Le tarif de la journée reprend la main.');
    }

    public function test_an_amount_below_what_was_already_paid_is_refused(): void
    {
        $accueil = User::factory()->create([
            'role' => Role::Accueil->value,
            'email_verified_at' => now(),
        ]);

        [$patient, $visit] = $this->hospitalisedVisit();

        $this->actingAs($accueil)->post(route('accueil.visits.stay-amount', $visit), [
            'stay_amount' => '120000',
        ]);

        $invoice = Invoice::where('visit_id', $visit->id)->firstOrFail();
        $invoice->update(['paid_amount' => 80_000, 'status' => Invoice::STATUS_PARTIAL]);

        $this->actingAs($accueil)
            ->post(route('accueil.visits.stay-amount', $visit), ['stay_amount' => '50000'])
            ->assertSessionHas('error');

        $invoice->refresh();

        $this->assertSame(120_000, (int) $invoice->agreed_stay_amount, 'Le montant précédent est conservé.');
        $this->assertSame(120_000, (int) $invoice->patient_amount);
    }

    public function test_only_the_accueil_agent_can_set_the_stay_amount(): void
    {
        $doctor = User::factory()->create([
            'role' => Role::Medecin->value,
            'email_verified_at' => now(),
        ]);

        $supervisor = User::factory()->create([
            'role' => Role::DocteurGeneral->value,
            'email_verified_at' => now(),
        ]);

        [$patient, $visit] = $this->hospitalisedVisit();

        $this->actingAs($doctor)
            ->post(route('accueil.visits.stay-amount', $visit), ['stay_amount' => '50000'])
            ->assertForbidden();

        $this->actingAs($supervisor)
            ->post(route('accueil.visits.stay-amount', $visit), ['stay_amount' => '50000'])
            ->assertForbidden();

        $this->assertSame(0, Invoice::where('visit_id', $visit->id)->whereNotNull('agreed_stay_amount')->count());
    }

    public function test_the_supervisor_sees_the_amount_without_being_able_to_edit_it(): void
    {
        $accueil = User::factory()->create([
            'role' => Role::Accueil->value,
            'email_verified_at' => now(),
        ]);

        $supervisor = User::factory()->create([
            'role' => Role::DocteurGeneral->value,
            'email_verified_at' => now(),
        ]);

        [$patient, $visit] = $this->hospitalisedVisit();

        $this->actingAs($accueil)->post(route('accueil.visits.stay-amount', $visit), [
            'stay_amount' => '120000',
        ]);

        $this->actingAs($supervisor)
            ->get(route('accueil.index'))
            ->assertOk()
            ->assertSee('120')
            ->assertDontSee('name="stay_amount"', false);
    }

    public function test_a_negative_amount_is_refused(): void
    {
        $accueil = User::factory()->create([
            'role' => Role::Accueil->value,
            'email_verified_at' => now(),
        ]);

        [$patient, $visit] = $this->hospitalisedVisit();

        $this->actingAs($accueil)
            ->post(route('accueil.visits.stay-amount', $visit), ['stay_amount' => '-5000'])
            ->assertSessionHasErrors('stay_amount');
    }
}