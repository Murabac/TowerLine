<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Region;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Replaces the demo logins with the real accounts listed in a plan file.
 *
 * - Accounts in the plan are converted from an existing user ("match") or created,
 *   and get a new random password that is shown once.
 * - Every other user is deactivated if anything in the database points at them
 *   (audit trail, letters, applications...), otherwise deleted.
 * - Custom roles that nobody uses any more are deleted. Built-in roles stay.
 * - Each change is written to the audit log as "System".
 *
 * Without --force nothing is written (dry run).
 */
class CleanUsers extends Command
{
    protected $signature = 'towers:clean-users
        {--plan=storage/app/private/user-cleanup.json : JSON file listing the accounts to keep}
        {--dry-run : Show what would change without writing anything (default)}
        {--force : Apply the changes}';

    protected $description = 'Replace demo logins with the real staff accounts from a plan file';

    /** Columns that point at users.id. Deleting a user linked here would cascade or blank the record. */
    private const LINKS = [
        'audit_logs' => ['user_id'],
        'approval_requests' => ['submitted_by', 'reviewed_by'],
        'build_approval_letters' => ['issued_by'],
        'frequency_allocation_letters' => ['issued_by'],
        'frequency_renewal_receipts' => ['issued_by'],
        'inspections' => ['inspector_id'],
        'site_applications' => ['assigned_to', 'assigned_by', 'site_visited_by', 'officer_reviewed_by', 'director_reviewed_by', 'dg_reviewed_by'],
        'complaints' => ['created_by', 'assigned_to'],
        'complaint_updates' => ['user_id'],
    ];

