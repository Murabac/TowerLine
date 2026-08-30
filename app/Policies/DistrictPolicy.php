<?php

namespace App\Policies;

use App\Models\District;
use App\Models\User;

class DistrictPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canTask('geography.view');
    }

    public function view(User $user, District $district): bool
    {
        if ($user->hasFullRegionAccess()) {
            return true;
        }

        if ($user->isInspector()) {
            return in_array($district->region_id, $user->regionIds(), true);
        }

        return false;
    }

    public function update(User $user, District $district): bool
    {
        return $user->canTask('geography.manage');
    }

    public function delete(User $user, District $district): bool
    {
        return $user->canTask('geography.manage');
    }
}
