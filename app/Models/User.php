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
        'region_id',
        'operator_id',
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

    public function isOperatorViewer(): bool
    {
        return $this->role === 'operator_viewer';
    }

    public function hasFullRegionAccess(): bool
    {
        return $this->isAdmin() || $this->isOperationsManager();
    }

    public function canTask(string $task): bool
    {
        return Permissions::roleCan($this->role, $task);
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

        if (! $this->isInspector() || $regionId === null) {
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
        return $this->hasFullRegionAccess() ? 'dashboard' : 'map';
    }

    public function roleLabel(): string
    {
        return __('app.roles.'.$this->role);
    }
}
