<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canTask('users.manage');
    }

    public function view(User $user, User $model): bool
    {
        return $user->canTask('users.manage') || $user->is($model);
    }

    public function create(User $user): bool
    {
        return $user->canTask('users.manage');
    }

    public function update(User $user, User $model): bool
    {
        return $user->canTask('users.manage');
    }

    public function delete(User $user, User $model): bool
    {
        if (! $user->canTask('users.manage') || $user->is($model)) {
            return false;
        }

        if ($model->isAdmin() && User::query()->where('role', 'admin')->count() <= 1) {
            return false;
        }

        return true;
    }
}
