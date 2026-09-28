<?php

namespace App\Enums;

enum Role: string
{
    case Accueil = 'accueil';
    case Caissier = 'caissier';
    case Medecin = 'medecin';
    case Pediatre = 'pediatre';
    case Laborantin = 'laborantin';
    case Pharmacien = 'pharmacien';
    case Logistique = 'logistique';
    case AideSoignante = 'aide_soignante';
    case DocteurGeneral = 'docteur_general';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Accueil => "Agent d'accueil",
            self::Caissier => 'Caissier',
            self::Medecin => 'Médecin',
            self::Pediatre => 'Pédiatre',
            self::Laborantin => 'Technicien de laboratoire',
            self::Pharmacien => 'Pharmacien',
            self::Logistique => 'Agent de logistique',
            self::AideSoignante => 'Aide-soignante',
            self::DocteurGeneral => 'Docteur Général',
            self::Admin => 'Administrateur du système',
        };
    }

    public function homeRouteName(): string
    {
        return match ($this) {
            self::Accueil, self::Admin => 'accueil.index',
            self::Caissier => 'caisse.index',
            self::Medecin, self::Pediatre => 'medecin.index',
            self::Laborantin => 'laboratoire.index',
            self::Pharmacien => 'pharmacie.index',
            self::Logistique => 'logistique.index',
            self::DocteurGeneral => 'docteurgeneral.index',
            self::AideSoignante => 'attente.index',
        };
    }
}
