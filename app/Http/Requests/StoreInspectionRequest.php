<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($approval = $this->route('approval')) {
            return $this->user()->can('update', $approval);
        }

        return $this->user()->can('create', [\App\Models\Inspection::class, $this->route('tower')]);
    }

    public function rules(): array
    {
        return [
            'power_status' => ['nullable', Rule::in(['on_grid', 'generator', 'battery', 'down'])],
            'generator_condition' => ['nullable', Rule::in(['good', 'fair', 'poor', 'n_a'])],
            'physical_condition' => ['nullable', Rule::in(['good', 'fair', 'poor'])],
            'notes' => ['nullable', 'string', 'max:5000'],
            'photos' => ['nullable', 'array'],
            'photos.wide' => ['nullable', 'array', 'max:8'],
            'photos.wide.*' => ['image', 'max:5120'],
            'photos.base' => ['nullable', 'array', 'max:8'],
            'photos.base.*' => ['image', 'max:5120'],
            'photos.power' => ['nullable', 'array', 'max:8'],
            'photos.power.*' => ['image', 'max:5120'],
            'photos.condition' => ['nullable', 'array', 'max:8'],
            'photos.condition.*' => ['image', 'max:5120'],
        ];
    }
}
