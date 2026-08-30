<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Tower extends Model
{
    protected $fillable = [
        'name',
        'latitude',
        'longitude',
        'region_id',
        'district_id',
        'sub_district_id',
        'operator_id',
        'type',
        'height_m',
        'capacity',
        'signal_radius_m',
        'status',
        'health_status',
        'commissioned_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'height_m' => 'float',
            'signal_radius_m' => 'integer',
            'commissioned_at' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (Tower $tower) {
            if ($tower->latitude === null || $tower->longitude === null) {
                return;
            }

            if (DB::connection()->getDriverName() !== 'mysql') {
                return;
            }

            $wkt = sprintf('POINT(%F %F)', $tower->longitude, $tower->latitude);

            DB::update('UPDATE towers SET location = ST_GeomFromText(?) WHERE id = ?', [$wkt, $tower->id]);
        });
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

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class)->latest('expires_at');
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
        if ($user->isAdmin()) {
            return $query;
        }

        if ($user->isInspector()) {
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

        return $latest->inspected_at->lt(now()->subDays(90));
    }

    public function scopeInspectionOverdue(Builder $query): Builder
    {
        $cutoff = now()->subDays(90);

        return $query->where(function (Builder $q) use ($cutoff) {
            $q->where(function (Builder $neverInspected) {
                $neverInspected->where('status', 'active')->whereDoesntHave('latestInspection');
            })->orWhereHas('latestInspection', function (Builder $inspection) use ($cutoff) {
                $inspection->where('inspected_at', '<', $cutoff);
            });
        });
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
