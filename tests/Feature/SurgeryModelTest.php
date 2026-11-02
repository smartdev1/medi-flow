<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Surgery;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SurgeryModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_creates_surgeries_table(): void
    {
        $this->assertTrue(Schema::hasTable('surgeries'));

        $expectedColumns = [
            'id',
            'visit_id',
            'patient_id',
            'surgeon_id',
            'anesthetist_id',
            'scheduled_at',
            'status',
            'surgery_type',
            'procedure_name',
            'pre_anesthetic_notes',
            'consent_signed_at',
            'consent_signed_by',
            'notes',
            'started_at',
            'completed_at',
            'created_at',
            'updated_at',
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('surgeries', $column),
                "Column '{$column}' should exist on surgeries table."
            );
        }
    }

    public function test_surgery_belongs_to_visit(): void
    {
        $surgery = Surgery::factory()->create();

        $this->assertInstanceOf(Visit::class, $surgery->visit);
        $this->assertSame($surgery->visit_id, $surgery->visit->id);
    }

    public function test_surgery_belongs_to_patient(): void
    {
        $surgery = Surgery::factory()->create();

        $this->assertInstanceOf(Patient::class, $surgery->patient);
        $this->assertSame($surgery->patient_id, $surgery->patient->id);
    }

    public function test_surgery_belongs_to_surgeon(): void
    {
        $surgeon = User::factory()->create();
        $surgery = Surgery::factory()->create(['surgeon_id' => $surgeon->id]);

        $this->assertInstanceOf(User::class, $surgery->surgeon);
        $this->assertSame($surgeon->id, $surgery->surgeon->id);
    }

    public function test_surgery_belongs_to_anesthetist(): void
    {
        // Without anesthetist
        $surgery = Surgery::factory()->create(['anesthetist_id' => null]);
        $this->assertNull($surgery->anesthetist);

        // With anesthetist
        $anesthetist = User::factory()->create();
        $surgery->update(['anesthetist_id' => $anesthetist->id]);
        $surgery->refresh();

        $this->assertInstanceOf(User::class, $surgery->anesthetist);
        $this->assertSame($anesthetist->id, $surgery->anesthetist->id);
    }

    public function test_visit_has_many_surgeries(): void
    {
        $visit = Visit::factory()->create();
        $patient = Patient::factory()->create();

        Surgery::factory()->count(3)->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
        ]);

        $this->assertCount(3, $visit->surgeries);
        $this->assertInstanceOf(Surgery::class, $visit->surgeries->first());
    }

    public function test_patient_has_many_surgeries(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create(['patient_id' => $patient->id]);

        Surgery::factory()->count(2)->create([
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
        ]);

        $this->assertCount(2, $patient->surgeries);
        $this->assertInstanceOf(Surgery::class, $patient->surgeries->first());
    }

    public function test_factory_produces_valid_model(): void
    {
        $surgery = Surgery::factory()->create();

        $this->assertDatabaseHas('surgeries', ['id' => $surgery->id]);
        $this->assertSame(Surgery::STATUS_SCHEDULED, $surgery->status);
        $this->assertSame('interne', $surgery->surgery_type);
        $this->assertNotEmpty($surgery->procedure_name);
    }
}
