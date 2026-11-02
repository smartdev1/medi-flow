<?php

namespace Tests\Feature;

use App\Enums\ActCategory;
use App\Enums\Role;
use App\Models\Act;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EtatQuotidienTest extends TestCase
{
    use RefreshDatabase;

    public function test_docteur_general_can_access(): void
    {
        $user = User::factory()->withRole(Role::DocteurGeneral)->create();

        $response = $this->actingAs($user)->get(route('etat-quotidien.index'));

        $response->assertStatus(200);
    }

    public function test_accueil_can_access(): void
    {
        $user = User::factory()->withRole(Role::Accueil)->create();

        $response = $this->actingAs($user)->get(route('etat-quotidien.index'));

        $response->assertStatus(200);
    }

    public function test_caissier_cannot_access(): void
    {
        $user = User::factory()->withRole(Role::Caissier)->create();

        $response = $this->actingAs($user)->get(route('etat-quotidien.index'));

        $response->assertStatus(403);
    }

    public function test_filter_by_date_works(): void
    {
        $user = User::factory()->withRole(Role::Accueil)->create();
        $patient = Patient::factory()->uninsured()->create();
        $act = Act::factory()->create();

        $todayVisit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'act_id' => $act->id,
            'arrived_at' => today()->setHour(9),
        ]);

        $yesterdayVisit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'act_id' => $act->id,
            'arrived_at' => today()->subDay()->setHour(9),
        ]);

        $response = $this->actingAs($user)->get(route('etat-quotidien.index', ['date' => today()->toDateString()]));

        $response->assertStatus(200);
        $response->assertSee($patient->full_name);

        $response2 = $this->actingAs($user)->get(route('etat-quotidien.index', ['date' => today()->subDays(2)->toDateString()]));

        $response2->assertStatus(200);
        $response2->assertDontSee($patient->full_name);
    }

    public function test_filter_by_service_works(): void
    {
        $user = User::factory()->withRole(Role::Accueil)->create();
        $consultationAct = Act::factory()->create(['category' => ActCategory::Consultation->value]);
        $labAct = Act::factory()->create(['category' => ActCategory::Laboratoire->value]);

        $consultPatient = Patient::factory()->uninsured()->create(['first_name' => 'ConsultPatient', 'last_name' => 'Unique']);
        $labPatient = Patient::factory()->uninsured()->create(['first_name' => 'LabPatient', 'last_name' => 'Unique']);

        Visit::factory()->create([
            'patient_id' => $consultPatient->id,
            'act_id' => $consultationAct->id,
            'arrived_at' => today()->setHour(9),
        ]);

        Visit::factory()->create([
            'patient_id' => $labPatient->id,
            'act_id' => $labAct->id,
            'arrived_at' => today()->setHour(10),
        ]);

        $response = $this->actingAs($user)->get(route('etat-quotidien.index', [
            'service' => ActCategory::Consultation->value,
        ]));

        $response->assertSee('ConsultPatient');
        $response->assertDontSee('LabPatient');
    }

    public function test_filter_by_statut_works(): void
    {
        $user = User::factory()->withRole(Role::Accueil)->create();
        $act = Act::factory()->create();

        $waitingPatient = Patient::factory()->uninsured()->create(['first_name' => 'WaitingOne', 'last_name' => 'Test']);
        $consultPatient = Patient::factory()->uninsured()->create(['first_name' => 'ConsultOne', 'last_name' => 'Test']);

        Visit::factory()->create([
            'patient_id' => $waitingPatient->id,
            'act_id' => $act->id,
            'arrived_at' => today()->setHour(8),
            'status' => Visit::STATUS_WAITING,
        ]);

        Visit::factory()->create([
            'patient_id' => $consultPatient->id,
            'act_id' => $act->id,
            'arrived_at' => today()->setHour(9),
            'status' => Visit::STATUS_IN_CONSULTATION,
        ]);

        $response = $this->actingAs($user)->get(route('etat-quotidien.index', [
            'statut' => Visit::STATUS_WAITING,
        ]));

        $response->assertSee('WaitingOne');
        $response->assertDontSee('ConsultOne');
    }

    public function test_search_by_patient_name(): void
    {
        $user = User::factory()->withRole(Role::Accueil)->create();
        $act = Act::factory()->create();

        $target = Patient::factory()->uninsured()->create(['first_name' => 'Amadou', 'last_name' => 'Diallo']);
        $other = Patient::factory()->uninsured()->create(['first_name' => 'Fatou', 'last_name' => 'Kone']);

        Visit::factory()->create(['patient_id' => $target->id, 'act_id' => $act->id, 'arrived_at' => today()->setHour(8)]);
        Visit::factory()->create(['patient_id' => $other->id, 'act_id' => $act->id, 'arrived_at' => today()->setHour(9)]);

        $response = $this->actingAs($user)->get(route('etat-quotidien.index', ['search' => 'Amadou']));

        $response->assertSee('Amadou');
        $response->assertDontSee('Fatou');
    }

    public function test_search_by_patient_code(): void
    {
        $user = User::factory()->withRole(Role::Accueil)->create();
        $act = Act::factory()->create();

        $target = Patient::factory()->uninsured()->create(['code' => 'CSM-9999']);
        $other = Patient::factory()->uninsured()->create(['code' => 'CSM-0001']);

        Visit::factory()->create(['patient_id' => $target->id, 'act_id' => $act->id, 'arrived_at' => today()->setHour(8)]);
        Visit::factory()->create(['patient_id' => $other->id, 'act_id' => $act->id, 'arrived_at' => today()->setHour(9)]);

        $response = $this->actingAs($user)->get(route('etat-quotidien.index', ['search' => 'CSM-9999']));

        $response->assertSee('CSM-9999');
        $response->assertDontSee('CSM-0001');
    }

    public function test_patient_without_discharged_at(): void
    {
        $user = User::factory()->withRole(Role::Accueil)->create();
        $act = Act::factory()->create();
        $patient = Patient::factory()->uninsured()->create();

        Visit::factory()->create([
            'patient_id' => $patient->id,
            'act_id' => $act->id,
            'arrived_at' => today()->setHour(10),
            'discharged_at' => null,
        ]);

        $response = $this->actingAs($user)->get(route('etat-quotidien.index'));

        $response->assertStatus(200);
        // The discharged_at column should show the dash placeholder
        $response->assertSee($patient->full_name);
    }

    public function test_patient_with_discharged_at(): void
    {
        $user = User::factory()->withRole(Role::Accueil)->create();
        $act = Act::factory()->create();
        $patient = Patient::factory()->uninsured()->create();

        Visit::factory()->create([
            'patient_id' => $patient->id,
            'act_id' => $act->id,
            'arrived_at' => today()->setHour(10),
            'discharged_at' => today()->setHour(14)->setMinute(30),
        ]);

        $response = $this->actingAs($user)->get(route('etat-quotidien.index'));

        $response->assertStatus(200);
        $response->assertSee('14:30');
    }

    public function test_csv_export_returns_download(): void
    {
        $user = User::factory()->withRole(Role::Accueil)->create();

        $response = $this->actingAs($user)->get(route('etat-quotidien.export-csv'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_aide_soignante_can_access(): void
    {
        $user = User::factory()->withRole(Role::AideSoignante)->create();

        $response = $this->actingAs($user)->get(route('etat-quotidien.index'));

        $response->assertStatus(200);
    }
}
