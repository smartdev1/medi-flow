<?php

namespace Database\Seeders;

use App\Models\Medication;
use App\Models\MedicationOrder;
use App\Models\OnCallShift;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ClinicSeeder extends Seeder
{
    public function run(): void
    {
        $today = Carbon::today();

        $logistique = User::where('email', 'moussa.bamba@clinique-saintmatthieu.ci')->first();
        $medecin = User::where('email', 'adama.traore@clinique-saintmatthieu.ci')->first();
        $pediatre = User::where('email', 'fatoumata.sanogo@clinique-saintmatthieu.ci')->first();

        $medications = [
            ['name' => 'Paracétamol 1g', 'unit' => 'Comprimé', 'unit_price' => 500, 'stock_quantity' => 200, 'low_stock_threshold' => 30],
            ['name' => 'Amoxicilline 500mg', 'unit' => 'Gélule', 'unit_price' => 1500, 'stock_quantity' => 150, 'low_stock_threshold' => 30],
            ['name' => 'Ibuprofène 400mg', 'unit' => 'Comprimé', 'unit_price' => 800, 'stock_quantity' => 120, 'low_stock_threshold' => 25],
            ['name' => 'Sirop antitussif', 'unit' => 'Flacon', 'unit_price' => 2500, 'stock_quantity' => 3, 'low_stock_threshold' => 10],
            ['name' => 'Vitamine C', 'unit' => 'Comprimé', 'unit_price' => 1000, 'stock_quantity' => 300, 'low_stock_threshold' => 40],
            ['name' => 'Antalgique', 'unit' => 'Comprimé', 'unit_price' => 700, 'stock_quantity' => 180, 'low_stock_threshold' => 30],
            ['name' => 'Oméprazole 20mg', 'unit' => 'Gélule', 'unit_price' => 1200, 'stock_quantity' => 90, 'low_stock_threshold' => 20],
            ['name' => 'Metformine 500mg', 'unit' => 'Comprimé', 'unit_price' => 1000, 'stock_quantity' => 60, 'low_stock_threshold' => 20],
            ['name' => 'Amlodipine 5mg', 'unit' => 'Comprimé', 'unit_price' => 1300, 'stock_quantity' => 50, 'low_stock_threshold' => 15],
            ['name' => 'Solution de réhydratation orale', 'unit' => 'Sachet', 'unit_price' => 300, 'stock_quantity' => 8, 'low_stock_threshold' => 20],
            ['name' => 'Artéméther-Luméfantrine', 'unit' => 'Plaquette', 'unit_price' => 3000, 'stock_quantity' => 100, 'low_stock_threshold' => 25],
            ['name' => 'Fer + Acide folique', 'unit' => 'Comprimé', 'unit_price' => 600, 'stock_quantity' => 150, 'low_stock_threshold' => 30],
        ];

        foreach ($medications as $entry) {
            Medication::firstOrCreate(['name' => $entry['name']], $entry);
        }

        $wards = [
            'Médecine générale' => [
                ['number' => 'Chambre 10', 'capacity' => 2],
                ['number' => 'Chambre 11', 'capacity' => 2],
                ['number' => 'Chambre 12', 'capacity' => 2],
            ],
            'Chirurgie' => [
                ['number' => 'Chambre 5', 'capacity' => 1],
                ['number' => 'Chambre 6', 'capacity' => 1],
            ],
            'Maternité' => [
                ['number' => 'Chambre 8', 'capacity' => 2],
                ['number' => 'Chambre 9', 'capacity' => 2],
            ],
            'Pédiatrie' => [
                ['number' => 'Chambre 14', 'capacity' => 2],
                ['number' => 'Chambre 15', 'capacity' => 1, 'maintenance' => true],
            ],
        ];

        foreach ($wards as $ward => $rooms) {
            foreach ($rooms as $room) {
                $capacity = $room['capacity'];
                $type = $capacity === 1 ? \App\Models\Room::TYPE_INDIVIDUELLE : \App\Models\Room::TYPE_GROUPE;
                $dailyRate = $capacity === 1 ? 15_000 : 10_000;

                Room::updateOrCreate(
                    ['ward' => $ward, 'number' => $room['number']],
                    [
                        'capacity' => $capacity,
                        'type' => $type,
                        'daily_rate' => $dailyRate,
                        'maintenance' => $room['maintenance'] ?? false,
                    ]
                );
            }
        }

        foreach ([$medecin, $pediatre] as $doctor) {
            if ($doctor) {
                OnCallShift::firstOrCreate(['user_id' => $doctor->id, 'date' => $today->format('Y-m-d')]);
            }
        }

        if ($logistique) {
            $order = MedicationOrder::firstOrCreate(
                ['requested_by' => $logistique->id, 'status' => MedicationOrder::STATUS_PENDING],
                [
                    'reference' => MedicationOrder::generateReference(),
                    'ordered_at' => $today->copy()->setTimeFromTimeString('08:00'),
                    'notes' => 'Réapprovisionnement urgent — stocks sous le seuil d\'alerte.',
                ]
            );

            foreach (['Sirop antitussif' => 50, 'Solution de réhydratation orale' => 100] as $name => $quantity) {
                $medication = Medication::where('name', $name)->first();

                if ($medication) {
                    $order->items()->firstOrCreate(['medication_id' => $medication->id], ['quantity' => $quantity]);
                }
            }
        }
    }
}
