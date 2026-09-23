<?php

namespace App\Http\Requests;

use App\Models\Complaint;
use App\Support\SomalilandPhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Complaint::class);
    }

    public function rules(): array
    {
        return [
            'complaint_type' => ['required', 'string', Rule::in(Complaint::TYPES)],
            'description' => ['required', 'string', 'max:5000'],
            'region_id' => ['required_without:tower_id', 'nullable', 'integer', 'exists:regions,id'],
            'tower_id' => ['nullable', 'integer', 'exists:towers,id'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'submitter_name' => ['nullable', 'string', 'max:255'],
            'submitter_phone' => ['nullable', 'string', 'max:20'],
            'priority' => ['nullable', 'string', Rule::in(Complaint::PRIORITIES)],
            'photo' => ['nullable', 'image', 'max:5120'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->filled('submitter_phone') && ! SomalilandPhone::isValid($this->string('submitter_phone')->toString())) {
                $validator->errors()->add('submitter_phone', __('app.complaints.phone_invalid'));
            }

            if (! $this->filled('tower_id') && (! $this->filled('latitude') || ! $this->filled('longitude'))) {
                $validator->errors()->add('latitude', __('app.complaints.location_required'));
            }
        });
    }
}
