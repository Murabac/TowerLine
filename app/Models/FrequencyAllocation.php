<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FrequencyAllocation extends Model
{
    protected $fillable = [
        'operator_id',
        'region_id',
        'band_label',
        'frequency_range',
        'channel_details',
        'issued_at',
        'expires_at',
        'notes',
        'documents',
        'renewed_from_id',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'expires_at' => 'date',
            'documents' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (FrequencyAllocation $allocation) {
            foreach ($allocation->documentRecords() as $document) {
                Storage::disk('public')->delete($document['path']);
            }
        });
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function renewedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'renewed_from_id');
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(self::class, 'renewed_from_id');
    }

    public function letters(): HasMany
    {
        return $this->hasMany(FrequencyAllocationLetter::class)->latest('issued_at');
    }

    public function currentLetter(): HasOne
    {
        return $this->hasOne(FrequencyAllocationLetter::class)->latestOfMany('issued_at');
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(FrequencyRenewalReceipt::class)->latest('issued_at');
    }

    public function currentReceipt(): HasOne
    {
        return $this->hasOne(FrequencyRenewalReceipt::class)->latestOfMany('issued_at');
    }

    /**
     * @return \Illuminate\Support\Collection<int, FrequencyAllocation>
     */
    public function renewalChain(): \Illuminate\Support\Collection
    {
        $chain = collect([$this]);
        $cursor = $this->relationLoaded('renewedFrom') ? $this->renewedFrom : $this->renewedFrom()->first();

        while ($cursor) {
            $chain->push($cursor);
            $cursor = $cursor->relationLoaded('renewedFrom') ? $cursor->renewedFrom : $cursor->renewedFrom()->first();
        }

        return $chain;
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{kind: string, document: FrequencyAllocationLetter|FrequencyRenewalReceipt, allocation: FrequencyAllocation}>
     */
    public function officialDocumentsTimeline(): \Illuminate\Support\Collection
    {
        return $this->renewalChain()
            ->flatMap(function (self $allocation) {
                $letters = $allocation->relationLoaded('letters')
                    ? $allocation->letters
                    : $allocation->letters()->get();

                $receipts = $allocation->relationLoaded('receipts')
                    ? $allocation->receipts
                    : $allocation->receipts()->get();

                return $letters->map(fn (FrequencyAllocationLetter $letter) => [
                    'kind' => 'letter',
                    'document' => $letter,
                    'allocation' => $allocation,
                ])->merge($receipts->map(fn (FrequencyRenewalReceipt $receipt) => [
                    'kind' => 'receipt',
                    'document' => $receipt,
                    'allocation' => $allocation,
                ]));
            })
            ->sortByDesc(fn (array $row) => $row['document']->issued_at->timestamp)
            ->values();
    }

    public function loadRenewalChain(int $depth = 12): self
    {
        $this->load(['letters', 'receipts', 'renewedFrom']);

        if ($depth > 0 && $this->renewedFrom) {
            $this->renewedFrom->loadRenewalChain($depth - 1);
        }

        return $this;
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

    public function scopeExpiringWithin(Builder $query, int $days): Builder
    {
        return $query
            ->whereDate('expires_at', '>=', now()->toDateString())
            ->whereDate('expires_at', '<=', now()->addDays($days)->toDateString());
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereDate('expires_at', '<', now()->toDateString());
    }

    public function scopeOutstandingRenewal(Builder $query): Builder
    {
        return $query->expired()->whereDoesntHave('renewals');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasFullRegionAccess()) {
            return $query;
        }

        if ($user->requiresRegions()) {
            return $query->where(function (Builder $scoped) use ($user) {
                $regionIds = $user->regionIds() ?: [0];

                $scoped
                    ->whereNull('region_id')
                    ->orWhereIn('region_id', $regionIds);
            });
        }

        if ($user->isOperatorViewer()) {
            return $query->where('operator_id', $user->operator_id);
        }

        return $query->whereRaw('1 = 0');
    }

    public function coverageLabel(): string
    {
        return $this->region?->localizedName() ?? __('app.frequencies.national');
    }

    /**
     * @return list<array{path: string, name: string, size: int|null}>
     */
    public function documentRecords(): array
    {
        return collect($this->documents ?? [])
            ->map(function ($document) {
                if (is_string($document) && $document !== '') {
                    return [
                        'path' => $document,
                        'name' => basename($document),
                        'size' => null,
                    ];
                }

                if (! is_array($document) || empty($document['path'])) {
                    return null;
                }

                return [
                    'path' => $document['path'],
                    'name' => $document['name'] ?? basename($document['path']),
                    'size' => $document['size'] ?? null,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return list<array{path: string, name: string, size: int|null, url: string}>
     */
    public function documentList(): array
    {
        return collect($this->documentRecords())
            ->map(fn (array $document) => [
                ...$document,
                'url' => asset('storage/'.$document['path']),
            ])
            ->all();
    }

    /**
     * @param  array<int, UploadedFile>|null  $files
     * @param  array<int, string>  $removePaths
     */
    public function syncDocuments(?array $files, array $removePaths = [], int $max = 10): void
    {
        $documents = collect($this->documentRecords());

        foreach ($removePaths as $path) {
            if ($documents->contains(fn (array $document) => $document['path'] === $path)) {
                Storage::disk('public')->delete($path);
            }
        }

        $documents = $documents
            ->reject(fn (array $document) => in_array($document['path'], $removePaths, true))
            ->values();

        foreach ($files ?? [] as $file) {
            if (! $file instanceof UploadedFile || $documents->count() >= $max) {
                continue;
            }

            $documents->push([
                'path' => $file->store('frequencies/'.$this->id, 'public'),
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
            ]);
        }

        $this->forceFill([
            'documents' => $documents->all(),
        ])->save();
    }
}
