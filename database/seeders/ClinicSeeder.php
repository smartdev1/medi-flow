<?php

namespace Database\Seeders;

use App\Models\Admission;
use App\Models\Consultation;
use App\Models\Exam;
use App\Models\Medication;
use App\Models\MedicationOrder;
use App\Models\OnCallShift;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Prescription;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ClinicSeeder extends Seeder
{
    public function run(): void
    {
        $today = Carbon::today();

        $receptionist = User::where('email', 'akissi.koffi@clinique-saintmatthieu.ci')->first();
        $logistique = User::where('email', 'moussa.bamba@clinique-saintmatthieu.ci')->first();

        // Catalogue de médicaments, avec deux références volontairement en
        // stock bas pour que les alertes de la Logistique aient de quoi
        // s'afficher dès la première connexion.
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

        $roster = [
            [
                'code' => 'CSM-8821', 'first_name' => 'Kouassi', 'last_name' => 'Yao', 'sex' => 'M',
                'birth_date' => '1990-03-14', 'phone' => '+225 07 01 02 03 04', 'coverage' => 'Privé',
                'visit' => ['time' => '08:15', 'orientation' => 'Médecin généraliste', 'reason' => 'Consultation de suivi', 'status' => 'En consultation', 'urgent' => false],
                'payment' => ['service' => 'Consultation — Généraliste', 'amount' => 10000, 'method' => 'Carte bancaire', 'status' => 'Payé'],
            ],
            [
                'code' => 'CSM-9012', 'first_name' => 'Adjoua', 'last_name' => 'Kouadio', 'sex' => 'F',
                'birth_date' => '2018-06-02', 'phone' => '+225 05 11 22 33 44', 'coverage' => 'CMU',
                'allergies' => 'Aucune connue', 'guardian_name' => 'Akissi Kouadio (mère)', 'emergency_contact' => '+225 05 11 22 33 44',
                'visit' => ['time' => '08:42', 'orientation' => 'Pédiatrie', 'reason' => 'Fièvre — première consultation', 'status' => 'En attente', 'urgent' => false],
                'payment' => ['service' => 'Consultation — Pédiatrie', 'amount' => 5000, 'method' => 'CMU', 'status' => 'En attente'],
            ],
            [
                'code' => 'CSM-7430', 'first_name' => 'Amara', 'last_name' => 'Ouattara', 'sex' => 'M',
                'birth_date' => '1975-11-30', 'phone' => '+225 01 22 33 44 55', 'coverage' => 'Privé',
                'visit' => ['time' => '09:05', 'orientation' => 'Urgence', 'reason' => 'Douleur thoracique', 'status' => 'Prioritaire', 'urgent' => true],
                'payment' => ['service' => 'Consultation — Urgence', 'amount' => 15000, 'method' => 'Espèces', 'status' => 'Payé'],
            ],
            [
                'code' => 'CSM-1290', 'first_name' => "Aya", 'last_name' => "N'Guessan", 'sex' => 'F',
                'birth_date' => '1992-01-19', 'phone' => '+225 07 44 55 66 77', 'coverage' => 'CMU',
                'allergies' => 'Pénicilline',
                'visit' => ['time' => '09:12', 'orientation' => 'Médecin généraliste', 'reason' => 'Contrôle de routine', 'status' => 'En attente', 'urgent' => false],
                'payment' => null,
            ],
            [
                'code' => 'CSM-4456', 'first_name' => 'Aminata', 'last_name' => 'Diallo', 'sex' => 'F',
                'birth_date' => '1988-09-08', 'phone' => '+225 05 66 77 88 99', 'coverage' => 'Assurance',
                'visit' => ['time' => '09:30', 'orientation' => 'Laboratoire', 'reason' => 'Suivi post-laboratoire', 'status' => 'Orienté', 'urgent' => false],
                'payment' => ['service' => 'Analyses — Laboratoire', 'amount' => 12000, 'method' => 'Assurance CNAM', 'status' => 'Payé'],
            ],
            [
                'code' => 'CSM-3387', 'first_name' => 'Ibrahim', 'last_name' => 'Koné', 'sex' => 'M',
                'birth_date' => '1965-04-25', 'phone' => '+225 01 88 99 00 11', 'coverage' => 'CMU',
                'visit' => ['time' => '08:50', 'orientation' => 'Médecin généraliste', 'reason' => 'Renouvellement ordonnance', 'status' => 'En attente', 'urgent' => false],
                'payment' => ['service' => 'Renouvellement ordonnance', 'amount' => 3000, 'method' => 'Mobile Money', 'status' => 'Partiellement payé'],
            ],
        ];

        foreach ($roster as $entry) {
            $patient = Patient::firstOrCreate(
                ['code' => $entry['code']],
                [
                    'first_name' => $entry['first_name'],
                    'last_name' => $entry['last_name'],
                    'sex' => $entry['sex'],
                    'birth_date' => $entry['birth_date'],
                    'phone' => $entry['phone'],
                    'coverage' => $entry['coverage'],
                    'allergies' => $entry['allergies'] ?? null,
                    'guardian_name' => $entry['guardian_name'] ?? null,
                    'emergency_contact' => $entry['emergency_contact'] ?? null,
                ]
            );

            $visit = $patient->visits()->firstOrCreate(
                [],
                [
                    'arrived_at' => $today->copy()->setTimeFromTimeString($entry['visit']['time']),
                    'orientation' => $entry['visit']['orientation'],
                    'reason' => $entry['visit']['reason'],
                    'registered_by' => $receptionist?->id,
                    'status' => $entry['visit']['status'],
                    'urgent' => $entry['visit']['urgent'],
                ]
            );

            if ($entry['payment']) {
                $patient->payments()->firstOrCreate(
                    ['service' => $entry['payment']['service']],
                    [
                        'visit_id' => $visit->id,
                        'amount' => $entry['payment']['amount'],
                        'method' => $entry['payment']['method'],
                        'status' => $entry['payment']['status'],
                    ]
                );
            }
        }

        // Patients déjà plus loin dans le circuit (labo, pharmacie, hospitalisation),
        // sans passage par l'accueil du jour.
        $otherPatients = [
            ['code' => 'CSM-7765', 'first_name' => 'Affoué', 'last_name' => 'Konan', 'sex' => 'F', 'birth_date' => '1998-02-11', 'phone' => '+225 07 21 32 43 54', 'coverage' => 'Privé'],
            ['code' => 'CSM-5541', 'first_name' => 'Yao', 'last_name' => 'Konan', 'sex' => 'M', 'birth_date' => '1982-07-19', 'phone' => '+225 05 22 33 44 55', 'coverage' => 'CMU'],
            ['code' => 'CSM-6620', 'first_name' => 'Fatou', 'last_name' => 'Bamba', 'sex' => 'F', 'birth_date' => '1979-12-03', 'phone' => '+225 01 33 44 55 66', 'coverage' => 'Assurance'],
            ['code' => 'CSM-2214', 'first_name' => 'Koffi', 'last_name' => "N'Dri", 'sex' => 'M', 'birth_date' => '1970-05-27', 'phone' => '+225 07 44 55 66 77', 'coverage' => 'Privé'],
            ['code' => 'CSM-1188', 'first_name' => 'Aya', 'last_name' => 'Brou', 'sex' => 'F', 'birth_date' => '2015-09-14', 'phone' => '+225 05 55 66 77 88', 'coverage' => 'CMU'],
            ['code' => 'CSM-9931', 'first_name' => 'Yao', 'last_name' => 'Brou', 'sex' => 'M', 'birth_date' => '1960-01-08', 'phone' => '+225 01 66 77 88 99', 'coverage' => 'Privé'],
            ['code' => 'CSM-6003', 'first_name' => "N'Guessan", 'last_name' => 'Aka', 'sex' => 'M', 'birth_date' => '1955-10-22', 'phone' => '+225 07 77 88 99 00', 'coverage' => 'Privé'],
            ['code' => 'CSM-6144', 'first_name' => 'Akissi', 'last_name' => 'Yao', 'sex' => 'F', 'birth_date' => '1996-04-17', 'phone' => '+225 05 88 99 00 11', 'coverage' => 'Assurance'],
        ];

        foreach ($otherPatients as $entry) {
            Patient::firstOrCreate(['code' => $entry['code']], $entry);
        }

        // Affoué Konan vient d'arriver et n'a pas encore été orientée : elle
        // représente le stade "Accueil" du circuit. Yao Brou a terminé tout son
        // parcours aujourd'hui et représente le stade "Sortie".
        Patient::where('code', 'CSM-7765')->firstOrFail()->visits()->firstOrCreate(
            [],
            [
                'arrived_at' => $today->copy()->setTimeFromTimeString('08:50'),
                'orientation' => 'Non orientée',
                'status' => 'En attente',
                'urgent' => false,
            ]
        );

        Patient::where('code', 'CSM-9931')->firstOrFail()->visits()->firstOrCreate(
            [],
            [
                'arrived_at' => $today->copy()->setTimeFromTimeString('08:20'),
                'orientation' => 'Médecin généraliste',
                'status' => 'Orienté',
                'urgent' => false,
                'discharged_at' => $today->copy()->setTimeFromTimeString('09:48'),
            ]
        );

        $exams = [
            ['patient' => 'CSM-5541', 'name' => 'Glycémie à jeun', 'status' => Exam::STATUS_AWAITING_SAMPLE, 'time' => '08:58'],
            ['patient' => 'CSM-6620', 'name' => 'Bilan sanguin complet (NFS)', 'status' => Exam::STATUS_IN_PROGRESS, 'time' => '09:10'],
            ['patient' => 'CSM-4456', 'name' => 'Bilan biochimique', 'status' => Exam::STATUS_READY, 'time' => '09:35'],
            ['patient' => 'CSM-3387', 'name' => 'Bilan rénal', 'status' => Exam::STATUS_SENT, 'time' => '08:55'],
        ];

        foreach ($exams as $entry) {
            $patient = Patient::where('code', $entry['patient'])->firstOrFail();

            $patient->exams()->firstOrCreate(
                ['name' => $entry['name']],
                [
                    'status' => $entry['status'],
                    'requested_at' => $today->copy()->setTimeFromTimeString($entry['time']),
                ]
            );
        }

        $prescribingMedecin = User::where('email', 'adama.traore@clinique-saintmatthieu.ci')->first();
        $prescribingPediatre = User::where('email', 'fatoumata.sanogo@clinique-saintmatthieu.ci')->first();
        $dispensingPharmacien = User::where('email', 'aicha.cisse@clinique-saintmatthieu.ci')->first();

        $prescriptions = [
            [
                'patient' => 'CSM-2214', 'prescriber' => $prescribingMedecin,
                'items' => [['medication' => 'Amoxicilline 500mg', 'quantity' => 1], ['medication' => 'Paracétamol 1g', 'quantity' => 2]],
                'status' => Prescription::STATUS_AWAITING_DISPENSE, 'time' => '09:18',
            ],
            [
                'patient' => 'CSM-1188', 'prescriber' => $prescribingPediatre,
                'items' => [['medication' => 'Ibuprofène 400mg', 'quantity' => 2], ['medication' => 'Sirop antitussif', 'quantity' => 5]],
                'status' => Prescription::STATUS_AWAITING_DISPENSE, 'time' => '09:22',
            ],
            [
                'patient' => 'CSM-9931', 'prescriber' => $prescribingMedecin,
                'items' => [['medication' => 'Vitamine C', 'quantity' => 1], ['medication' => 'Antalgique', 'quantity' => 1]],
                'status' => Prescription::STATUS_DISPENSED, 'time' => '09:40',
            ],
        ];

        foreach ($prescriptions as $entry) {
            if (! $entry['prescriber']) {
                continue;
            }

            $patient = Patient::where('code', $entry['patient'])->firstOrFail();

            $shortage = collect($entry['items'])->contains(
                fn (array $item) => $item['quantity'] > Medication::where('name', $item['medication'])->value('stock_quantity')
            );

            $prescription = $patient->prescriptions()->firstOrCreate(
                [],
                [
                    'prescribed_by' => $entry['prescriber']->id,
                    'requested_at' => $today->copy()->setTimeFromTimeString($entry['time']),
                    'stock_status' => $shortage ? 'Rupture partielle' : 'Disponible',
                    'status' => $entry['status'],
                ]
            );

            foreach ($entry['items'] as $item) {
                $medication = Medication::where('name', $item['medication'])->firstOrFail();

                $prescription->items()->firstOrCreate(
                    ['medication_id' => $medication->id],
                    [
                        'name' => $medication->name,
                        'quantity' => $item['quantity'],
                        'unit_price' => $medication->unit_price,
                    ]
                );
            }

            if ($entry['status'] === Prescription::STATUS_DISPENSED && $dispensingPharmacien && ! $prescription->dispensed_at) {
                $prescription->update([
                    'dispensed_by' => $dispensingPharmacien->id,
                    'dispensed_at' => $today->copy()->setTimeFromTimeString('09:45'),
                ]);

                $prescription->loadMissing('items');

                Payment::firstOrCreate(
                    ['prescription_id' => $prescription->id],
                    [
                        'patient_id' => $patient->id,
                        'visit_id' => $patient->visits()->latest('arrived_at')->value('id'),
                        'service' => 'Pharmacie — '.$prescription->medications_summary,
                        'amount' => $prescription->total_amount,
                        'method' => 'Espèces',
                        'status' => 'Payé',
                    ]
                );
            }
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
                Room::firstOrCreate(
                    ['ward' => $ward, 'number' => $room['number']],
                    [
                        'capacity' => $room['capacity'],
                        'maintenance' => $room['maintenance'] ?? false,
                    ]
                );
            }
        }

        $admissions = [
            [
                'patient' => 'CSM-7430', 'room' => 'Chambre 12', 'bed' => 1,
                'reason' => 'Surveillance — douleur thoracique', 'attending' => 'Médecin en charge',
                'status' => Admission::STATUS_OBSERVATION, 'admitted_at' => $today->copy()->setTimeFromTimeString('09:40'),
            ],
            [
                'patient' => 'CSM-6003', 'room' => 'Chambre 5', 'bed' => 1,
                'reason' => 'Suites opératoires (J+2)', 'attending' => 'Dr. Chirurgien référent',
                'status' => Admission::STATUS_STABLE, 'admitted_at' => $today->copy()->subDays(2)->setTimeFromTimeString('10:00'),
            ],
            [
                'patient' => 'CSM-6144', 'room' => 'Chambre 8', 'bed' => 1,
                'reason' => 'Suites de couches', 'attending' => 'Sage-femme référente',
                'status' => Admission::STATUS_DISCHARGE_PLANNED, 'admitted_at' => $today->copy()->subDay()->setTimeFromTimeString('14:00'),
            ],
        ];

        foreach ($admissions as $entry) {
            $patient = Patient::where('code', $entry['patient'])->firstOrFail();
            $room = Room::where('number', $entry['room'])->firstOrFail();

            Admission::firstOrCreate(
                ['patient_id' => $patient->id, 'room_id' => $room->id, 'bed_number' => $entry['bed']],
                [
                    'reason' => $entry['reason'],
                    'attending' => $entry['attending'],
                    'status' => $entry['status'],
                    'admitted_at' => $entry['admitted_at'],
                ]
            );
        }

        // Consultations en cours, pour que les patients déjà "en consultation"
        // dans le récit du circuit aient une vraie fiche (constantes, notes,
        // médecin) au lieu d'un simple statut de visite.
        $medecin = User::where('email', 'adama.traore@clinique-saintmatthieu.ci')->first();
        $pediatre = User::where('email', 'fatoumata.sanogo@clinique-saintmatthieu.ci')->first();

        $consultations = [
            [
                'patient' => 'CSM-1290', 'doctor' => $medecin, 'orientation' => 'Médecin généraliste',
                'vitals' => [
                    ['label' => 'Tension', 'value' => '12/8'],
                    ['label' => 'Température', 'value' => '37.1°C'],
                    ['label' => 'Pouls', 'value' => '76 bpm'],
                    ['label' => 'Saturation', 'value' => '98%'],
                ],
                'notes' => "Patiente se présentant pour un contrôle de routine. Absence de plainte particulière. Tension et constantes stables par rapport à la dernière visite. Poursuite du traitement en cours, renouvellement de l'ordonnance pour 3 mois.",
                'time' => '09:24',
            ],
            [
                'patient' => 'CSM-9012', 'doctor' => $pediatre, 'orientation' => 'Pédiatrie',
                'vitals' => [
                    ['label' => 'Température', 'value' => '38.2°C'],
                    ['label' => 'Pouls', 'value' => '98 bpm'],
                    ['label' => 'Saturation', 'value' => '99%'],
                    ['label' => 'Poids', 'value' => '24 kg'],
                ],
                'notes' => "Enfant amenée par sa mère pour fièvre depuis 2 jours. Pas de vomissement ni de signe de gravité à l'examen. Suspicion d'infection virale bénigne, surveillance recommandée.",
                'time' => '09:35',
            ],
        ];

        foreach ($consultations as $entry) {
            if (! $entry['doctor']) {
                continue;
            }

            $patient = Patient::where('code', $entry['patient'])->firstOrFail();
            $visit = $patient->visits()->where('orientation', $entry['orientation'])->latest('arrived_at')->first();

            Consultation::firstOrCreate(
                ['patient_id' => $patient->id, 'visit_id' => $visit?->id],
                [
                    'doctor_id' => $entry['doctor']->id,
                    'vitals' => $entry['vitals'],
                    'notes' => $entry['notes'],
                    'status' => Consultation::STATUS_IN_PROGRESS,
                    'started_at' => $today->copy()->setTimeFromTimeString($entry['time']),
                ]
            );
        }

        // Planning de garde du jour, pour que l'indicateur "de garde" ait des
        // données réelles à afficher.
        foreach ([$medecin, $pediatre] as $doctor) {
            if ($doctor) {
                OnCallShift::firstOrCreate(['user_id' => $doctor->id, 'date' => $today->format('Y-m-d')]);
            }
        }

        // Commande de réapprovisionnement en attente, pour illustrer le
        // circuit logistique dès la première connexion.
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
