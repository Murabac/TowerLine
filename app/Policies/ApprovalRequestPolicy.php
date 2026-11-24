<?php

namespace App\Policies;

use App\Models\ApprovalRequest;
use App\Models\User;

class ApprovalRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAnyTask('approvals.review', 'towers.register_existing', 'towers.confirm_existing')
            || $user->isInspector();
    }

    public function view(User $user, ApprovalRequest $approval): bool
    {
        return $approval->isReviewableBy($user) || $approval->isOwnedBy($user);
    }

    public function update(User $user, ApprovalRequest $approval): bool
    {
        if (! $approval->isPending()) {
            return false;
        }

        // The Director may correct an existing-tower registration before confirming it.
        return $approval->isOwnedBy($user)
            || ($approval->isExistingTower() && $approval->isReviewableBy($user));
    }

    public function review(User $user, ApprovalRequest $approval): bool
    {
        return $approval->isPending() && $approval->isReviewableBy($user);
    }
}
