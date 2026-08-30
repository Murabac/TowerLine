<?php

namespace App\Policies;

use App\Models\MinistrySetting;
use App\Models\User;

class MinistrySettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, MinistrySetting $ministrySetting): bool
    {
        return $user->isAdmin();
    }
}
