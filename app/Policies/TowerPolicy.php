<?php

namespace App\Policies;

use App\Models\Tower;
use App\Models\User;

class TowerPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Tower $tower): bool
    {
        return $this->inScope($user, $tower);
    }

    public function create(User $user): bool
    {
        return $user->canWrite();
    }

    public function update(User $user, Tower $tower): bool
    {
        return $user->canWrite() && $this->inScope($user, $tower);
    }

    public function delete(User $user, Tower $tower): bool
    {
        return $user->canTask('towers.delete') && $this->inScope($user, $tower);
    }

    private function inScope(User $user, Tower $tower): bool
    {
        if ($user->hasFullRegionAccess()) {
            return true;
        }

        if ($user->isInspector()) {
            return $user->coversRegion((int) $tower->region_id);
        }

        if ($user->isOperatorViewer()) {
            return (int) $user->operator_id === (int) $tower->operator_id;
        }

        return false;
    }
}
