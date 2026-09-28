<?php

namespace App\Policies;

use App\Models\CashierSession;
use App\Models\User;
use App\Enums\Role;

class CashierSessionPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->hasRole(Role::DocteurGeneral)) {
            return true;
        }

        return $user->hasRole(Role::Caissier);
    }

    public function view(User $user, CashierSession $cashierSession): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->hasRole(Role::DocteurGeneral)) {
            return true;
        }

        if ($user->hasRole(Role::Caissier)) {
            return $cashierSession->cashier_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::Caissier);
    }

    public function update(User $user, CashierSession $cashierSession): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->hasRole(Role::Caissier)
            && $cashierSession->cashier_id === $user->id
            && $cashierSession->isActive();
    }

    public function delete(User $user): bool
    {
        return $user->isAdmin();
    }
}
