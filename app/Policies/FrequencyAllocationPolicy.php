<?php

namespace App\Policies;

use App\Models\FrequencyAllocation;
use App\Models\User;

class FrequencyAllocationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessGroup('frequencies');
    }

    public function view(User $user, FrequencyAllocation $allocation): bool
    {
        if (! $user->canAccessGroup('frequencies')) {
            return false;
        }

        return FrequencyAllocation::query()
            ->visibleTo($user)
            ->whereKey($allocation->id)
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->canTask('frequencies.create');
    }

    public function update(User $user, FrequencyAllocation $allocation): bool
    {
        return $user->canTask('frequencies.update') && $this->view($user, $allocation);
    }

    public function delete(User $user, FrequencyAllocation $allocation): bool
    {
        return $user->canTask('frequencies.delete') && $this->view($user, $allocation);
    }

    public function renew(User $user, FrequencyAllocation $allocation): bool
    {
        return $user->canTask('frequencies.renew') && $this->view($user, $allocation);
    }
}
