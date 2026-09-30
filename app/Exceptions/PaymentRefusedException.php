<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Refus motivé d'un encaissement.
 *
 * Étend RuntimeException pour que les contrôleurs, qui présentent le refus à
 * l'utilisateur sous forme de message, n'aient pas à connaître le type exact
 * levé par le modèle. Distincte d'une erreur technique : ici, l'encaissement
 * est volontairement rejeté et aucune écriture n'a eu lieu.
 */
class PaymentRefusedException extends RuntimeException {}
