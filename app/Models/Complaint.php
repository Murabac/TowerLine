<?php

namespace App\Models;

use App\Support\Permissions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class Complaint extends Model
{
    public const TYPE_SIGNAL_ISSUE = 'signal_issue';

    public const TYPE_SAFETY_HAZARD = 'safety_hazard';

    public const TYPE_UNAUTHORIZED_TOWER = 'unauthorized_tower';

    public const TYPE_INTERFERENCE = 'interference';

    public const TYPE_COVERAGE_GAP = 'coverage_gap';

    public const TYPE_ENVIRONMENTAL = 'environmental';

    public const TYPE_OTHER = 'other';

    public const TYPES = [
        self::TYPE_SIGNAL_ISSUE,
        self::TYPE_SAFETY_HAZARD,
        self::TYPE_UNAUTHORIZED_TOWER,
        self::TYPE_INTERFERENCE,
        self::TYPE_COVERAGE_GAP,
        self::TYPE_ENVIRONMENTAL,
        self::TYPE_OTHER,
    ];

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_SUBMITTED,
        self::STATUS_UNDER_REVIEW,
        self::STATUS_ASSIGNED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_RESOLVED,
        self::STATUS_CLOSED,
        self::STATUS_REJECTED,
    ];

    public const PRIORITY_LOW = 'low';

    public const PRIORITY_MEDIUM = 'medium';

    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_CRITICAL = 'critical';

    public const PRIORITIES = [
        self::PRIORITY_LOW,
        self::PRIORITY_MEDIUM,
        self::PRIORITY_HIGH,
        self::PRIORITY_CRITICAL,
    ];

    protected $fillable = [
        'reference_number',
        'tower_id',
        'latitude',
        'longitude',
        'region_id',
        'complaint_type',
        'description',
        'submitter_name',
        'submitter_phone',
        'photo_path',
        'status',
        'priority',
        'created_by',
        'assigned_to',
        'resolution_notes',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'resolved_at' => 'datetime',
        ];
    }

    public function tower(): BelongsTo
    {
        return $this->belongsTo(Tower::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function updates(): HasMany
    {
        return $this->hasMany(ComplaintUpdate::class)->orderByDesc('created_at');
    }

    public function mapLatitude(): ?float
    {
        return $this->latitude ?? $this->tower?->latitude;
    }

    public function mapLongitude(): ?float
    {
        return $this->longitude ?? $this->tower?->longitude;
    }

    public function typeLabel(): string
    {
        return __('app.complaints.types.'.$this->complaint_type);
    }

    public function statusLabel(): string
    {
        return __('app.complaints.status.'.$this->status);
    }

    public function priorityLabel(): string
    {
        return __('app.complaints.priority.'.$this->priority);
    }

    public function isOpen(): bool
    {
        return ! in_array($this->status, [self::STATUS_CLOSED, self::STATUS_REJECTED], true);
    }

    public function awaitingClosure(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    public function logUpdate(User $user, string $note, ?string $statusChange = null): ComplaintUpdate
    {
        return $this->updates()->create([
            'user_id' => $user->id,
            'note' => $note,
            'status_change' => $statusChange,
        ]);
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? route('complaints.photo', $this) : null;
    }

    public function photoExists(): bool
    {
        return $this->photo_path && Storage::disk('local')->exists($this->photo_path);
    }

    /**
     * @return Builder<static>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->canTask('complaints.triage') || $user->isAdmin()) {
            return $query;
        }

        if ($user->isOperatorViewer()) {
            return $query->where(function (Builder $inner) use ($user) {
                $inner->whereHas('tower', fn (Builder $towers) => $towers->where('operator_id', $user->operator_id))
                    ->orWhere('assigned_to', $user->id);
            });
        }

        if ($user->requiresRegions()) {
            return $query->whereIn('region_id', $user->regionIds() ?: [0]);
        }

        if ($user->canTask('complaints.view')) {
            return $query;
        }

        return $query->whereRaw('1 = 0');
    }

    public function scopeNeedingStaffAction(Builder $query, User $user): Builder
    {
        $query->visibleTo($user);

        if ($user->canTask('complaints.triage')) {
            return $query->whereIn('status', [
                self::STATUS_SUBMITTED,
                self::STATUS_UNDER_REVIEW,
                self::STATUS_RESOLVED,
            ]);
        }

        return $query->whereIn('status', [
            self::STATUS_ASSIGNED,
            self::STATUS_IN_PROGRESS,
        ]);
    }

    /**
     * @return Collection<int, User>
     */
    public function assignableUsers(): Collection
    {
        $inspectors = User::query()
            ->where('role', Role::KEY_INSPECTOR)
            ->with('regions')
            ->get()
            ->filter(fn (User $user) => $user->coversRegion((int) $this->region_id))
            ->values();

        $operators = User::query()
            ->where('role', Role::KEY_OPERATOR_VIEWER)
            ->when(
                $this->tower?->operator_id,
                fn (Builder $query) => $query->where('operator_id', $this->tower->operator_id),
            )
            ->orderBy('name')
            ->get();

        return $inspectors->concat($operators)->unique('id')->values();
    }

    public static function stamp(?\DateTimeInterface $value): ?string
    {
        return $value?->timezone(config('app.timezone'))->format('d M Y H:i');
    }

    /**
     * @return list<string>
     */
    public static function triageRoleKeys(): array
    {
        return collect(Permissions::ROLE_TASKS)
            ->filter(fn (array $tasks) => in_array('complaints.triage', $tasks, true))
            ->keys()
            ->all();
    }
}
