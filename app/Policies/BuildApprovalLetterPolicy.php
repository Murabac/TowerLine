<?php

namespace App\Policies;

use App\Models\BuildApprovalLetter;
use App\Models\Tower;
use App\Models\User;

class BuildApprovalLetterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canWrite();
    }

    public function view(User $user, BuildApprovalLetter $letter): bool
    {
        return $user->can('view', $letter->tower);
    }

    public function create(User $user, Tower $tower): bool
    {
        return $user->canWrite() && $user->can('update', $tower);
    }
}
