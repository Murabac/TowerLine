<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    public function isNational(): bool
    {
        if ($this->relationLoaded('regions')) {
            return $this->regions->isEmpty();
        }

        return ! $this->regions()->exists();
    }

    public function servesRegion(?int $regionId): bool
    {
        if ($regionId === null) {
            return true;
        }

        if ($this->isNational()) {
            return true;
        }

        if ($this->relationLoaded('regions')) {
            return $this->regions->contains('id', $regionId);
        }

        return $this->regions()->whereKey($regionId)->exists();
    }

    public function displayName(): string
    {
        if ($this->isNational()) {
            return $this->name;
        }

        $regions = $this->relationLoaded('regions')
            ? $this->regions
            : $this->regions()->orderBy('name_en')->get();

        $scope = $regions->map(fn (Region $region) => $region->localizedName())->join(', ');

        return "{$this->name} ({$scope})";
    }

    /**
     * @return array{id: int, name: string, display_name: string, national: bool, region_ids: list<int>}
     */
    public function toFormOption(): array
    {
        $regionIds = $this->relationLoaded('regions')
            ? $this->regions->pluck('id')->map(fn ($id) => (int) $id)->all()
            : $this->regions()->pluck('regions.id')->map(fn ($id) => (int) $id)->all();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'display_name' => $this->displayName(),
            'national' => $this->isNational(),
            'region_ids' => $regionIds,
        ];
    }

    public function scopeServingRegion(Builder $query, ?int $regionId): Builder
    {
        if ($regionId === null) {
            return $query;
        }

        return $query->where(function (Builder $operators) use ($regionId): void {
            $operators
                ->whereDoesntHave('regions')
                ->orWhereHas('regions', fn (Builder $regions) => $regions->whereKey($regionId));
        });
    }

    public static function optionsForRegion(?int $regionId)
    {
        return static::query()
            ->with('regions')
            ->servingRegion($regionId)
            ->orderBy('name')
            ->get();
    }
}
