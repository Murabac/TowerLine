<?php

namespace App\Models;

use App\Support\Permissions;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'role_id',
        'region_id',
        'operator_id',
        'signature_path',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function regions(): BelongsToMany
    {
        return $this->belongsToMany(Region::class)->withTimestamps();
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    public function roleRecord(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class, 'inspector_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isOperationsManager(): bool
    {
        return $this->role === 'operations_manager';
    }

    public function isInspector(): bool
    {
        return $this->role === 'inspector';
    }

    public function hasSavedSignature(): bool
    {
        return \App\Support\UserSignature::exists($this->signature_path);
    }

    public function isOperatorViewer(): bool
    {
        return $this->role === Role::KEY_OPERATOR_VIEWER;
    }

    public function managesSiteApplications(): bool
    {
        return $this->canTask('applications.assign')
            || in_array($this->role, [
                Role::KEY_ADMIN,
                Role::KEY_SECTION_HEAD,
                Role::KEY_DEPARTMENT_DIRECTOR,
                Role::KEY_DIRECTOR_GENERAL,
            ], true);
    }

    public function hasFullRegionAccess(): bool
    {
        return ! $this->requiresRegions() && ! $this->isOperatorViewer();
    }

    public function requiresRegions(): bool
    {
        return (bool) ($this->roleRecord?->requires_regions ?? $this->isInspector());
    }

    public function canTask(string $task): bool
    {
        return Permissions::roleCan($this->role, $task);
    }

    public function canAnyTask(string ...$tasks): bool
    {
        foreach ($tasks as $task) {
            if ($this->canTask($task)) {
                return true;
            }
        }

        return false;
    }

    public function canAccessGroup(string $group): bool
    {
        $tasks = Permissions::TASK_GROUPS[$group] ?? [];

        return $tasks !== [] && $this->canAnyTask(...$tasks);
    }

    /**
     * @return list<int>
     */
    public function regionIds(): array
    {
        return $this->regions->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    public function coversRegion(?int $regionId): bool
    {
        if ($this->hasFullRegionAccess()) {
            return true;
        }

        if (! $this->requiresRegions() || $regionId === null) {
            return false;
        }

        return in_array($regionId, $this->regionIds(), true);
    }

    public function syncInspectorRegions(array $regionIds): void
    {
        $ids = collect($regionIds)->filter()->map(fn ($id) => (int) $id)->unique()->values();

        $this->regions()->sync($ids->all());
        $this->forceFill(['region_id' => $ids->first()])->saveQuietly();
        $this->unsetRelation('regions');
    }

    public function canWrite(): bool
    {
        return $this->canTask('towers.create')
            || $this->canTask('towers.update')
            || $this->canTask('inspections.create')
            || $this->canTask('licenses.create')
            || $this->canTask('licenses.update')
            || $this->canTask('frequencies.create')
            || $this->canTask('frequencies.update')
            || $this->canTask('frequencies.renew');
    }

    public function publishesDirectly(): bool
    {
        return $this->canTask('approvals.review');
    }

    public function homeRouteName(): string
    {
        if (in_array($this->role, [
            Role::KEY_SECTION_HEAD,
            Role::KEY_ASSIGNED_OFFICER,
            Role::KEY_REGIONAL_COORDINATOR,
            Role::KEY_DEPARTMENT_DIRECTOR,
            Role::KEY_DIRECTOR_GENERAL,
        ], true)) {
            return 'applications.index';
        }

        return $this->hasFullRegionAccess() ? 'dashboard' : 'map';
    }

    public function assignRole(Role|string $role): void
    {
        $record = $role instanceof Role
            ? $role
            : Role::query()->where('key', $role)->firstOrFail();

        $this->forceFill([
            'role' => $record->key,
            'role_id' => $record->id,
        ])->save();

        $this->setRelation('roleRecord', $record);
    }

    public function roleLabel(): string
    {
        $key = 'app.roles.'.$this->role;

        if (trans()->has($key)) {
            return __($key);
        }

        return $this->roleRecord?->name ?? $this->role;
    }
}
