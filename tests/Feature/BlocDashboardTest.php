<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Patient;
use App\Models\Surgery;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlocDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_medecin_can_access_bloc_dashboard(): void
    {
        $user = User::factory()->withRole(Role::Medecin)->create();

        $response = $this->actingAs($user)->get(route("bloc.index"));

        $response->assertStatus(200);
        $response->assertViewIs("bloc.dashboard");
    }

    public function test_aide_soignante_can_access_bloc_dashboard(): void
    {
        $user = User::factory()->withRole(Role::AideSoignante)->create();

        $response = $this->actingAs($user)->get(route("bloc.index"));

        $response->assertStatus(200);
    }

    public function test_caissier_cannot_access_bloc_dashboard(): void
    {
        $user = User::factory()->withRole(Role::Caissier)->create();

        $response = $this->actingAs($user)->get(route("bloc.index"));

        $response->assertStatus(403);
    }

    public function test_only_surgeries_for_selected_date_shown(): void
    {
        $user = User::factory()->withRole(Role::Medecin)->create();
        $patient = Patient::factory()->create();

        $targetDate = "2026-11-15";
        $todaySurgery = Surgery::factory()->create([
            "patient_id" => $patient->id,
            "scheduled_at" => $targetDate . " 09:00:00",
            "procedure_name" => "Appendicectomie",
        ]);

        $otherSurgery = Surgery::factory()->create([
            "patient_id" => $patient->id,
            "scheduled_at" => "2026-11-16 10:00:00",
            "procedure_name" => "Cholecystectomie",
        ]);

        $response = $this->actingAs($user)->get(route("bloc.index", ["date" => $targetDate]));

        $response->assertStatus(200);
        $response->assertSee("Appendicectomie");
        $response->assertDontSee("Cholecystectomie");
    }

    public function test_filter_by_status_works(): void
    {
        $user = User::factory()->withRole(Role::Medecin)->create();
        $patient = Patient::factory()->create();
        $date = today()->toDateString();

        $scheduled = Surgery::factory()->create([
            "patient_id" => $patient->id,
            "scheduled_at" => $date . " 08:00:00",
            "status" => Surgery::STATUS_SCHEDULED,
            "procedure_name" => "Scheduled Surgery",
        ]);

        $completed = Surgery::factory()->create([
            "patient_id" => $patient->id,
            "scheduled_at" => $date . " 10:00:00",
            "status" => Surgery::STATUS_COMPLETED,
            "procedure_name" => "Completed Surgery",
        ]);

        $response = $this->actingAs($user)->get(route("bloc.index", [
            "date" => $date,
            "status" => Surgery::STATUS_SCHEDULED,
        ]));

        $response->assertStatus(200);
        $response->assertSee("Scheduled Surgery");
        $response->assertDontSee("Completed Surgery");
    }

    public function test_empty_state_when_no_surgeries(): void
    {
        $user = User::factory()->withRole(Role::Medecin)->create();

        $response = $this->actingAs($user)->get(route("bloc.index"));

        $response->assertStatus(200);
        $response->assertSee("Aucune chirurgie");
    }
}
