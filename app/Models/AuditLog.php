<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'changes',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function modelLabel(): string
    {
        $key = 'app.audit.models.'.strtolower(class_basename($this->model_type));

        return trans()->has($key) ? __($key) : class_basename($this->model_type);
    }

    public function actionLabel(): string
    {
        return __('app.audit.actions.'.$this->action);
    }

    public function actorName(): string
    {
        return $this->user?->name ?: __('app.audit.system');
    }

    public function sentence(): string
    {
        $key = 'app.audit.sentences.'.$this->action;
        $record = $this->modelLabel();
        $name = $this->changeSet()['name'] ?? null;

        if (is_string($name) && $name !== '') {
            $record .= ' · '.$name;
        }

        if (! trans()->has($key)) {
            return $this->actorName().' · '.$this->actionLabel().' · '.$record;
        }

        return __($key, [
            'user' => $this->actorName(),
            'record' => $record,
        ]);
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    public function detailLines(): array
    {
        $changes = $this->changeSet();
        $lines = [];

        $name = $changes['name'] ?? null;
        if (is_string($name) && $name !== '' && (isset($changes['before']) || isset($changes['after']))) {
            $lines[] = [
                'label' => $this->fieldLabel('name'),
                'value' => $name,
            ];
        }

        if (isset($changes['before'], $changes['after']) && is_array($changes['before']) && is_array($changes['after'])) {
            $keys = array_unique([...array_keys($changes['before']), ...array_keys($changes['after'])]);

            foreach ($keys as $key) {
                $from = $changes['before'][$key] ?? null;
                $to = $changes['after'][$key] ?? null;

                if ($from == $to) {
                    continue;
                }

                $lines[] = [
                    'label' => $this->fieldLabel((string) $key),
                    'value' => $this->fieldValue((string) $key, $from).' → '.$this->fieldValue((string) $key, $to),
                ];
            }

            return $lines;
        }

        foreach ($changes as $key => $value) {
            if (in_array($key, ['before', 'after'], true)) {
                continue;
            }

            if ($value === null || $value === '') {
                continue;
            }

            $lines[] = [
                'label' => $this->fieldLabel((string) $key),
                'value' => $this->fieldValue((string) $key, $value),
            ];
        }

        return $lines;
    }

    /**
     * @return array<string, mixed>
     */
    private function changeSet(): array
    {
        $changes = $this->getAttribute('changes');

        return is_array($changes) ? $changes : [];
    }

    private function fieldLabel(string $key): string
    {
        $labelKey = 'app.audit.fields.'.$key;

        return trans()->has($labelKey) ? __($labelKey) : str_replace('_', ' ', $key);
    }

    private function fieldValue(string $key, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? __('app.all') : __('app.none');
        }

        if (is_array($value)) {
            return implode(', ', array_map(fn ($item) => is_scalar($item) ? (string) $item : json_encode($item), $value));
        }

        $string = (string) $value;

        if ($key === 'role' && trans()->has('app.roles.'.$string)) {
            return __('app.roles.'.$string);
        }

        foreach (['app.status.'.$string, 'app.inspections.'.$string] as $translation) {
            if (trans()->has($translation)) {
                return __($translation);
            }
        }

        if (str_ends_with($key, '_id') && is_numeric($string)) {
            $resolved = $this->resolveRelatedName($key, (int) $string);

            if ($resolved) {
                return $resolved;
            }
        }

        return $string;
    }

    private function resolveRelatedName(string $key, int $id): ?string
    {
        return match ($key) {
            'tower_id' => Tower::query()->find($id)?->name,
            'region_id' => Region::query()->find($id)?->localizedName(),
            'operator_id' => Operator::query()->find($id)?->name,
            default => null,
        };
    }

    public function recordUrl(): ?string
    {
        if ($this->action === 'deleted') {
            return null;
        }

        return match (class_basename($this->model_type)) {
            'Tower' => route('towers.show', $this->model_id),
            'License' => route('licenses.edit', $this->model_id),
            'User' => route('users.edit', $this->model_id),
            'Role' => route('roles.edit', $this->model_id),
            'ApprovalRequest' => route('approvals.show', $this->model_id),
            'Inspection' => isset($this->changeSet()['tower_id'])
                ? route('towers.show', $this->changeSet()['tower_id'])
                : null,
            default => null,
        };
    }
}
