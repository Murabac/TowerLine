<?php

namespace App\Support;

use App\Models\ApprovalRequest;
use App\Models\Inspection;
use App\Models\Tower;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InspectorApprovals
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function submitTowerCreate(User $user, array $attributes, ?UploadedFile $siteMap = null): ApprovalRequest
    {
        $token = (string) Str::uuid();
        $siteMapPath = self::storeUpload($siteMap, "approvals/{$token}");

        return ApprovalRequest::query()->create([
            'type' => ApprovalRequest::TYPE_TOWER_CREATE,
            'tower_id' => null,
            'payload' => [
                'attributes' => self::jsonSafe($attributes),
                'site_map_path' => $siteMapPath,
                'summary' => $attributes['name'] ?? $attributes['city'] ?? __('app.approvals.types.tower_create'),
            ],
            'submitted_by' => $user->id,
            'status' => ApprovalRequest::STATUS_PENDING,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function submitTowerUpdate(User $user, Tower $tower, array $attributes, ?UploadedFile $siteMap = null): ApprovalRequest
    {
        $token = (string) Str::uuid();
        $siteMapPath = self::storeUpload($siteMap, "approvals/{$token}");

        return ApprovalRequest::query()->create([
            'type' => ApprovalRequest::TYPE_TOWER_UPDATE,
            'tower_id' => $tower->id,
            'payload' => [
                'attributes' => self::jsonSafe($attributes),
                'snapshot' => self::jsonSafe($tower->only(array_keys($attributes))),
                'site_map_path' => $siteMapPath,
                'summary' => $tower->name,
            ],
            'submitted_by' => $user->id,
            'status' => ApprovalRequest::STATUS_PENDING,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, list<UploadedFile>>  $photoUploads
     */
    public static function submitInspection(User $user, Tower $tower, array $attributes, array $photoUploads = []): ApprovalRequest
    {
        $token = (string) Str::uuid();
        $photos = [];

        foreach (Inspection::photoSlots() as $slot) {
            $files = $photoUploads[$slot] ?? [];
            $stored = [];

            foreach ($files as $file) {
                if ($file instanceof UploadedFile) {
                    $stored[] = $file->store("approvals/{$token}/photos/{$slot}", 'public');
                }
            }

            if ($stored !== []) {
                $photos[$slot] = $stored;
            }
        }

        return ApprovalRequest::query()->create([
            'type' => ApprovalRequest::TYPE_INSPECTION,
            'tower_id' => $tower->id,
            'payload' => [
                'attributes' => self::jsonSafe($attributes),
                'photos' => $photos,
                'summary' => $tower->name,
            ],
            'submitted_by' => $user->id,
            'status' => ApprovalRequest::STATUS_PENDING,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function amendTower(ApprovalRequest $approval, array $attributes, ?UploadedFile $siteMap = null): ApprovalRequest
    {
        $payload = $approval->payload ?? [];
        $payload['attributes'] = self::jsonSafe($attributes);
        $payload['summary'] = $attributes['name']
            ?? $approval->tower?->name
            ?? ($payload['summary'] ?? $approval->typeLabel());

        if ($approval->type === ApprovalRequest::TYPE_TOWER_UPDATE && $approval->tower) {
            $payload['snapshot'] = self::jsonSafe($approval->tower->only(array_keys($attributes)));
        }

        if ($siteMap instanceof UploadedFile) {
            $payload['site_map_path'] = self::storeUpload($siteMap, 'approvals/'.Str::uuid());
        }

        $approval->update(['payload' => $payload]);

        return $approval->fresh() ?? $approval;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, list<UploadedFile>>  $photoUploads
     */
    public static function amendInspection(ApprovalRequest $approval, array $attributes, array $photoUploads = []): ApprovalRequest
    {
        $payload = $approval->payload ?? [];
        $payload['attributes'] = self::jsonSafe($attributes);
        $photos = is_array($payload['photos'] ?? null) ? $payload['photos'] : [];
        $token = (string) Str::uuid();

        foreach (Inspection::photoSlots() as $slot) {
            $files = $photoUploads[$slot] ?? [];
            $stored = [];

            foreach ($files as $file) {
                if ($file instanceof UploadedFile) {
                    $stored[] = $file->store("approvals/{$token}/photos/{$slot}", 'public');
                }
            }

            if ($stored !== []) {
                $photos[$slot] = $stored;
            }
        }

        $payload['photos'] = $photos;
        $approval->update(['payload' => $payload]);

        return $approval->fresh() ?? $approval;
    }

    public static function approve(ApprovalRequest $approval, User $reviewer, ?string $comment = null): Model
    {
        return DB::transaction(function () use ($approval, $reviewer, $comment) {
            $applied = match ($approval->type) {
                ApprovalRequest::TYPE_TOWER_CREATE => self::applyTowerCreate($approval),
                ApprovalRequest::TYPE_TOWER_UPDATE => self::applyTowerUpdate($approval),
                ApprovalRequest::TYPE_INSPECTION => self::applyInspection($approval),
                default => throw new \InvalidArgumentException("Unknown approval type [{$approval->type}]."),
            };

            $approval->update([
                'status' => ApprovalRequest::STATUS_APPROVED,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'reviewer_comment' => $comment,
                'applied_id' => $applied->id,
            ]);

            Audits::log('approved', $approval, [
                'type' => $approval->type,
                'name' => $approval->summary(),
                'submitted_by' => $approval->submitter?->name,
                'applied_type' => class_basename($applied),
                'applied_id' => $applied->id,
                'tower_id' => $applied instanceof Inspection ? $applied->tower_id : $applied->id,
            ]);

            return $applied;
        });
    }

    public static function reject(ApprovalRequest $approval, User $reviewer, string $comment): void
    {
        $approval->update([
            'status' => ApprovalRequest::STATUS_REJECTED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'reviewer_comment' => $comment,
        ]);

        Audits::log('rejected', $approval, [
            'type' => $approval->type,
            'name' => $approval->summary(),
            'submitted_by' => $approval->submitter?->name,
            'reviewer_comment' => $comment,
            'tower_id' => $approval->tower_id,
        ]);
    }

    /**
     * @return list<array{label: string, from: string, to: string}>
     */
    public static function diffLines(ApprovalRequest $approval): array
    {
        if ($approval->type !== ApprovalRequest::TYPE_TOWER_UPDATE) {
            return [];
        }

        $before = $approval->snapshot();
        $after = $approval->attributes();
        $keys = array_unique([...array_keys($before), ...array_keys($after)]);
        $lines = [];

        foreach ($keys as $key) {
            $from = $before[$key] ?? null;
            $to = $after[$key] ?? null;

            if ($from == $to) {
                continue;
            }

            $lines[] = [
                'label' => self::fieldLabel((string) $key),
                'from' => self::fieldValue($from),
                'to' => self::fieldValue($to),
            ];
        }

        return $lines;
    }

    private static function applyTowerCreate(ApprovalRequest $approval): Tower
    {
        $attributes = $approval->attributes();
        $attributes['name'] = TowerNameGenerator::generate(
            operatorId: (int) ($attributes['operator_id'] ?? 0),
            regionId: (int) ($attributes['region_id'] ?? 0),
            city: (string) ($attributes['city'] ?? ''),
            districtId: isset($attributes['district_id']) ? (int) $attributes['district_id'] : null,
            subDistrictId: isset($attributes['sub_district_id']) ? (int) $attributes['sub_district_id'] : null,
        );

        $tower = Tower::query()->create($attributes);
        self::attachSiteMap($tower, $approval->siteMapPath());

        Audits::log('created', $tower, $tower->only(['name', 'region_id', 'district_id', 'sub_district_id', 'operator_id', 'status']));

        return $tower->fresh() ?? $tower;
    }

    private static function applyTowerUpdate(ApprovalRequest $approval): Tower
    {
        $tower = $approval->tower ?? Tower::query()->findOrFail($approval->tower_id);
        $before = $tower->only(['name', 'status', 'latitude', 'longitude', 'district_id', 'sub_district_id']);
        $tower->update($approval->attributes());
        self::attachSiteMap($tower, $approval->siteMapPath());

        Audits::log('updated', $tower, ['before' => $before, 'after' => $tower->only(array_keys($before))]);

        return $tower->fresh() ?? $tower;
    }

    private static function applyInspection(ApprovalRequest $approval): Inspection
    {
        $tower = $approval->tower ?? Tower::query()->findOrFail($approval->tower_id);
        $photos = [];

        foreach ($approval->photos() as $slot => $paths) {
            $moved = [];

            foreach ($paths as $path) {
                $moved[] = self::movePublicFile($path, "inspections/{$tower->id}/".basename($path));
            }

            if ($moved !== []) {
                $photos[$slot] = $moved;
            }
        }

        $inspection = $tower->inspections()->create([
            ...$approval->attributes(),
            'inspector_id' => $approval->submitted_by,
            'photos' => $photos,
            'inspected_at' => $approval->attributes()['inspected_at'] ?? $approval->created_at,
        ]);

        Audits::log('created', $inspection, [
            'tower_id' => $tower->id,
            'health' => $inspection->derivedHealth(),
            'approval_id' => $approval->id,
        ]);

        return $inspection;
    }

    private static function attachSiteMap(Tower $tower, ?string $path): void
    {
        if (! $path) {
            return;
        }

        if ($tower->site_map_path) {
            Storage::disk('public')->delete($tower->site_map_path);
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $destination = "towers/{$tower->id}/site-map".($extension ? '.'.$extension : '');

        $tower->update([
            'site_map_path' => self::movePublicFile($path, $destination),
        ]);
    }

    private static function storeUpload(?UploadedFile $file, string $directory): ?string
    {
        if (! $file instanceof UploadedFile) {
            return null;
        }

        return $file->store($directory, 'public');
    }

    private static function movePublicFile(string $from, string $to): string
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($from)) {
            return $from;
        }

        $disk->delete($to);
        $disk->move($from, $to);

        return $to;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private static function jsonSafe(array $values): array
    {
        return collect($values)->map(function ($value) {
            if ($value instanceof CarbonInterface) {
                return $value->toDateString();
            }

            return $value;
        })->all();
    }

    private static function fieldLabel(string $key): string
    {
        $labelKey = 'app.audit.fields.'.$key;

        return trans()->has($labelKey) ? __($labelKey) : str_replace('_', ' ', $key);
    }

    private static function fieldValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_array($value)) {
            return implode(', ', array_map(fn ($item) => is_scalar($item) ? (string) $item : json_encode($item), $value));
        }

        return (string) $value;
    }
}
