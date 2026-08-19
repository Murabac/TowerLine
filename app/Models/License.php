<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class License extends Model
{
    protected $fillable = [
        'tower_id',
        'operator_id',
        'license_type',
        'issued_at',
        'expires_at',
        'documents',
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
        static::deleting(function (License $license) {
            foreach ($license->documentRecords() as $document) {
                Storage::disk('public')->delete($document['path']);
            }
        });
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
            return $query->whereHas('tower', fn (Builder $towers) => $towers->whereIn('region_id', $user->regionIds() ?: [0]));
        }

        if ($user->isOperatorViewer()) {
            return $query->where('operator_id', $user->operator_id);
        }

        return $query->whereRaw('1 = 0');
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
                'path' => $file->store('licenses/'.$this->tower_id, 'public'),
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
            ]);
        }

        $this->forceFill([
            'documents' => $documents->all(),
        ])->save();
    }
}
