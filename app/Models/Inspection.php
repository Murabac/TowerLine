<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inspection extends Model
{
    protected $fillable = [
        'tower_id',
        'inspector_id',
        'power_status',
        'generator_condition',
        'physical_condition',
        'notes',
        'photos',
        'inspected_at',
    ];

    protected function casts(): array
    {
        return [
            'photos' => 'array',
            'inspected_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (Inspection $inspection) {
            $inspection->tower?->refreshHealthStatus();
        });
    }

    public function tower(): BelongsTo
    {
        return $this->belongsTo(Tower::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function derivedHealth(): string
    {
        if ($this->power_status === 'down' || $this->physical_condition === 'poor') {
            return 'critical';
        }

        if ($this->generator_condition === 'poor' || $this->physical_condition === 'fair') {
            return 'needs_attention';
        }

        return 'good';
    }

    public function photoUrls(): array
    {
        return collect($this->photos ?? [])
            ->flatMap(fn ($path) => is_array($path) ? $path : [$path])
            ->filter(fn ($path) => is_string($path) && $path !== '')
            ->map(fn (string $path) => asset('storage/'.$path))
            ->values()
            ->all();
    }

    public static function photoSlots(): array
    {
        return ['wide', 'base', 'power', 'condition'];
    }
}
