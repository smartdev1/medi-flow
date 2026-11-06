<?php

namespace Database\Seeders;

use App\Models\Medication;
use Illuminate\Database\Seeder;

class ClinicSeeder extends Seeder
{
    public function run(): void
    {
        $medications = [
            ['name' => 'Paracétamol 1g', 'unit' => 'Comprimé', 'unit_price' => 500, 'stock_quantity' => 200, 'low_stock_threshold' => 30],
            ['name' => 'Amoxicilline 500mg', 'unit' => 'Gélule', 'unit_price' => 1500, 'stock_quantity' => 150, 'low_stock_threshold' => 30],
            ['name' => 'Ibuprofène 400mg', 'unit' => 'Comprimé', 'unit_price' => 800, 'stock_quantity' => 120, 'low_stock_threshold' => 25],
            ['name' => 'Sirop antitussif', 'unit' => 'Flacon', 'unit_price' => 2500, 'stock_quantity' => 50, 'low_stock_threshold' => 10],
            ['name' => 'Vitamine C', 'unit' => 'Comprimé', 'unit_price' => 1000, 'stock_quantity' => 300, 'low_stock_threshold' => 40],
            ['name' => 'Antalgique', 'unit' => 'Comprimé', 'unit_price' => 700, 'stock_quantity' => 180, 'low_stock_threshold' => 30],
            ['name' => 'Oméprazole 20mg', 'unit' => 'Gélule', 'unit_price' => 1200, 'stock_quantity' => 90, 'low_stock_threshold' => 20],
            ['name' => 'Metformine 500mg', 'unit' => 'Comprimé', 'unit_price' => 1000, 'stock_quantity' => 60, 'low_stock_threshold' => 20],
            ['name' => 'Amlodipine 5mg', 'unit' => 'Comprimé', 'unit_price' => 1300, 'stock_quantity' => 50, 'low_stock_threshold' => 15],
            ['name' => 'Solution de réhydratation orale', 'unit' => 'Sachet', 'unit_price' => 300, 'stock_quantity' => 100, 'low_stock_threshold' => 20],
            ['name' => 'Artéméther-Luméfantrine', 'unit' => 'Plaquette', 'unit_price' => 3000, 'stock_quantity' => 100, 'low_stock_threshold' => 25],
            ['name' => 'Fer + Acide folique', 'unit' => 'Comprimé', 'unit_price' => 600, 'stock_quantity' => 150, 'low_stock_threshold' => 30],
        ];

        foreach ($medications as $entry) {
            Medication::firstOrCreate(['name' => $entry['name']], $entry);
        }
    }
}
