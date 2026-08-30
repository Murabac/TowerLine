<?php

namespace App\Http\Requests;

use App\Models\FrequencyAllocation;
use Illuminate\Foundation\Http\FormRequest;

class StoreFrequencyAllocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $allocation = $this->route('frequency');

        return $allocation
            ? $this->user()->can('update', $allocation)
            : $this->user()->can('create', FrequencyAllocation::class);
    }

    public function rules(): array
    {
        return [
            'operator_id' => ['required', 'exists:operators,id'],
            'region_id' => ['nullable', 'exists:regions,id'],
            'band_label' => ['required', 'string', 'max:120'],
            'frequency_range' => ['required', 'string', 'max:120'],
            'channel_details' => ['nullable', 'string', 'max:2000'],
            'issued_at' => ['required', 'date'],
            'expires_at' => ['required', 'date', 'after:issued_at'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'documents' => ['nullable', 'array', 'max:10'],
            'documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx', 'max:10240'],
            'remove_documents' => ['nullable', 'array'],
            'remove_documents.*' => ['string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('region_id') === '') {
            $this->merge(['region_id' => null]);
        }
    }
}
