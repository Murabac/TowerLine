<?php

namespace App\Http\Requests;

use App\Models\License;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLicenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $license = $this->route('license');

        return $license
            ? $this->user()->can('update', $license)
            : $this->user()->can('create', License::class);
    }

    public function rules(): array
    {
        $creating = ! $this->route('license');

        return [
            'tower_id' => [$creating ? 'required' : 'sometimes', 'exists:towers,id'],
            'license_type' => ['required', Rule::in(['A', 'B', 'C'])],
            'issued_at' => ['required', 'date'],
            'expires_at' => ['required', 'date', 'after:issued_at'],
        ];
    }
}
