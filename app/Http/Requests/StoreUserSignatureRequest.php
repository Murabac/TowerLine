<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserSignatureRequest extends FormRequest
{
    use ValidatesDrawnSignature;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return $this->signatureRules();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'use_saved_signature' => false,
            'save_signature' => true,
        ]);
    }
}
