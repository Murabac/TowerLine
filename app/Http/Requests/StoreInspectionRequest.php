<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [\App\Models\Inspection::class, $this->route('tower')]);
    }

    public function rules(): array
    {
        return [
            'power_status' => ['required', Rule::in(['on_grid', 'generator', 'battery', 'down'])],
            'generator_condition' => ['required', Rule::in(['good', 'fair', 'poor', 'n_a'])],
            'physical_condition' => ['required', Rule::in(['good', 'fair', 'poor'])],
            'notes' => ['nullable', 'string', 'max:5000'],
            'photos' => ['nullable', 'array', 'max:6'],
            'photos.*' => ['image', 'max:5120'],
        ];
    }
}
