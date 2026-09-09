<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class SiteApplication extends Model
{
    public const STATUS_RECEIVED = 'received';

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_DIRECTOR_REVIEW = 'director_review';

    public const STATUS_DG_REVIEW = 'dg_review';

    public const STATUS_GRANTED = 'granted';

    public const STATUS_REFUSED = 'refused';

    public const STATUS_RETURNED = 'returned';

    public const DECISION_APPROVE = 'approve';

    public const DECISION_REJECT = 'reject';

    public const DECISION_YES = 'yes';

    public const DECISION_NO = 'no';

    public const DECISION_GRANT = 'grant';

    public const DECISION_RETURN = 'return';

    public const DOCUMENT_FIELDS = [
        'letter' => 'letter_path',
        'layout' => 'layout_path',
        'radio' => 'radio_path',
        'icnirp' => 'icnirp_path',
    ];

    protected $fillable = [
        'reference_number',
        'contact_name',
        'telephone',
        'operator_id',
        'license_class_no',
        'email',
        'address',
        'site_name',
        'region_id',
        'district_id',
        'sub_district_id',
        'city',
        'latitude',
        'longitude',
        'type',
        'height_m',
        'capacity',
        'signal_radius_m',
        'land_area',
        'fence_distance_m',
        'power_sources',
        'nearest_school_name',
        'nearest_school_m',
        'nearest_hospital_name',
        'nearest_hospital_m',
        'nearest_house_name',
        'nearest_house_m',
        'site_map_notes',
        'letter_path',
        'map_path',
        'layout_path',
        'radio_path',
        'icnirp_path',
        'status',
        'assigned_to',
        'assigned_by',
        'assigned_at',
        'site_visit_on',
        'site_visit_notes',
        'site_visited_by',
        'site_visited_at',
        'officer_remarks',
        'officer_reviewed_by',
        'officer_reviewed_at',
        'officer_signature_path',
        'director_decision',
        'director_remarks',
        'director_name',
        'director_reviewed_by',
        'director_reviewed_at',
        'director_signature_path',
        'dg_decision',
        'dg_remarks',
        'dg_name',
        'dg_reviewed_by',
        'dg_reviewed_at',
        'dg_signature_path',
        'tower_id',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'height_m' => 'float',
            'fence_distance_m' => 'float',
            'power_sources' => 'array',
            'assigned_at' => 'datetime',
            'site_visit_on' => 'date',
            'site_visited_at' => 'datetime',
            'officer_reviewed_at' => 'datetime',
            'director_reviewed_at' => 'datetime',
            'dg_reviewed_at' => 'datetime',
        ];
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
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

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'site_visited_by');
    }

    public function officerReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'officer_reviewed_by');
    }

    public function directorReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'director_reviewed_by');
    }

    public function dgReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dg_reviewed_by');
    }

    public function tower(): BelongsTo
    {
        return $this->belongsTo(Tower::class);
    }

    public function isReceived(): bool
    {
        return $this->status === self::STATUS_RECEIVED;
    }

    public function isAssigned(): bool
    {
        return $this->status === self::STATUS_ASSIGNED;
    }

    public function isDirectorReview(): bool
    {
        return $this->status === self::STATUS_DIRECTOR_REVIEW;
    }

    public function isDgReview(): bool
    {
        return $this->status === self::STATUS_DG_REVIEW;
    }

    public function isGranted(): bool
    {
        return $this->status === self::STATUS_GRANTED;
    }

    public function isRefused(): bool
    {
        return $this->status === self::STATUS_REFUSED;
    }

    public function isReturned(): bool
    {
        return $this->status === self::STATUS_RETURNED;
    }

    public function canBeAssigned(): bool
    {
        return in_array($this->status, [self::STATUS_RECEIVED, self::STATUS_ASSIGNED, self::STATUS_RETURNED], true);
    }

    public function canBeReviewed(): bool
    {
        return $this->status === self::STATUS_ASSIGNED;
    }

    public function canReceiveDirectorDecision(): bool
    {
        return $this->status === self::STATUS_DIRECTOR_REVIEW;
    }

    public function canReceiveDgDecision(): bool
    {
        return $this->status === self::STATUS_DG_REVIEW;
    }

    public function hasSiteVisit(): bool
    {
        return $this->site_visit_on !== null || filled($this->site_visit_notes);
    }

    public function statusLabel(): string
    {
        return __('app.applications.status.'.$this->status);
    }

    public function documentPath(string $field): ?string
    {
        $column = self::DOCUMENT_FIELDS[$field] ?? null;

        return $column ? $this->{$column} : null;
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->managesSiteApplications()) {
            return $query;
        }

        $query->where('assigned_to', $user->id);

        if ($user->requiresRegions()) {
            $query->whereIn('region_id', $user->regionIds() ?: [0]);
        }

        return $query;
    }

    /**
     * @return Collection<int, User>
     */
    public static function assigneesForRegion(int $regionId): Collection
    {
        return User::query()
            ->with('regions')
            ->whereIn('role', [Role::KEY_REGIONAL_COORDINATOR, Role::KEY_ASSIGNED_OFFICER])
            ->orderBy('name')
            ->get()
            ->filter(fn (User $user) => $user->coversRegion($regionId))
            ->values();
    }

    public function assignTo(User $assignee, User $assigner): void
    {
        $this->forceFill([
            'assigned_to' => $assignee->id,
            'assigned_by' => $assigner->id,
            'assigned_at' => now(),
            'status' => self::STATUS_ASSIGNED,
        ])->save();
    }

    public function recordOfficerReview(User $officer, string $decision, string $visitOn, string $visitNotes, string $remarks, ?string $signaturePath = null): void
    {
        $this->forceFill([
            'site_visit_on' => $visitOn,
            'site_visit_notes' => $visitNotes,
            'site_visited_by' => $officer->id,
            'site_visited_at' => now(),
            'officer_remarks' => $remarks,
            'officer_reviewed_by' => $officer->id,
            'officer_reviewed_at' => now(),
            'officer_signature_path' => $signaturePath,
            'status' => $decision === self::DECISION_APPROVE
                ? self::STATUS_DIRECTOR_REVIEW
                : self::STATUS_RETURNED,
        ])->save();
    }

    public function recordDirectorDecision(User $director, string $decision, string $remarks, string $name, ?string $signaturePath = null): void
    {
        $this->forceFill([
            'director_decision' => $decision,
            'director_remarks' => $remarks,
            'director_name' => $name,
            'director_reviewed_by' => $director->id,
            'director_reviewed_at' => now(),
            'director_signature_path' => $signaturePath,
            'status' => $decision === self::DECISION_YES
                ? self::STATUS_DG_REVIEW
                : self::STATUS_REFUSED,
        ])->save();
    }

    public function recordDgDecision(User $dg, string $decision, string $remarks, string $name, ?string $signaturePath = null): void
    {
        $this->forceFill([
            'dg_decision' => $decision,
            'dg_remarks' => $remarks,
            'dg_name' => $name,
            'dg_reviewed_by' => $dg->id,
            'dg_reviewed_at' => now(),
            'dg_signature_path' => $signaturePath,
            'status' => $decision === self::DECISION_GRANT
                ? self::STATUS_GRANTED
                : self::STATUS_DIRECTOR_REVIEW,
        ])->save();
    }

    /**
     * @return list<array{party: string, label: string, name: string, outcome: string, at: \Illuminate\Support\Carbon|null, path: string|null}>
     */
    public function approvalSignatories(): array
    {
        $rows = [];

        if ($this->officer_reviewed_at) {
            $approved = $this->status !== self::STATUS_RETURNED;
            $rows[] = [
                'party' => 'officer',
                'label' => __('app.signatures.officer'),
                'name' => $this->officerReviewer?->name ?: $this->visitor?->name ?: '—',
                'outcome' => $approved
                    ? __('app.applications.approve')
                    : __('app.applications.reject'),
                'at' => $this->officer_reviewed_at,
                'path' => $this->officer_signature_path,
            ];
        }

        if ($this->director_reviewed_at) {
            $rows[] = [
                'party' => 'director',
                'label' => __('app.signatures.director'),
                'name' => $this->director_name ?: $this->directorReviewer?->name ?: '—',
                'outcome' => __('app.applications.decision_'.$this->director_decision),
                'at' => $this->director_reviewed_at,
                'path' => $this->director_signature_path,
            ];
        }

        if ($this->dg_reviewed_at) {
            $rows[] = [
                'party' => 'dg',
                'label' => __('app.signatures.dg'),
                'name' => $this->dg_name ?: $this->dgReviewer?->name ?: '—',
                'outcome' => __('app.applications.decision_'.$this->dg_decision),
                'at' => $this->dg_reviewed_at,
                'path' => $this->dg_signature_path,
            ];
        }

        return $rows;
    }
}
