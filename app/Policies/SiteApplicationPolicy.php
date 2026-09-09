<?php

namespace App\Policies;

use App\Models\SiteApplication;
use App\Models\User;
use App\Models\Role;

class SiteApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canTask('applications.view');
    }

    public function view(User $user, SiteApplication $application): bool
    {
        if (! $user->canTask('applications.view')) {
            return false;
        }

        if ($user->managesSiteApplications()) {
            return true;
        }

        if ((int) $application->assigned_to !== (int) $user->id) {
            return false;
        }

        if ($user->requiresRegions()) {
            return $user->coversRegion((int) $application->region_id);
        }

        return true;
    }

    public function assign(User $user, SiteApplication $application): bool
    {
        return $user->canTask('applications.assign') && $application->canBeAssigned();
    }

    public function review(User $user, SiteApplication $application): bool
    {
        if (! $user->canTask('applications.review') || ! $application->canBeReviewed()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ((int) $application->assigned_to !== (int) $user->id) {
            return false;
        }

        if ($user->requiresRegions()) {
            return $user->coversRegion((int) $application->region_id);
        }

        return true;
    }

    public function concur(User $user, SiteApplication $application): bool
    {
        if (! $user->canTask('applications.concur') || ! $application->canReceiveDirectorDecision()) {
            return false;
        }

        return $user->isAdmin() || $user->role === Role::KEY_DEPARTMENT_DIRECTOR;
    }

    public function grant(User $user, SiteApplication $application): bool
    {
        if (! $user->canTask('applications.grant') || ! $application->canReceiveDgDecision()) {
            return false;
        }

        return $user->isAdmin() || $user->role === Role::KEY_DIRECTOR_GENERAL;
    }
}
