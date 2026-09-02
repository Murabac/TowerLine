<?php

namespace App\Support;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Permissions
{
    /**
     * Fixed task list — labels are English defaults; UI uses lang keys when present.
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
        'map.view' => 'Open the tower map',
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
        'reports.print' => 'Print reports',
        'reports.export_excel' => 'Export reports to Excel',
        'reports.audit' => 'Export the audit log report',
    ];

    /**
     * @var array<string, list<string>>
     */
    public const TASK_GROUPS = [
        'towers' => ['towers.view', 'towers.create', 'towers.update', 'towers.delete', 'towers.approve'],
        'inspections' => ['inspections.create', 'inspections.delete', 'inspections.approve'],
        'approvals' => ['approvals.review'],
        'map' => ['map.view'],
        'letters' => ['letters.create'],
        'licenses' => ['licenses.create', 'licenses.update', 'licenses.delete'],
        'frequencies' => ['frequencies.create', 'frequencies.update', 'frequencies.delete', 'frequencies.renew'],
        'reports' => ['reports.view', 'reports.print', 'reports.export_excel', 'reports.audit'],
        'users' => ['users.manage', 'roles.manage'],
        'geography' => ['geography.view', 'geography.manage'],
        'audit' => ['audit.view'],
        'settings' => ['settings.manage'],
    ];

    /**
     * @var array<string, array{name: string, requires_regions: bool}>
     */
    public const SYSTEM_ROLES = [
        'admin' => ['name' => 'Ministry admin', 'requires_regions' => false],
        'operations_manager' => ['name' => 'Operations manager', 'requires_regions' => false],
        'inspector' => ['name' => 'Regional inspector', 'requires_regions' => true],
        'operator_viewer' => ['name' => 'Operator viewer', 'requires_regions' => false],
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
            'map.view',
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
            'reports.print',
            'reports.export_excel',
            'reports.audit',
        ],
        'operations_manager' => [
            'geography.view',
            'geography.manage',
            'map.view',
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
            'reports.print',
            'reports.export_excel',
        ],
        'inspector' => [
            'geography.view',
            'map.view',
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
            'reports.print',
        ],
        'operator_viewer' => [
            'map.view',
            'towers.view',
            'reports.view',
            'reports.print',
        ],
    ];

    public static function roleCan(string $role, string $task): bool
    {
        return in_array($task, self::tasksForRole($role), true);
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

            // After permissions have been seeded, an empty pivot means this role
            // was saved with no tasks — do not fall back to the code defaults.
            if (DB::table('role_permission')->exists()) {
                return [];
            }

            return self::ROLE_TASKS[$role] ?? [];
        });
    }

    /**
     * @param  list<string>  $tasks
     */
    public static function syncRoleTasks(string $role, array $tasks): void
    {
        $permissionIds = Permission::query()->pluck('id', 'key');

        DB::table('role_permission')->where('role', $role)->delete();

        foreach (array_unique($tasks) as $task) {
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

        self::forgetRole($role);
    }

    public static function forgetRole(string $role): void
    {
        Cache::forget("permissions.role.{$role}");
    }

    public static function syncToDatabase(): void
    {
        foreach (self::TASKS as $key => $label) {
            Permission::query()->updateOrCreate(
                ['key' => $key],
                ['label' => $label],
            );
        }

        foreach (self::SYSTEM_ROLES as $key => $meta) {
            Role::query()->updateOrCreate(
                ['key' => $key],
                [
                    'name' => $meta['name'],
                    'is_system' => true,
                    'requires_regions' => $meta['requires_regions'],
                ],
            );
        }

        foreach (self::ROLE_TASKS as $role => $tasks) {
            self::syncRoleTasks($role, $tasks);
        }

        $roleIds = Role::query()->pluck('id', 'key');

        foreach ($roleIds as $key => $id) {
            DB::table('users')->where('role', $key)->where(function ($query) use ($id) {
                $query->whereNull('role_id')->orWhere('role_id', '!=', $id);
            })->update(['role_id' => $id]);
        }
    }
}
