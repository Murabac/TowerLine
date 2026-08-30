<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BuildApprovalLetter extends Model
{
    public const TEMPLATE_VERSION = '2026-08';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_DENIED = 'denied';

    protected $fillable = [
        'tower_id',
        'operator_id',
        'reference_number',
        'status',
        'issued_at',
        'issued_by',
        'template_version',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
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

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasFullRegionAccess()) {
            return $query;
        }

        if ($user->isInspector()) {
            return $query->whereHas('tower', fn (Builder $towers) => $towers->whereIn('region_id', $user->regionIds() ?: [0]));
        }

        if ($user->isOperatorViewer()) {
            return $query->where('operator_id', $user->operator_id);
        }

        return $query->whereRaw('1 = 0');
    }
}
