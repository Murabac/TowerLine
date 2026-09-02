<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalRequest extends Model
{
    public const TYPE_TOWER_CREATE = 'tower_create';

    public const TYPE_TOWER_UPDATE = 'tower_update';

    public const TYPE_INSPECTION = 'inspection';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const TYPES = [
        self::TYPE_TOWER_CREATE,
        self::TYPE_TOWER_UPDATE,
        self::TYPE_INSPECTION,
    ];

    protected $fillable = [
        'type',
        'tower_id',
        'payload',
        'submitted_by',
        'status',
        'reviewed_by',
        'reviewed_at',
        'reviewer_comment',
        'applied_id',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function tower(): BelongsTo
    {
        return $this->belongsTo(Tower::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->canTask('approvals.review')) {
            return $query;
        }

        return $query->where('submitted_by', $user->id);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isOwnedBy(User $user): bool
    {
        return (int) $this->submitted_by === (int) $user->id;
    }

    public function typeLabel(): string
    {
        return __('app.approvals.types.'.$this->type);
    }

    public function statusLabel(): string
    {
        return __('app.approvals.status.'.$this->status);
    }

    public function summary(): string
    {
        $payload = $this->payload ?? [];

        return (string) ($payload['summary'] ?? $this->tower?->name ?? $this->typeLabel());
    }

    /**
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        $attributes = $this->payload['attributes'] ?? [];

        return is_array($attributes) ? $attributes : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        $snapshot = $this->payload['snapshot'] ?? [];

        return is_array($snapshot) ? $snapshot : [];
    }

    public function siteMapPath(): ?string
    {
        $path = $this->payload['site_map_path'] ?? null;

        return is_string($path) && $path !== '' ? $path : null;
    }

    /**
     * @return array<string, list<string>>
     */
    public function photos(): array
    {
        $photos = $this->payload['photos'] ?? [];

        return is_array($photos) ? $photos : [];
    }
}
