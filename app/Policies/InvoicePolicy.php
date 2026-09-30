<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    /**
     * Determine whether the user can view any invoices.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the invoice.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->hasRole('caissier')) {
            return true;
        }

        if ($user->hasRole('medecin', 'pediatre', 'docteur_general')) {
            return $invoice->patient->visits()
                ->where('registered_by', $user->id)
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can create invoices.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('accueil', 'caissier', 'admin');
    }

    /**
     * Determine whether the user can update the invoice.
     *
     * La caisse encaisse : sans ce droit, un caissier ne peut pas solder une
     * facture. La clôture du dossier reste, elle, protégée ailleurs.
     */
    public function update(User $user, Invoice $invoice): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->hasRole('caissier', 'docteur_general');
    }

    /**
     * Determine whether the user can delete the invoice.
     */
    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->isAdmin();
    }
}
