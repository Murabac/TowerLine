<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFrequencyAllocationLetterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $allocation = $this->route('frequency');

        return $this->user()?->can('update', $allocation) ?? false;
    }

    public function rules(): array
    {
        return [
            'issued_at' => ['nullable', 'date'],
        ];
    }
}
