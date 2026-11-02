<?php

namespace App\Policies;

use App\Models\Admission;
use App\Models\User;
use App\Enums\Role;

class AdmissionPolicy
{
    public function validateDepiquage(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->hasRole(Role::AideSoignante);
    }

    public function libererChambre(User $user, Admission $admission): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->hasRole(Role::AideSoignante);
    }
}
