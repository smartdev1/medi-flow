<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Refus motivé de l'enregistrement d'un montant de séjour par l'accueil.
 *
 * Même contrat que PaymentRefusedException : le contrôleur présente le refus à
 * l'utilisateur sous forme de message, sans connaître le type exact levé. Le
 * rollback de la transaction est assuré par l'appelant.
 */
class StayAmountRefusedException extends RuntimeException {}