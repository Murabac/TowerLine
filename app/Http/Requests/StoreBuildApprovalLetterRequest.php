<?php

namespace App\Http\Requests;

use App\Models\BuildApprovalLetter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBuildApprovalLetterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [BuildApprovalLetter::class, $this->route('tower')]);
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::in([
                BuildApprovalLetter::STATUS_APPROVED,
                BuildApprovalLetter::STATUS_DENIED,
            ])],
            'issued_at' => ['nullable', 'date'],
        ];
    }
}
