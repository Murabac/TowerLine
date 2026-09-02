<?php

namespace App\Models;

use App\Support\Permissions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    public const KEY_ADMIN = 'admin';

    public const KEY_OPERATIONS_MANAGER = 'operations_manager';

    public const KEY_INSPECTOR = 'inspector';

    public const KEY_OPERATOR_VIEWER = 'operator_viewer';

    protected $fillable = [
        'key',
        'name',
        'is_system',
        'requires_regions',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'requires_regions' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function scopeAssignable(Builder $query): Builder
    {
        return $query->where('key', '!=', self::KEY_OPERATOR_VIEWER);
    }

    public function isSystem(): bool
    {
        return $this->is_system;
    }

    public function isAssignable(): bool
    {
        return $this->key !== self::KEY_OPERATOR_VIEWER;
    }

    public function displayName(): string
    {
        $key = 'app.roles.'.$this->key;

        return trans()->has($key) ? __($key) : $this->name;
    }

    /**
     * @return list<string>
     */
    public function taskKeys(): array
    {
        return Permissions::tasksForRole($this->key);
    }

    public function taskCount(): int
    {
        return count($this->taskKeys());
    }
}
