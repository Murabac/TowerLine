<?php

namespace App\Models;

use App\Support\GeographyReference;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class District extends Model
{
    protected $fillable = [
        'region_id',
        'name',
        'grade',
    ];

    public function localizedName(): string
    {
        return $this->name;
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function subDistricts(): HasMany
    {
        return $this->hasMany(SubDistrict::class)->orderBy('name');
    }

    public function towers(): HasMany
    {
        return $this->hasMany(Tower::class);
    }

    /**
     * @return array{south: float, west: float, north: float, east: float}|null
     */
    public function mapBounds(): ?array
    {
        $this->loadMissing('subDistricts');

        return GeographyReference::unionBounds(
            $this->subDistricts->map(fn (SubDistrict $subDistrict) => $subDistrict->mapBounds())
        );
    }
}
