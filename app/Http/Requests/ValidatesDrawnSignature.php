<?php

namespace App\Http\Requests;

use App\Support\UserSignature;
use Illuminate\Validation\Validator;

trait ValidatesDrawnSignature
{
    /**
     * @return array<string, mixed>
     */
    protected function signatureRules(): array
    {
        return [
            'use_saved_signature' => ['sometimes', 'boolean'],
            'save_signature' => ['sometimes', 'boolean'],
            'signature_data' => ['nullable', 'string', 'max:400000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (UserSignature::binaryFromRequest($this->user(), $this) === null) {
                $validator->errors()->add('signature_data', __('app.signatures.required'));
            }
        });
    }
}
