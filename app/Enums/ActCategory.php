<?php

namespace App\Enums;

enum ActCategory: string
{
    case Consultation = 'consultation';
    case Laboratoire = 'laboratoire';
    case Imagerie = 'imagerie';
    case Pharmacie = 'pharmacie';
    case Chirurgie = 'chirurgie';
    case Maternite = 'maternite';
    case Hospitalisation = 'hospitalisation';
    case Bloc = 'bloc';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::Consultation => 'Consultation',
            self::Laboratoire => 'Laboratoire',
            self::Imagerie => 'Imagerie médicale',
            self::Pharmacie => 'Pharmacie',
            self::Chirurgie => 'Actes techniques & chirurgie',
            self::Maternite => 'Maternité & gynéco-obstétrique',
            self::Hospitalisation => 'Hospitalisation',
            self::Bloc => 'Bloc opératoire',
            self::Autre => 'Autres actes',
        };
    }
}
