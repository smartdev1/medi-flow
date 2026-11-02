<?php

namespace Tests\Feature;

use App\Models\Act;
use App\Enums\ActCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckActDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_acts_display_in_correct_categories(): void
    {
        $this->seed(\Database\Seeders\ActSeeder::class);
        
        $acts = Act::orderBy("category")->orderBy("label")->get();
        
        dump("Total acts: " . $acts->count());
        
        $byCategory = $acts->groupBy("category");
        
        foreach ($byCategory as $category => $categoryActs) {
            dump("Category: " . $category . " (" . $categoryActs->count() . " acts)");
            $categoryActs->each(fn($a) => dump("  - " . $a->code . " | " . $a->label));
        }
        
        // Check specifically for CHIR and BLOC
        $chirActs = $acts->filter(fn($a) => str_starts_with($a->code, "CHIR"));
        $blocActs = $acts->filter(fn($a) => str_starts_with($a->code, "BLOC"));
        
        dump("CHIR acts: " . $chirActs->count());
        $chirActs->each(fn($a) => dump("  - " . $a->code . " | category: " . $a->category));
        
        dump("BLOC acts: " . $blocActs->count());
        $blocActs->each(fn($a) => dump("  - " . $a->code . " | category: " . $a->category));
        
        $this->assertGreaterThan(0, $chirActs->count());
        $this->assertGreaterThan(0, $blocActs->count());
    }
}
