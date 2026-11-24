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

    /** A tower that already stands, registered by Ministry staff and confirmed by the Director. */
    public const TYPE_TOWER_EXISTING = 'tower_existing';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const TYPES = [
        self::TYPE_TOWER_CREATE,
        self::TYPE_TOWER_UPDATE,
        self::TYPE_INSPECTION,
        self::TYPE_TOWER_EXISTING,
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
        if ($user->canTask('approvals.review') && $user->canTask('towers.confirm_existing')) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user) {
            $query->where('submitted_by', $user->id);

            if ($user->canTask('approvals.review')) {
                $query->orWhere('type', '!=', self::TYPE_TOWER_EXISTING);
            }

            if ($user->canTask('towers.confirm_existing')) {
                $query->orWhere('type', self::TYPE_TOWER_EXISTING);
            }
        });
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isExistingTower(): bool
    {
        return $this->type === self::TYPE_TOWER_EXISTING;
    }

    /**
     * Whether this user decides on this kind of submission (approve, reject, correct).
     */
    public function isReviewableBy(User $user): bool
    {
        return $this->isExistingTower()
            ? $user->canTask('towers.confirm_existing')
            : $user->canTask('approvals.review');
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

    /**
     * @return list<string>
     */
    public function photoUrls(): array
    {
        return collect($this->photoUrlsBySlot())
            ->flatten()
            ->values()
            ->all();
    }

    /**
     * @return array<string, list<string>>
     */
    public function photoUrlsBySlot(): array
    {
        $index = 0;
        $urls = [];

        foreach ($this->photos() as $slot => $paths) {
            $urls[$slot] = [];

            foreach ((array) $paths as $path) {
                if (! is_string($path) || $path === '') {
                    continue;
                }

                $urls[$slot][] = route('approvals.photos.show', [$this, $index]);
                $index++;
            }
        }

        return $urls;
    }

    public function siteMapUrl(): ?string
    {
        return $this->siteMapPath()
            ? route('approvals.site-map', $this)
            : null;
    }
}
