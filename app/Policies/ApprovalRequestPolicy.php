<?php

namespace App\Policies;

use App\Models\ApprovalRequest;
use App\Models\User;

class ApprovalRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canTask('approvals.review') || $user->isInspector();
    }

    public function view(User $user, ApprovalRequest $approval): bool
    {
        return $user->canTask('approvals.review') || $approval->isOwnedBy($user);
    }

    public function update(User $user, ApprovalRequest $approval): bool
    {
        return $approval->isPending() && $approval->isOwnedBy($user);
    }

    public function review(User $user, ApprovalRequest $approval): bool
    {
        return $user->canTask('approvals.review') && $approval->isPending();
    }
}
