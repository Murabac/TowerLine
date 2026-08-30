<?php

namespace App\Policies;

use App\Models\Inspection;
use App\Models\Tower;
use App\Models\User;

class InspectionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Inspection $inspection): bool
    {
        return $user->can('view', $inspection->tower);
    }

    public function create(User $user, ?Tower $tower = null): bool
    {
        if (! $user->canWrite()) {
            return false;
        }

        if ($tower) {
            return $user->can('update', $tower);
        }

        return true;
    }

    public function delete(User $user, Inspection $inspection): bool
    {
        return $user->canTask('inspections.delete');
    }
}
