<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMinistrySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canTask('settings.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'approval_director_name' => ['nullable', 'string', 'max:255'],
            'approval_director_title_so' => ['required', 'string', 'max:255'],
            'approval_director_title_en' => ['required', 'string', 'max:255'],
        ];
    }
}
