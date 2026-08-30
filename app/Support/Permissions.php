<?php

namespace App\Support;

use App\Models\Permission;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Permissions
{
    /**
     * Fixed task list — source of truth until Week 20 custom roles UI.
     *
     * @var array<string, string>
     */
    public const TASKS = [
        'audit.view' => 'View audit log',
        'users.manage' => 'Manage users',
        'roles.manage' => 'Manage custom roles',
        'settings.manage' => 'Manage ministry settings',
        'geography.view' => 'View geography master data',
        'geography.manage' => 'Edit districts and sub-districts',
        'towers.view' => 'View towers',
        'towers.create' => 'Register towers',
        'towers.update' => 'Edit towers',
        'towers.delete' => 'Delete towers',
        'towers.approve' => 'Approve tower submissions',
        'inspections.create' => 'Submit inspections',
        'inspections.delete' => 'Delete inspections',
        'inspections.approve' => 'Approve inspection submissions',
        'licenses.create' => 'Create licenses',
        'licenses.update' => 'Edit licenses',
        'licenses.delete' => 'Delete licenses',
        'letters.create' => 'Issue build approval letters',
        'frequencies.create' => 'Create frequency allocations',
        'frequencies.update' => 'Edit frequency allocations',
        'frequencies.delete' => 'Delete frequency allocations',
        'frequencies.renew' => 'Renew frequency allocations',
        'approvals.review' => 'Review pending inspector submissions',
        'reports.view' => 'View reports',
    ];

    /**
     * @var array<string, list<string>>
     */
    public const ROLE_TASKS = [
        'admin' => [
            'audit.view',
            'users.manage',
            'roles.manage',
            'settings.manage',
            'geography.view',
            'geography.manage',
            'towers.view',
            'towers.create',
            'towers.update',
            'towers.delete',
            'towers.approve',
            'inspections.create',
            'inspections.delete',
            'inspections.approve',
            'licenses.create',
            'licenses.update',
            'licenses.delete',
            'letters.create',
            'frequencies.create',
            'frequencies.update',
            'frequencies.delete',
            'frequencies.renew',
            'approvals.review',
            'reports.view',
        ],
        'operations_manager' => [
            'geography.view',
            'geography.manage',
            'towers.view',
            'towers.create',
            'towers.update',
            'towers.approve',
            'inspections.create',
            'inspections.approve',
            'licenses.create',
            'licenses.update',
            'letters.create',
            'frequencies.create',
            'frequencies.update',
            'frequencies.renew',
            'approvals.review',
            'reports.view',
        ],
        'inspector' => [
            'geography.view',
            'towers.view',
            'towers.create',
            'towers.update',
            'inspections.create',
            'licenses.create',
            'licenses.update',
            'letters.create',
            'frequencies.create',
            'frequencies.update',
            'frequencies.renew',
            'reports.view',
        ],
        'operator_viewer' => [
            'towers.view',
            'reports.view',
        ],
    ];

    public static function roleCan(string $role, string $task): bool
    {
        $tasks = self::tasksForRole($role);

        return in_array($task, $tasks, true);
    }

    /**
     * @return list<string>
     */
    public static function tasksForRole(string $role): array
    {
        return Cache::remember("permissions.role.{$role}", 3600, function () use ($role) {
            $fromDatabase = DB::table('role_permission')
                ->join('permissions', 'permissions.id', '=', 'role_permission.permission_id')
                ->where('role_permission.role', $role)
                ->pluck('permissions.key')
                ->all();

            if ($fromDatabase !== []) {
                return array_values($fromDatabase);
            }

            return self::ROLE_TASKS[$role] ?? [];
        });
    }

    public static function syncToDatabase(): void
    {
        foreach (self::TASKS as $key => $label) {
            Permission::query()->updateOrCreate(
                ['key' => $key],
                ['label' => $label],
            );
        }

        $permissionIds = Permission::query()->pluck('id', 'key');

        foreach (self::ROLE_TASKS as $role => $tasks) {
            DB::table('role_permission')->where('role', $role)->delete();

            foreach ($tasks as $task) {
                $permissionId = $permissionIds[$task] ?? null;

                if ($permissionId === null) {
                    continue;
                }

                DB::table('role_permission')->insert([
                    'role' => $role,
                    'permission_id' => $permissionId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            Cache::forget("permissions.role.{$role}");
        }
    }
}
