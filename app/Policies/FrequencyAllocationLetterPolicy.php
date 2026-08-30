<?php

namespace App\Policies;

use App\Models\FrequencyAllocation;
use App\Models\FrequencyAllocationLetter;
use App\Models\User;

class FrequencyAllocationLetterPolicy
{
    public function view(User $user, FrequencyAllocationLetter $letter): bool
    {
        return $user->can('view', $letter->allocation);
    }

    public function create(User $user, FrequencyAllocation $allocation): bool
    {
        return $user->can('update', $allocation);
    }
}
