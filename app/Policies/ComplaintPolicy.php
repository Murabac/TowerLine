<?php

namespace App\Policies;

use App\Models\Complaint;
use App\Models\Role;
use App\Models\User;

class ComplaintPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canTask('complaints.view');
    }

    public function view(User $user, Complaint $complaint): bool
    {
        if (! $user->canTask('complaints.view')) {
            return false;
        }

        if ($user->canTask('complaints.triage') || $user->isAdmin()) {
            return true;
        }

        if ($user->isOperatorViewer()) {
            if ((int) $complaint->assigned_to === (int) $user->id) {
                return true;
            }

            return $complaint->tower && (int) $complaint->tower->operator_id === (int) $user->operator_id;
        }

        if ($user->requiresRegions()) {
            return $user->coversRegion((int) $complaint->region_id);
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->canTask('complaints.create');
    }

    public function triage(User $user, Complaint $complaint): bool
    {
        return $user->canTask('complaints.triage') && $this->view($user, $complaint);
    }

    public function assign(User $user, Complaint $complaint): bool
    {
        return $this->triage($user, $complaint) && $complaint->isOpen();
    }

    public function respond(User $user, Complaint $complaint): bool
    {
        if (! $this->view($user, $complaint) || ! $complaint->isOpen()) {
            return false;
        }

        if ($user->canTask('complaints.triage')) {
            return true;
        }

        if (! $user->canTask('complaints.respond')) {
            return false;
        }

        if ($user->isOperatorViewer()) {
            return $this->view($user, $complaint);
        }

        if ($user->role === Role::KEY_INSPECTOR) {
            return $user->coversRegion((int) $complaint->region_id);
        }

        return false;
    }

    public function close(User $user, Complaint $complaint): bool
    {
        return $this->triage($user, $complaint);
    }
}
