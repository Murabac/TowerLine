<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FrequencyRenewalReceipt extends Model
{
    public const TEMPLATE_VERSION = '2026-08';

    public const RENEWAL_FEE_SLSH = 2_000_000;

    public static function renewalFeeAmount(): int
    {
        return self::RENEWAL_FEE_SLSH;
    }

    public function formattedFeeAmount(): string
    {
        return number_format(self::renewalFeeAmount()).' SLSH';
    }

    protected $fillable = [
        'frequency_allocation_id',
        'frequency_allocation_letter_id',
        'operator_id',
        'reference_number',
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

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(FrequencyAllocation::class, 'frequency_allocation_id');
    }

    public function letter(): BelongsTo
    {
        return $this->belongsTo(FrequencyAllocationLetter::class, 'frequency_allocation_letter_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->whereHas('allocation', fn (Builder $allocations) => $allocations->visibleTo($user));
    }
}