    public function handle(): int
    {
        if ($this->option('force') && $this->option('dry-run')) {
            $this->error('Use either --dry-run or --force, not both.');

            return self::FAILURE;
        }

        $apply = (bool) $this->option('force');

        try {
            $plan = $this->loadPlan((string) $this->option('plan'));
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $keepIds = collect($plan)->pluck('user')->filter()->map->id->all();
        $others = User::query()->whereNotIn('id', $keepIds)->orderBy('id')->get();
        $links = $others->mapWithKeys(fn (User $user) => [$user->id => $this->linkCounts($user->id)]);

        $this->showPlan($plan, $others, $links);

        $rolesToDelete = $this->unusedCustomRoles($plan, $others, $links);
        $this->showRoles($rolesToDelete);

        if (! $apply) {
            $this->newLine();
            $this->warn('Dry run: nothing was changed. Run again with --force to apply.');

            return self::SUCCESS;
        }

        try {
            $passwords = $this->apply($plan, $others, $links, $rolesToDelete);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Done. Give each person their password privately; it is not stored anywhere else.');
        $this->table(['Email', 'New password'], collect($passwords)->map(fn ($password, $email) => [$email, $password])->values());

        return self::SUCCESS;
    }

    /**
     * Everything in one transaction: if any step fails, nothing changes.
     *
     * @return array<string, string> email => new password
     */
    private function apply(array $plan, $others, $links, $rolesToDelete): array
    {
        return DB::transaction(function () use ($plan, $others, $links, $rolesToDelete) {
            $passwords = [];

            // 1. Real accounts first, so there is always a working admin before any demo login goes.
            foreach ($plan as $entry) {
                $passwords[$entry['email']] = $this->applyAccount($entry);
            }

            $this->assertRealAdminExists();

            // 2. Demo / unused accounts.
            foreach ($others as $user) {
                $links[$user->id] === []
                    ? $this->deleteUser($user)
                    : $this->deactivateUser($user, $links[$user->id]);
            }

            // 3. Custom roles nobody uses.
            foreach ($rolesToDelete as $role) {
                $this->deleteRole($role);
            }

            return $passwords;
        });
    }

    /**
     * @return list<array{name: string, email: string, role: Role, region_ids: list<int>, user: ?User, match: ?string}>
     */
    private function loadPlan(string $path): array
    {
        $file = str_starts_with($path, '/') ? $path : base_path($path);

        if (! is_file($file)) {
            throw new RuntimeException("Plan file not found: {$file}");
        }

        $json = json_decode((string) file_get_contents($file), true);

        if (! is_array($json) || ! isset($json['accounts']) || ! is_array($json['accounts']) || $json['accounts'] === []) {
            throw new RuntimeException('The plan file needs an "accounts" list.');
        }

        $plan = [];
        $emails = [];

        foreach ($json['accounts'] as $i => $row) {
            $label = 'accounts['.$i.']';
            $name = trim((string) ($row['name'] ?? ''));
            $email = Str::lower(trim((string) ($row['email'] ?? '')));
            $match = isset($row['match']) && $row['match'] !== null ? Str::lower(trim((string) $row['match'])) : null;

            if ($name === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException("{$label}: a name and a valid email are required.");
            }

            if (str_ends_with($email, '.local')) {
                throw new RuntimeException("{$label}: {$email} is a demo address. Use the person's real email.");
            }

            if (in_array($email, $emails, true)) {
                throw new RuntimeException("{$label}: {$email} is listed twice.");
            }
            $emails[] = $email;

            $role = Role::query()->where('key', (string) ($row['role'] ?? ''))->first();

            if (! $role) {
                throw new RuntimeException("{$label}: unknown role \"".($row['role'] ?? '')."\".");
            }

            $user = null;

            if ($match !== null) {
                $user = User::query()->where('email', $match)->first();

                if (! $user) {
                    throw new RuntimeException("{$label}: no user with email {$match} to convert.");
                }
            }

            $taken = User::query()->where('email', $email)->when($user, fn ($q) => $q->whereKeyNot($user->id))->exists();

            if ($taken) {
                throw new RuntimeException("{$label}: {$email} already belongs to another user.");
            }

            $regionIds = [];

            foreach ((array) ($row['regions'] ?? []) as $regionName) {
                $region = Region::query()->where('name_en', $regionName)->first();

                if (! $region) {
                    throw new RuntimeException("{$label}: unknown region \"{$regionName}\".");
                }

                $regionIds[] = (int) $region->id;
            }

            if ($role->requires_regions && $regionIds === []) {
                throw new RuntimeException("{$label}: the {$role->key} role needs at least one region.");
            }

            $plan[] = compact('name', 'email', 'role', 'match', 'user') + ['region_ids' => $regionIds];
        }

        if (collect($plan)->pluck('user')->filter()->map->id->duplicates()->isNotEmpty()) {
            throw new RuntimeException('Two plan entries convert the same existing user.');
        }

        if (! collect($plan)->contains(fn ($entry) => $entry['role']->key === Role::KEY_ADMIN)) {
            throw new RuntimeException('The plan must keep at least one admin, or nobody can manage users.');
        }

        return $plan;
    }

    /**
     * @return array<string, int> table.column => count, only non-zero
     */
    private function linkCounts(int $userId): array
    {
        $counts = [];

        foreach (self::LINKS as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                $count = DB::table($table)->where($column, $userId)->count();

                if ($count > 0) {
                    $counts[$table.'.'.$column] = $count;
                }
            }
        }

        return $counts;
    }

    private function showPlan(array $plan, $others, $links): void
    {
        $rows = [];

        foreach ($plan as $entry) {
            $rows[] = [
                $entry['user'] ? 'convert #'.$entry['user']->id : 'create',
                $entry['user']?->email ?? '—',
                $entry['email'],
                $entry['name'],
                $entry['role']->key,
                $entry['region_ids'] ? Region::query()->whereKey($entry['region_ids'])->pluck('name_en')->join(', ') : '—',
            ];
        }

        $this->info('Accounts to keep (new random password each):');
        $this->table(['Action', 'From', 'Email', 'Name', 'Role', 'Regions'], $rows);

        $rows = $others->map(fn (User $user) => [
            $links[$user->id] === [] ? 'delete' : 'deactivate',
            '#'.$user->id,
            $user->email,
            $user->role,
            $links[$user->id] === [] ? 'none' : collect($links[$user->id])->map(fn ($n, $k) => "{$k}:{$n}")->join(', '),
        ]);

        $this->info('Other users:');
        $this->table(['Action', 'ID', 'Email', 'Role', 'Linked records'], $rows);
    }

    /**
     * @return \Illuminate\Support\Collection<int, Role>
     */
    private function unusedCustomRoles(array $plan, $others, $links)
    {
        $remainingKeys = collect($plan)->map(fn ($entry) => $entry['role']->key)
            ->merge($others->filter(fn (User $user) => $links[$user->id] !== [])->pluck('role'))
            ->unique();

        return Role::query()
            ->where('is_system', false)
            ->whereNotIn('key', $remainingKeys)
            ->orderBy('id')
            ->get();
    }

    private function showRoles($rolesToDelete): void
    {
        $this->info('Custom roles to delete (built-in roles are always kept):');
        $this->table(['ID', 'Key', 'Name'], $rolesToDelete->map(fn (Role $role) => [$role->id, $role->key, $role->name]));
    }

    private function applyAccount(array $entry): string
    {
        $password = Str::password(20, symbols: false);
        $user = $entry['user'] ?? new User;
        $before = $user->exists ? $user->only(['name', 'email', 'role']) : null;

        $user->forceFill([
            'name' => $entry['name'],
            'email' => $entry['email'],
            'password' => $password,
            'role' => $entry['role']->key,
            'role_id' => $entry['role']->id,
            'operator_id' => null,
            'deactivated_at' => null,
            'remember_token' => Str::random(60),
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        $user->syncInspectorRegions($entry['region_ids']);
        $this->forgetSessions($user->id);

        $after = $user->only(['name', 'email', 'role']);

        $before === null
            ? $this->audit('created', $user, $after + ['source' => 'towers:clean-users'])
            : $this->audit('updated', $user, ['name' => $after['name'], 'before' => $before, 'after' => $after, 'source' => 'towers:clean-users']);

        return $password;
    }

    private function assertRealAdminExists(): void
    {
        $exists = User::query()
            ->active()
            ->where('role', Role::KEY_ADMIN)
            ->where('email', 'not like', '%.local')
            ->exists();

        if (! $exists) {
            throw new RuntimeException('No active admin with a real email would remain. Nothing was changed.');
        }
    }

    private function deactivateUser(User $user, array $links): void
    {
        $user->forceFill([
            'deactivated_at' => now(),
            'password' => Str::password(40),
            'remember_token' => Str::random(60),
        ])->save();

        $this->forgetSessions($user->id);
        $this->audit('deactivated', $user, [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'linked_records' => array_sum($links),
            'source' => 'towers:clean-users',
        ]);
    }

    private function deleteUser(User $user): void
    {
        $details = $user->only(['name', 'email', 'role']) + ['source' => 'towers:clean-users'];

        $this->forgetSessions($user->id);

        if (Schema::hasTable('notifications')) {
            DB::table('notifications')
                ->where('notifiable_type', $user->getMorphClass())
                ->where('notifiable_id', $user->id)
                ->delete();
        }

        $user->delete();
        $this->audit('deleted', $user, $details);
    }

    private function deleteRole(Role $role): void
    {
        $details = ['name' => $role->name, 'key' => $role->key, 'source' => 'towers:clean-users'];

        DB::table('role_permission')->where('role', $role->key)->delete();
        User::query()->where('role_id', $role->id)->update(['role_id' => null]);
        $role->delete();

        $this->audit('deleted', $role, $details);
    }

    private function forgetSessions(int $userId): void
    {
        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $userId)->delete();
        }
    }

    private function audit(string $action, $model, array $changes): void
    {
        AuditLog::query()->create([
            'user_id' => null, // shown as "System"
            'action' => $action,
            'model_type' => $model::class,
            'model_id' => $model->getKey(),
            'changes' => $changes,
            'created_at' => now(),
        ]);
    }
}
