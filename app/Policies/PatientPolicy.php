<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    /**
     * Determine whether the user can view any patients.
     */
    public function viewAny(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->hasRole('accueil', 'caissier', 'medecin', 'pediatre', 'docteur_general');
    }

    /**
     * Determine whether the user can view the patient.
     */
    public function view(User $user, Patient $patient): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->hasRole('accueil', 'caissier')) {
            return true;
        }

        if ($user->hasRole('medecin', 'pediatre', 'docteur_general')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create patients.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('accueil', 'admin', 'docteur_general');
    }

    /**
     * Determine whether the user can update the patient.
     */
    public function update(User $user, Patient $patient): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->hasRole('accueil')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the patient.
     */
    public function delete(User $user, Patient $patient): bool
    {
        return $user->isAdmin();
    }
}
