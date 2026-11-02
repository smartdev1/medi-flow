<?php

namespace Tests\Feature;

use App\Models\Act;
use App\Enums\ActCategory;
use App\Models\User;
use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckRenderedHtmlTest extends TestCase
{
    use RefreshDatabase;

    public function test_accueil_dashboard_renders_act_codes(): void
    {
        $this->seed(\Database\Seeders\ActSeeder::class);
        
        $user = User::factory()->withRole(Role::Accueil)->create();
        
        $response = $this->actingAs($user)->get(route("accueil.index"));
        
        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Check if data-code attributes are present in the HTML
        $hasChirCodes = str_contains($content, "data-code=\"CHIR-");
        $hasBlocCodes = str_contains($content, "data-code=\"BLOC-");
        
        dump("Has CHIR codes in HTML: " . ($hasChirCodes ? "YES" : "NO"));
        dump("Has BLOC codes in HTML: " . ($hasBlocCodes ? "YES" : "NO"));
        
        // Check for the surgery_type select
        $hasSurgeryTypeSelect = str_contains($content, "name=\"surgery_type\"");
        dump("Has surgery_type select: " . ($hasSurgeryTypeSelect ? "YES" : "NO"));
        
        // Check for x-show isSurgeryCategory
        $hasXShow = str_contains($content, "isSurgeryCategory");
        dump("Has isSurgeryCategory in HTML: " . ($hasXShow ? "YES" : "NO"));
        
        // Check Alpine component initialization
        $hasAlpine = str_contains($content, "patientForm");
        dump("Has patientForm component: " . ($hasAlpine ? "YES" : "NO"));
        
        $this->assertTrue($hasChirCodes, "CHIR codes should be in HTML");
        $this->assertTrue($hasBlocCodes, "BLOC codes should be in HTML");
    }
}
