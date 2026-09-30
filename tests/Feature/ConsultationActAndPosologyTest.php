<?php

namespace Tests\Feature;

use App\Enums\ActCategory;
use App\Enums\Role;
use App\Models\Act;
use App\Models\Consultation;
use App\Models\Invoice;
use App\Models\Medication;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * L'acte réalisé et la posologie sont saisis par le médecin, au moment où il
 * fait la consultation — et non à l'accueil, avant d'avoir vu le patient.
 */
class ConsultationActAndPosologyTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_consultation_without_an_act_is_billed_at_zero(): void
    {
        $doctor = $this->doctor();
        $patient = Patient::factory()->uninsured()->create();
        $visit = Visit::factory()->inConsultation()->create([
            'patient_id' => $patient->id,
            'act_id' => null,
        ]);

        $this->assertSame(0, Invoice::forVisit($visit)->subtotal);
    }

    public function test_the_doctor_can_record_the_act_performed(): void
    {
        $doctor = $this->doctor();
        $patient = Patient::factory()->uninsured()->create();
        $visit = Visit::factory()->inConsultation()->create([
            'patient_id' => $patient->id,
            'act_id' => null,
        ]);
        $consultation = Consultation::factory()->create([
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
            'doctor_id' => $doctor->id,
        ]);

        $act = Act::factory()->create([
            'label' => 'Médecine générale',
            'base_price' => 5_000,
            'insured_price' => null,
            'category' => ActCategory::Consultation->value,
        ]);

        $this->actingAs($doctor)
            ->post(route('consultations.update', $consultation), [
                'act_id' => $act->id,
                'notes' => 'RAS',
            ])
            ->assertRedirect();

        $this->assertSame($act->id, $visit->fresh()->act_id);

        // La consultation n'est plus à 0 FCFA : l'acte entre dans la facture.
        $invoice = Invoice::forVisit($visit->fresh());
        $this->assertSame(5_000, $invoice->subtotal);
        $this->assertSame(5_000, $invoice->patient_amount);
    }

    public function test_changing_the_act_recalculates_the_invoice(): void
    {
        $doctor = $this->doctor();
        $patient = Patient::factory()->uninsured()->create();

        $general = Act::factory()->create([
            'label' => 'Médecine générale',
            'base_price' => 5_000,
            'insured_price' => null,
            'category' => ActCategory::Consultation->value,
        ]);
        $urgent = Act::factory()->create([
            'label' => "Consultation d'urgence",
            'base_price' => 10_000,
            'insured_price' => null,
            'category' => ActCategory::Consultation->value,
        ]);

        $visit = Visit::factory()->inConsultation()->create([
            'patient_id' => $patient->id,
            'act_id' => $general->id,
        ]);
        $consultation = Consultation::factory()->create([
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
            'doctor_id' => $doctor->id,
        ]);

        $this->assertSame(5_000, Invoice::forVisit($visit)->subtotal);

        $this->actingAs($doctor)
            ->post(route('consultations.update', $consultation), ['act_id' => $urgent->id])
            ->assertRedirect();

        $this->assertSame(10_000, Invoice::forVisit($visit->fresh())->subtotal);
    }

    public function test_an_unknown_act_is_refused(): void
    {
        $doctor = $this->doctor();
        $patient = Patient::factory()->uninsured()->create();
        $visit = Visit::factory()->inConsultation()->create(['patient_id' => $patient->id]);
        $consultation = Consultation::factory()->create([
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
            'doctor_id' => $doctor->id,
        ]);

        $this->actingAs($doctor)
            ->post(route('consultations.update', $consultation), ['act_id' => 999999])
            ->assertSessionHasErrors('act_id');
    }

    public function test_the_doctor_can_add_a_posology_to_a_prescription(): void
    {
        $doctor = $this->doctor();
        $patient = Patient::factory()->create();
        $medication = Medication::create([
            'name' => 'Amoxicilline 500 mg',
            'stock_quantity' => 100,
            'unit_price' => 2_000,
        ]);

        $this->actingAs($doctor)
            ->post(route('prescriptions.store'), [
                'patient_id' => $patient->id,
                'date' => now()->format('Y-m-d'),
                'items' => [
                    [
                        'medication_id' => $medication->id,
                        'posology' => '1 comprimé matin et soir pendant 5 jours',
                        'quantity' => 10,
                    ],
                ],
            ])
            ->assertSessionHas('success');

        $prescription = Prescription::where('patient_id', $patient->id)->latest('id')->first();

        $this->assertNotNull($prescription);
        $this->assertSame(
            '1 comprimé matin et soir pendant 5 jours',
            $prescription->items->first()->posology
        );
    }

    public function test_a_prescription_remains_possible_without_a_posology(): void
    {
        $doctor = $this->doctor();
        $patient = Patient::factory()->create();
        $medication = Medication::create([
            'name' => 'Paracétamol',
            'stock_quantity' => 100,
            'unit_price' => 1_000,
        ]);

        $this->actingAs($doctor)
            ->post(route('prescriptions.store'), [
                'patient_id' => $patient->id,
                'date' => now()->format('Y-m-d'),
                'items' => [
                    ['medication_id' => $medication->id, 'quantity' => 2],
                ],
            ])
            ->assertSessionHas('success');

        $prescription = Prescription::where('patient_id', $patient->id)->latest('id')->first();
        $this->assertNull($prescription->items->first()->posology);
    }

    private function doctor(): User
    {
        return User::factory()->withRole(Role::Medecin)->create();
    }
}
