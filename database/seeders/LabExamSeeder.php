<?php

namespace Database\Seeders;

use App\Models\LabExam;
use Illuminate\Database\Seeder;

class LabExamSeeder extends Seeder
{
    private const EXAMS = [
        'Hématologie' => [
            ['name' => 'Numération Formule Sanguine (NFS)', 'price' => 5000],
            ['name' => 'Groupage sanguin (ABO/Rhésus)', 'price' => 3000],
            ['name' => 'Vitesse de sédimentation (VS)', 'price' => 2000],
            ['name' => "Taux d'hémoglobine", 'price' => 2000],
            ['name' => 'Temps de saignement / temps de coagulation', 'price' => 3000],
            ['name' => 'Taux de prothrombine (TP) / INR', 'price' => 4000],
            ['name' => 'Électrophorèse de l\'hémoglobine', 'price' => 15000],
        ],
        'Biochimie' => [
            ['name' => 'Glycémie (à jeun / post-prandiale)', 'price' => 3000],
            ['name' => 'Bilan lipidique (cholestérol total, HDL, LDL, triglycérides)', 'price' => 8000],
            ['name' => 'Bilan hépatique (ASAT, ALAT, gamma-GT, bilirubine)', 'price' => 10000],
            ['name' => 'Bilan rénal (urée, créatinine, clairance)', 'price' => 8000],
            ['name' => 'Ionogramme sanguin (Na, K, Cl, Ca)', 'price' => 6000],
            ['name' => 'Protéine C-Réactive (CRP)', 'price' => 4000],
            ['name' => 'Acide urique', 'price' => 3000],
        ],
        'Sérologie / Immunologie' => [
            ['name' => 'Sérologie VIH', 'price' => 5000],
            ['name' => 'Sérologie hépatite B (Ag HBs, anticorps)', 'price' => 8000],
            ['name' => 'Sérologie hépatite C', 'price' => 8000],
            ['name' => 'Sérologie syphilis (TPHA/VDRL)', 'price' => 5000],
            ['name' => 'Sérologie toxoplasmose', 'price' => 6000],
            ['name' => 'Sérologie rubéole', 'price' => 5000],
            ['name' => 'Test de grossesse (β-hCG)', 'price' => 3000],
            ['name' => 'Sérologie fièvre typhoïde (Widal)', 'price' => 5000],
        ],
        'Bactériologie / Microbiologie' => [
            ['name' => 'ECBU (Examen Cytobactériologique des Urines)', 'price' => 5000],
            ['name' => 'Coproculture (analyse des selles)', 'price' => 8000],
            ['name' => 'Prélèvement vaginal (PV)', 'price' => 5000],
            ['name' => 'Hémoculture', 'price' => 15000],
            ['name' => 'Antibiogramme', 'price' => 10000],
            ['name' => 'Prélèvement de gorge (angine)', 'price' => 4000],
            ['name' => 'Crachat / recherche de BAAR (tuberculose)', 'price' => 8000],
        ],
        'Parasitologie' => [
            ['name' => 'Goutte épaisse / frottis sanguin (paludisme)', 'price' => 3000],
            ['name' => 'Examen parasitologique des selles (EPS)', 'price' => 3000],
            ['name' => 'Recherche d\'œufs et de parasites intestinaux', 'price' => 3000],
        ],
        'Endocrinologie / Hormonologie' => [
            ['name' => 'Bilan thyroïdien (TSH, T3, T4)', 'price' => 15000],
            ['name' => 'Bilan hormonal (prolactine, cortisol, etc.)', 'price' => 20000],
            ['name' => 'HbA1c (hémoglobine glyquée, suivi diabète)', 'price' => 10000],
        ],
        'Analyses d\'urines' => [
            ['name' => 'Analyse d\'urines standard (bandelette)', 'price' => 2000],
            ['name' => 'Protéinurie', 'price' => 3000],
            ['name' => 'Glycosurie', 'price' => 2000],
            ['name' => 'Compte d\'Addis', 'price' => 4000],
        ],
        'Autres' => [
            ['name' => 'Test rapide au paludisme (TDR)', 'price' => 2000],
            ['name' => 'Recherche de sang occulte dans les selles', 'price' => 4000],
            ['name' => 'Dosage de la ferritine', 'price' => 8000],
            ['name' => 'Dosage de la vitamine D', 'price' => 12000],
            ['name' => 'Ionogramme urinaire', 'price' => 5000],
        ],
    ];

    public function run(): void
    {
        foreach (self::EXAMS as $category => $exams) {
            foreach ($exams as $exam) {
                LabExam::firstOrCreate([
                    'name' => $exam['name'],
                ], [
                    'category' => $category,
                    'price' => $exam['price'],
                ]);
            }
        }
    }
}
