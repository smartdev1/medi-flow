<?php

namespace App\Enums;

enum Role: string
{
    case Accueil = 'accueil';
    case Caissier = 'caissier';
    case Medecin = 'medecin';
    case Pediatre = 'pediatre';
    case Chirurgien = 'chirurgien';
    case Anesthesiste = 'anesthesiste';
    case Cardiologue = 'cardiologue';
    case Dermatologue = 'dermatologue';
    case Orl = 'orl';
    case Ophtalmo = 'ophtalmo';
    case Dentiste = 'dentiste';
    case Laborantin = 'laborantin';
    case Pharmacien = 'pharmacien';
    case AideSoignante = 'aide_soignante';
    case Logistique = 'logistique';
    case DocteurGeneral = 'docteur_general';
    case Raf = 'raf';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Accueil => "Agent d'accueil",
            self::Caissier => 'Caissier',
            self::Medecin => 'Médecin',
            self::Pediatre => 'Pédiatre',
            self::Chirurgien => 'Chirurgien',
            self::Anesthesiste => 'Anesthésiste',
            self::Cardiologue => 'Cardiologue',
            self::Dermatologue => 'Dermatologue',
            self::Orl => 'ORL',
            self::Ophtalmo => 'Ophtalmologue',
            self::Dentiste => 'Dentiste',
            self::Laborantin => 'Technicien de laboratoire',
            self::Pharmacien => 'Pharmacien',
            self::AideSoignante => 'Aide-soignante',
            self::Logistique => 'Agent de logistique',
            self::DocteurGeneral => 'Docteur Général',
            self::Raf => "Responsable d'Activité Financière",
            self::Admin => 'Administrateur du système',
        };
    }

    public function homeRouteName(): string
    {
        return match ($this) {
            self::Accueil, self::Admin => 'accueil.index',
            self::Caissier => 'caisse.index',
            self::Medecin, self::Pediatre, self::Chirurgien, self::Anesthesiste,
            self::Cardiologue, self::Dermatologue, self::Orl, self::Ophtalmo,
            self::Dentiste => 'medecin.index',
            self::Laborantin => 'laboratoire.index',
            self::Pharmacien => 'pharmacie.index',
            self::Logistique => 'logistique.index',
            self::DocteurGeneral => 'docteurgeneral.index',
            self::Raf => 'caisse.raf.dashboard',
            self::AideSoignante => 'attente.index',
        };
    }
}
