<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    /**
     * Determine whether the user can view any payments.
     */
    public function viewAny(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->hasRole('caissier', 'medecin', 'pediatre', 'docteur_general');
    }

    /**
     * Determine whether the user can view the payment.
     */
    public function view(User $user, Payment $payment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->hasRole('docteur_general')) {
            return true;
        }

        if ($user->hasRole('caissier')) {
            return $payment->cashier_id === $user->id;
        }

        if ($user->hasRole('medecin', 'pediatre')) {
            return $payment->patient->visits()
                ->where('registered_by', $user->id)
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can create payments.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('caissier', 'admin');
    }

    /**
     * Determine whether the user can update the payment.
     */
    public function update(User $user, Payment $payment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->hasRole('caissier')) {
            return $payment->cashier_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can mark the payment as paid.
     */
    public function markAsPaid(User $user, Payment $payment): bool
    {
        return $this->update($user, $payment);
    }

    /**
     * Determine whether the user can delete the payment.
     */
    public function delete(User $user, Payment $payment): bool
    {
        return $user->isAdmin();
    }
}
