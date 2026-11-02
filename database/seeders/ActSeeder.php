<?php

namespace Database\Seeders;

use App\Enums\ActCategory;
use App\Models\Act;
use Illuminate\Database\Seeder;

class ActSeeder extends Seeder
{
    /**
     * Catalogue des actes médicaux (référentiel de facturation).
     * Tarifs en FCFA. insured_price = tarif conventionné (CMU / Assurance).
     * roles = praticiens habilités à réaliser l'acte.
     */
    public function run(): void
    {
        $acts = [
            // 1. Consultations
            ['CONS-GEN', 'Consultation générale', ActCategory::Consultation, 5000, 3500, ['medecin']],
            ['CONS-SUIVI', 'Consultation de suivi / contrôle', ActCategory::Consultation, 3000, 2000, ['medecin', 'pediatre']],
            ['CONS-URG', 'Consultation d\'urgence', ActCategory::Consultation, 10000, 7000, ['medecin', 'pediatre']],
            ['CONS-PED', 'Consultation pédiatrique', ActCategory::Consultation, 5000, 3500, ['pediatre']],
            ['CONS-GYN', 'Consultation gynécologie', ActCategory::Consultation, 10000, 7000, ['medecin']],
            ['CONS-CARDIO', 'Consultation cardiologie', ActCategory::Consultation, 15000, 10000, ['medecin']],
            ['CONS-DERMA', 'Consultation dermatologie', ActCategory::Consultation, 12000, 8000, ['medecin']],
            ['CONS-ORL', 'Consultation ORL', ActCategory::Consultation, 12000, 8000, ['medecin']],
            ['CONS-OPHT', 'Consultation ophtalmologie', ActCategory::Consultation, 12000, 8000, ['medecin']],

            // 2. Laboratoire
            ['LAB-NFS', 'NFS (numération formule sanguine)', ActCategory::Laboratoire, 3000, 2000, ['laborantin']],
            ['LAB-GLYC', 'Glycémie', ActCategory::Laboratoire, 1500, 1000, ['laborantin']],
            ['LAB-LIPID', 'Bilan lipidique', ActCategory::Laboratoire, 4000, 2500, ['laborantin']],
            ['LAB-URINE', 'Analyse d\'urines', ActCategory::Laboratoire, 2000, 1500, ['laborantin']],
            ['LAB-SERO-VIH', 'Test sérologique VIH', ActCategory::Laboratoire, 2500, 2000, ['laborantin']],
            ['LAB-SERO-HEP', 'Sérologies hépatites (A,B,C)', ActCategory::Laboratoire, 5000, 3500, ['laborantin']],
            ['LAB-GROSS', 'Test de grossesse', ActCategory::Laboratoire, 1500, 1000, ['laborantin']],
            ['LAB-BACT', 'Analyse bactériologique / microbiologique', ActCategory::Laboratoire, 5000, 3500, ['laborantin']],

            // 3. Imagerie médicale
            ['IMG-RX', 'Radiographie standard', ActCategory::Imagerie, 5000, 3500, ['medecin']],
            ['IMG-ECHO-ABD', 'Échographie abdominale', ActCategory::Imagerie, 10000, 7000, ['medecin']],
            ['IMG-ECHO-OBST', 'Échographie obstétricale', ActCategory::Imagerie, 12000, 8000, ['medecin', 'pediatre']],
            ['IMG-ECHO-PELV', 'Échographie pelvienne', ActCategory::Imagerie, 10000, 7000, ['medecin']],
            ['IMG-CT', 'Scanner / Tomodensitométrie', ActCategory::Imagerie, 40000, 28000, ['medecin']],
            ['IMG-IRM', 'IRM', ActCategory::Imagerie, 60000, 45000, ['medecin']],
            ['IMG-MAMMO', 'Mammographie', ActCategory::Imagerie, 12000, 9000, ['medecin']],

            // 4. Actes techniques & petite chirurgie
            ['CHIR-PANSEMENT', 'Pansement', ActCategory::Chirurgie, 2000, 1500, ['medecin', 'pediatre']],
            ['CHIR-SUTURE', 'Suture', ActCategory::Chirurgie, 5000, 3500, ['medecin', 'pediatre']],
            ['CHIR-INJECT', 'Injection', ActCategory::Chirurgie, 2000, 1500, ['medecin', 'pediatre']],
            ['CHIR-PETITE', 'Petite chirurgie (kyste, abcès)', ActCategory::Chirurgie, 15000, 10000, ['medecin']],
            ['CHIR-PERFUSION', 'Pose de perfusion', ActCategory::Chirurgie, 3000, 2000, ['medecin', 'pediatre']],

            // 5. Maternité & gynéco-obstétrique
            ['MAT-PRENAT', 'Consultation prénatale', ActCategory::Maternite, 4000, 2500, ['medecin']],
            ['MAT-ACCOUCH-N', 'Accouchement normal', ActCategory::Maternite, 50000, 35000, ['medecin']],
            ['MAT-ACCOUCH-C', 'Accouchement par césarienne', ActCategory::Maternite, 150000, 110000, ['medecin']],
            ['MAT-POSTNAT', 'Suivi postnatal', ActCategory::Maternite, 3000, 2000, ['medecin']],
            ['MAT-PLANFAM', 'Planning familial', ActCategory::Maternite, 2000, 1500, ['medecin']],

            // 6. Hospitalisation
            ['HOSP-JOUR-COMM', 'Journée d\'hospitalisation — chambre commune', ActCategory::Hospitalisation, 10000, 7000, ['medecin', 'pediatre']],
            ['HOSP-JOUR-INDIV', 'Journée d\'hospitalisation — chambre individuelle', ActCategory::Hospitalisation, 20000, 14000, ['medecin', 'pediatre']],
            ['HOSP-JOUR-VIP', 'Journée d\'hospitalisation — chambre VIP', ActCategory::Hospitalisation, 40000, 28000, ['medecin', 'pediatre']],
            ['HOSP-SOINS', 'Soins infirmiers en hospitalisation', ActCategory::Hospitalisation, 5000, 3500, ['medecin', 'pediatre']],
            ['HOSP-SURV', 'Surveillance médicale continue', ActCategory::Hospitalisation, 8000, 6000, ['medecin', 'pediatre']],

            // 7. Bloc opératoire
            ['BLOC-MINEURE', 'Chirurgie mineure', ActCategory::Bloc, 30000, 21000, ['medecin']],
            ['BLOC-MAJEURE', 'Chirurgie majeure', ActCategory::Bloc, 120000, 90000, ['medecin']],
            ['BLOC-ANESTH-LOC', 'Anesthésie locale', ActCategory::Bloc, 10000, 7000, ['medecin']],
            ['BLOC-ANESTH-GEN', 'Anesthésie générale', ActCategory::Bloc, 25000, 18000, ['medecin']],
            ['BLOC-LOCATION', 'Location de bloc opératoire (forfait)', ActCategory::Bloc, 50000, 35000, ['medecin']],

            // 8. Autres actes courants
            ['AUT-VACCIN', 'Vaccination', ActCategory::Autre, 3000, 2000, ['medecin', 'pediatre']],
            ['AUT-CERTIF', 'Certificat médical', ActCategory::Autre, 2000, 1500, ['medecin', 'pediatre']],
            ['AUT-CHECKUP', 'Bilan de santé / check-up complet', ActCategory::Autre, 25000, 18000, ['medecin']],
            ['AUT-KINE', 'Kinésithérapie / rééducation', ActCategory::Autre, 5000, 3500, ['medecin']],
            ['AUT-DENTAIRE', 'Consultation dentaire', ActCategory::Autre, 8000, 6000, ['medecin']],
        ];

        foreach ($acts as [$code, $label, $category, $base, $insured, $roles]) {
            Act::updateOrCreate(
                ['code' => $code],
                [
                    'label' => $label,
                    'category' => $category,
                    'base_price' => $base,
                    'insured_price' => $insured,
                    'roles' => $roles,
                ]
            );
        }
    }
}
