<?php

namespace App\Http\Requests;

use App\Models\SiteApplication;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConcurSiteApplicationRequest extends FormRequest
{
    use ValidatesDrawnSignature;

    public function authorize(): bool
    {
        $application = $this->route('application');

        return $application instanceof SiteApplication
            && $this->user()?->can('concur', $application);
    }

    public function rules(): array
    {
        return array_merge([
            'decision' => ['required', Rule::in([SiteApplication::DECISION_YES, SiteApplication::DECISION_NO])],
            'director_remarks' => ['required', 'string', 'max:5000'],
            'director_name' => ['required', 'string', 'max:120'],
        ], $this->signatureRules());
    }
}
