<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'region_id', 'operator_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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

    public function isInspector(): bool
    {
        return $this->role === 'inspector';
    }

    public function isOperatorViewer(): bool
    {
        return $this->role === 'operator_viewer';
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
        if ($this->isAdmin()) {
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
        return $this->isAdmin() || $this->isInspector();
    }

    public function homeRouteName(): string
    {
        return $this->isAdmin() ? 'dashboard' : 'map';
    }

    public function roleLabel(): string
    {
        return __('app.roles.'.$this->role);
    }
}
