<?php

namespace Tests\Feature;

use App\Models\Act;
use App\Enums\ActCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckActCodesTest extends TestCase
{
    use RefreshDatabase;

    public function test_act_codes_for_surgery(): void
    {
        $this->seed(\Database\Seeders\ActSeeder::class);
        
        $chirActs = Act::where("code", "like", "CHIR%")->get(["code", "label", "category"]);
        $blocActs = Act::where("code", "like", "BLOC%")->get(["code", "label", "category"]);
        
        dump("CHIR acts: " . $chirActs->count());
        $chirActs->each(fn($a) => dump($a->code . " - " . $a->label . " - " . $a->category));
        
        dump("BLOC acts: " . $blocActs->count());
        $blocActs->each(fn($a) => dump($a->code . " - " . $a->label . " - " . $a->category));
        
        $this->assertGreaterThan(0, $chirActs->count());
        $this->assertGreaterThan(0, $blocActs->count());
    }
}
