<?php

namespace App\Policies;

use App\Models\District;
use App\Models\User;

class DistrictPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isInspector();
    }

    public function view(User $user, District $district): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInspector()) {
            return in_array($district->region_id, $user->regionIds(), true);
        }

        return false;
    }

    public function update(User $user, District $district): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, District $district): bool
    {
        return $user->isAdmin();
    }
}
