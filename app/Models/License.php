<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class License extends Model
{
    protected $fillable = [
        'tower_id',
        'operator_id',
        'license_type',
        'issued_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'expires_at' => 'date',
        ];
    }

    public function tower(): BelongsTo
    {
        return $this->belongsTo(Tower::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    protected function displayStatus(): Attribute
    {
        return Attribute::get(function (): string {
            if ($this->expires_at->isPast()) {
                return 'expired';
            }

            if ($this->expires_at->lte(now()->addDays(30))) {
                return 'expiring_soon';
            }

            return 'active';
        });
    }

    public function scopeExpiringSoon(Builder $query): Builder
    {
        return $query
            ->whereDate('expires_at', '>=', now()->toDateString())
            ->whereDate('expires_at', '<=', now()->addDays(30)->toDateString());
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereDate('expires_at', '<', now()->toDateString());
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        if ($user->isInspector()) {
            return $query->whereHas('tower', fn (Builder $towers) => $towers->where('region_id', $user->region_id));
        }

        if ($user->isOperatorViewer()) {
            return $query->where('operator_id', $user->operator_id);
        }

        return $query->whereRaw('1 = 0');
    }
}
