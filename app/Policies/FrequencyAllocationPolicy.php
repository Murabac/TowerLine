<?php

namespace App\Policies;

use App\Models\FrequencyAllocation;
use App\Models\User;

class FrequencyAllocationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, FrequencyAllocation $allocation): bool
    {
        return FrequencyAllocation::query()
            ->visibleTo($user)
            ->whereKey($allocation->id)
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->canWrite();
    }

    public function update(User $user, FrequencyAllocation $allocation): bool
    {
        return $user->canWrite() && $this->view($user, $allocation);
    }

    public function delete(User $user, FrequencyAllocation $allocation): bool
    {
        return $user->canTask('frequencies.delete') && $this->view($user, $allocation);
    }

    public function renew(User $user, FrequencyAllocation $allocation): bool
    {
        return $this->update($user, $allocation);
    }
}
