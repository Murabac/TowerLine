<?php

namespace App\Models;

use App\Support\GeographyReference;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubDistrict extends Model
{
    protected $fillable = [
        'district_id',
        'name',
        'latitude',
        'longitude',
        'bounds_south',
        'bounds_west',
        'bounds_north',
        'bounds_east',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'bounds_south' => 'float',
            'bounds_west' => 'float',
            'bounds_north' => 'float',
            'bounds_east' => 'float',
        ];
    }

    public function localizedName(): string
    {
        return $this->name;
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function towers(): HasMany
    {
        return $this->hasMany(Tower::class);
    }

    public function hasMapArea(): bool
    {
        return $this->bounds_south !== null
            || ($this->latitude !== null && $this->longitude !== null);
    }

    /**
     * @return array{south: float, west: float, north: float, east: float}|null
     */
    public function mapBounds(): ?array
    {
        if ($this->bounds_south !== null
            && $this->bounds_west !== null
            && $this->bounds_north !== null
            && $this->bounds_east !== null) {
            return [
                'south' => (float) $this->bounds_south,
                'west' => (float) $this->bounds_west,
                'north' => (float) $this->bounds_north,
                'east' => (float) $this->bounds_east,
            ];
        }

        if ($this->latitude !== null && $this->longitude !== null) {
            return GeographyReference::boundsAroundPoint($this->latitude, $this->longitude);
        }

        return null;
    }

    public function containsPoint(float $latitude, float $longitude): bool
    {
        $bounds = $this->mapBounds();

        if (! $bounds) {
            return false;
        }

        return $latitude >= $bounds['south']
            && $latitude <= $bounds['north']
            && $longitude >= $bounds['west']
            && $longitude <= $bounds['east'];
    }

    /**
     * @param  array{south: float, west: float, north: float, east: float}  $bounds
     */
    public function applyMapBounds(array $bounds): void
    {
        $this->bounds_south = $bounds['south'];
        $this->bounds_west = $bounds['west'];
        $this->bounds_north = $bounds['north'];
        $this->bounds_east = $bounds['east'];
        $this->latitude = ($bounds['south'] + $bounds['north']) / 2;
        $this->longitude = ($bounds['west'] + $bounds['east']) / 2;
    }

    public function clearMapArea(): void
    {
        $this->latitude = null;
        $this->longitude = null;
        $this->bounds_south = null;
        $this->bounds_west = null;
        $this->bounds_north = null;
        $this->bounds_east = null;
    }
}
