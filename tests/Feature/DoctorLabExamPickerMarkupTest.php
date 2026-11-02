<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le sélecteur d'examens est un composant Alpine compilé par Vite.
 *
 * Sa logique a longtemps été écrite en ligne dans la vue, où une erreur de
 * syntaxe ne se voyait pas : la page se rendait normalement et le composant
 * restait inerte. Ces tests verrouillent le branchement, l'absence de prix côté
 * médecin, et l'absence de toute logique de positionnement.
 */
class DoctorLabExamPickerMarkupTest extends TestCase
{
    use RefreshDatabase;

    private function renderDashboard(): string
    {
        $doctor = User::factory()->create([
            'role' => Role::Medecin->value,
            'email_verified_at' => now(),
        ]);

        $patient = Patient::factory()->create();
        $visit = Visit::factory()->inConsultation()->create([
            'patient_id' => $patient->id,
            'act_id' => null,
            'vitals_taken_at' => now(),
        ]);

        return $this->actingAs($doctor)
            ->get(route('medecin.index', ['patient' => $visit->id]))
            ->assertOk()
            ->getContent();
    }

    public function test_the_dashboard_uses_the_registered_alpine_component(): void
    {
        $this->assertStringContainsString('x-data="labExamPicker(', $this->renderDashboard());
    }

    public function test_the_component_is_registered_before_alpine_starts(): void
    {
        $app = file_get_contents(resource_path('js/app.js'));

        $registration = strpos($app, "Alpine.data('labExamPicker'");
        $start = strpos($app, 'Alpine.start()');

        $this->assertNotFalse($registration, 'Le composant doit être enregistré.');
        $this->assertNotFalse($start);
        $this->assertLessThan(
            $start,
            $registration,
            'Alpine doit connaître le composant avant de démarrer.'
        );
    }

    public function test_no_price_is_shown_or_submitted_to_the_doctor(): void
    {
        $html = $this->renderDashboard();
        $view = file_get_contents(
            resource_path('views/components/ui/lab-exam-picker.blade.php')
        );

        // Le médecin choisit une analyse, il ne la chiffre pas : le tarif vient
        // du catalogue, côté serveur. Un prix affiché ou transmis ferait une
        // seconde source de vérité, capable de diverger du catalogue.
        $this->assertStringNotContainsString('formatted_price', $view);
        $this->assertStringNotContainsString('name="price"', $html);
        $this->assertStringNotContainsString('Prix de l\'examen', $html);
    }

    public function test_the_price_never_travels_in_the_submitted_payload(): void
    {
        // Seuls l'identifiant et le libellé sont transmis : le serveur retrouve
        // le tarif dans le catalogue.
        $view = file_get_contents(
            resource_path('views/components/ui/lab-exam-picker.blade.php')
        );

        $this->assertStringContainsString('[lab_exam_id]', $view);
        $this->assertStringContainsString('[name]', $view);
        $this->assertStringNotContainsString('[price]', $view);
    }

    public function test_several_exams_can_be_requested_at_once(): void
    {
        $view = file_get_contents(
            resource_path('views/components/ui/lab-exam-picker.blade.php')
        );

        $this->assertStringContainsString('x-for="(item, index) in items"', $view);
        $this->assertStringContainsString('add(exam)', $view);
        $this->assertStringContainsString('remove(index)', $view);
    }

    public function test_an_exam_outside_the_catalogue_is_not_offered_as_free_entry(): void
    {
        $view = file_get_contents(
            resource_path('views/components/ui/lab-exam-picker.blade.php')
        );

        // Hors catalogue, aucun tarif de référence : l'examen serait consommé
        // au laboratoire sans jamais être facturé.
        $this->assertStringNotContainsString('useFreeEntry', $view);
        $this->assertStringContainsString('doit être ajouté au catalogue', $view);
    }

    public function test_the_duplicate_exam_is_flagged(): void
    {
        $source = file_get_contents(resource_path('js/components/labExamPicker.js'));

        $this->assertStringContainsString('alreadyAdded', $source);
    }

    public function test_the_suggestions_stay_in_the_drawer_flow(): void
    {
        $view = file_get_contents(
            resource_path('views/components/ui/lab-exam-picker.blade.php')
        );

        // Déplacer le panneau hors du tiroir échappait au rognage de son corps
        // défilant, mais laissait son contenu non initialisé : `x-cloak` n'était
        // jamais retiré et la liste restait invisible en permanence.
        $this->assertStringNotContainsString('x-teleport', $view);
        $this->assertStringNotContainsString('anchorStyle', $view);
        $this->assertStringNotContainsString('position: fixed', $view);
        $this->assertStringContainsString('max-h-56 overflow-y-auto', $view);
    }

    public function test_the_removed_single_exam_component_is_gone(): void
    {
        // Le composant mono-examen a été remplacé : le garder risquerait de
        // faire réapparaître une saisie libre avec prix.
        $this->assertFileDoesNotExist(
            resource_path('views/components/ui/lab-exam-search.blade.php')
        );
        $this->assertFileDoesNotExist(
            resource_path('js/components/labExamSearch.js')
        );
    }
}