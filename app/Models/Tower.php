<?php

namespace App\Models;

use App\Models\User;
use App\Support\TowerCapacity;
use App\Support\TowerPowerSource;
use App\Support\TowerProximity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class Tower extends Model
{
    /** Days without a visit before a tower is flagged as needing a fresh inspection. */
    public const INSPECTION_STALE_DAYS = 180;

    protected $fillable = [
        'name',
        'latitude',
        'longitude',
        'region_id',
        'district_id',
        'sub_district_id',
        'city',
        'land_area',
        'operator_id',
        'type',
        'height_m',
        'nearest_school_m',
        'nearest_school_name',
        'nearest_hospital_m',
        'nearest_hospital_name',
        'nearest_house_m',
        'nearest_house_name',
        'fence_distance_m',
        'other_towers_nearby',
        'site_map_notes',
        'site_map_path',
        'capacity',
        'power_sources',
        'signal_radius_m',
        'status',
        'health_status',
        'commissioned_at',
        'application_date',
        'registration_inspector_notes',
        'registration_director_notes',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'height_m' => 'float',
            'nearest_school_m' => 'integer',
            'nearest_hospital_m' => 'integer',
            'nearest_house_m' => 'integer',
            'fence_distance_m' => 'float',
            'signal_radius_m' => 'integer',
            'power_sources' => 'array',
            'commissioned_at' => 'date',
            'application_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (Tower $tower) {
            if ($tower->latitude === null || $tower->longitude === null) {
                return;
            }

            if (DB::connection()->getDriverName() === 'mysql') {
                $wkt = sprintf('POINT(%F %F)', $tower->longitude, $tower->latitude);
                DB::update('UPDATE towers SET location = ST_GeomFromText(?) WHERE id = ?', [$wkt, $tower->id]);
            }

            TowerProximity::refreshAffectedTowers($tower);
        });

        static::deleting(function (Tower $tower) {
            $tower->nearbyNeighborIdsForDelete = TowerProximity::nearby($tower)
                ->pluck('tower.id')
                ->all();
        });

        static::deleted(function (Tower $tower) {
            if (! empty($tower->nearbyNeighborIdsForDelete)) {
                TowerProximity::refreshMany($tower->nearbyNeighborIdsForDelete);
            }
        });
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{tower: Tower, distance_m: int}>
     */
    public function nearbyTowers(int $radiusMeters = TowerProximity::NEARBY_RADIUS_METERS): \Illuminate\Support\Collection
    {
        return TowerProximity::nearby($this, $radiusMeters);
    }

    public function nearbyTowersSummary(int $radiusMeters = TowerProximity::NEARBY_RADIUS_METERS): ?string
    {
        return TowerProximity::formatSummary($this->nearbyTowers($radiusMeters), $radiusMeters);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function subDistrict(): BelongsTo
    {
        return $this->belongsTo(SubDistrict::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class)->latest('inspected_at');
    }

    public function approvalRequests(): HasMany
    {
        return $this->hasMany(ApprovalRequest::class)->latest();
    }

    public function pendingApprovalRequests(): HasMany
    {
        return $this->approvalRequests()->pending();
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class)->latest('expires_at');
    }

    public function approvalLetters(): HasMany
    {
        return $this->hasMany(BuildApprovalLetter::class)->latest('issued_at');
    }

    public function currentApprovalLetter(): HasOne
    {
        return $this->hasOne(BuildApprovalLetter::class)->latestOfMany('issued_at');
    }

    public function siteApplication(): HasOne
    {
        return $this->hasOne(SiteApplication::class);
    }

    public function letterLocationLine(): string
    {
        return collect([
            $this->region?->localizedName(),
            $this->district?->localizedName(),
            $this->city,
            $this->subDistrict?->localizedName(),
        ])->filter()->unique()->implode(' / ');
    }

    public function letterTowerIdentity(): string
    {
        return __('app.status.'.$this->type).' / '.$this->name;
    }

    public function latestInspection(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Inspection::class)->latestOfMany('inspected_at');
    }

    public function currentLicense(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(License::class)->latestOfMany('expires_at');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasFullRegionAccess()) {
            return $query;
        }

        if ($user->requiresRegions()) {
            return $query->whereIn('region_id', $user->regionIds() ?: [0]);
        }

        if ($user->isOperatorViewer()) {
            return $query->where('operator_id', $user->operator_id);
        }

        return $query->whereRaw('1 = 0');
    }

    public function refreshHealthStatus(): void
    {
        $latest = $this->inspections()->first();

        $this->forceFill([
            'health_status' => $latest?->derivedHealth() ?? 'unknown',
        ])->saveQuietly();
    }

    public function isInspectionOverdue(): bool
    {
        $latest = $this->latestInspection;

        if (! $latest) {
            return $this->status === 'active';
        }

        return $latest->inspected_at->lt(now()->subDays(self::INSPECTION_STALE_DAYS));
    }

    public function scopeInspectionOverdue(Builder $query): Builder
    {
        $cutoff = now()->subDays(self::INSPECTION_STALE_DAYS);

        return $query->where(function (Builder $q) use ($cutoff) {
            $q->where(function (Builder $neverInspected) {
                $neverInspected->where('status', 'active')->whereDoesntHave('latestInspection');
            })->orWhereHas('latestInspection', function (Builder $inspection) use ($cutoff) {
                $inspection->where('inspected_at', '<', $cutoff);
            });
        });
    }

    public function scopeWithPowerSource(Builder $query, string $source): Builder
    {
        return $query->whereJsonContains('power_sources', $source);
    }

    /**
     * @return list<string>
     */
    public function powerSourceKeys(): array
    {
        return TowerPowerSource::normalize($this->power_sources);
    }

    public function powerSourceLabel(): string
    {
        return TowerPowerSource::labelList($this->powerSourceKeys());
    }

    public function capacityLabel(): string
    {
        return TowerCapacity::label($this->capacity);
    }

    public function proximityLabel(?string $name, ?int $meters): string
    {
        if ($name && $meters !== null) {
            return $name.' — '.number_format($meters).' m';
        }

        if ($meters !== null) {
            return number_format($meters).' m';
        }

        if ($name) {
            return $name;
        }

        return '—';
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'under_construction' => '#2563EB',
            'decommissioned' => '#6B7280',
            default => match ($this->health_status) {
                'good' => '#22C55E',
                'needs_attention' => '#F59E0B',
                'critical' => '#DC2626',
                default => '#94A3B8',
            },
        };
    }
}
