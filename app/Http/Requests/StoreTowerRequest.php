<?php

namespace App\Http\Requests;

use App\Models\Tower;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTowerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $tower = $this->route('tower');

        return $tower
            ? $this->user()->can('update', $tower)
            : $this->user()->can('create', Tower::class);
    }

    protected function prepareForValidation(): void
    {
        if ($this->user()->isInspector()) {
            $this->merge(['region_id' => $this->user()->region_id]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'region_id' => ['required', 'exists:regions,id'],
            'operator_id' => ['required', 'exists:operators,id'],
            'type' => ['required', Rule::in(['guyed', 'monopole', 'rooftop'])],
            'height_m' => ['required', 'numeric', 'min:1', 'max:500'],
            'capacity' => ['nullable', 'string', 'max:255'],
            'signal_radius_m' => ['required', 'integer', 'min:100', 'max:100000'],
            'status' => ['required', Rule::in(['active', 'under_construction', 'decommissioned'])],
            'commissioned_at' => ['nullable', 'date'],
        ];
    }
}
