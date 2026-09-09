<?php

namespace App\Models;

use App\Support\OperatorPalette;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Operator extends Model
{
    protected $fillable = [
        'name',
        'category',
        'color',
        'logo',
        'contact_info',
    ];

    protected static function booted(): void
    {
        static::creating(function (Operator $operator): void {
            if (filled($operator->color)) {
                return;
            }

            $used = static::query()->pluck('color')->all();
            $operator->color = OperatorPalette::colorFor((string) $operator->name, $used);
        });
    }

    public function towers(): HasMany
    {
        return $this->hasMany(Tower::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    public function frequencyAllocations(): HasMany
    {
        return $this->hasMany(FrequencyAllocation::class);
    }

    public function regions(): BelongsToMany
    {
        return $this->belongsToMany(Region::class, 'operator_region')->withTimestamps();
    }

    public function displayName(): string
    {
        return $this->name;
    }

    /**
     * @return array{id: int, name: string, display_name: string, color: string}
     */
    public function toFormOption(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'display_name' => $this->name,
            'color' => $this->color,
        ];
    }
}
