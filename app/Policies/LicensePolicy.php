<?php

namespace App\Policies;

use App\Models\License;
use App\Models\User;

class LicensePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, License $license): bool
    {
        return $user->can('view', $license->tower);
    }

    public function create(User $user): bool
    {
        return $user->canWrite();
    }

    public function update(User $user, License $license): bool
    {
        return $user->canWrite() && $user->can('update', $license->tower);
    }

    public function delete(User $user, License $license): bool
    {
        return $user->canTask('licenses.delete');
    }
}
