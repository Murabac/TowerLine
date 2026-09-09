<?php

namespace App\Http\Requests;

use App\Models\SiteApplication;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GrantSiteApplicationRequest extends FormRequest
{
    use ValidatesDrawnSignature;

    public function authorize(): bool
    {
        $application = $this->route('application');

        return $application instanceof SiteApplication
            && $this->user()?->can('grant', $application);
    }

    public function rules(): array
    {
        return array_merge([
            'decision' => ['required', Rule::in([SiteApplication::DECISION_GRANT, SiteApplication::DECISION_RETURN])],
            'dg_remarks' => ['required', 'string', 'max:5000'],
            'dg_name' => ['required', 'string', 'max:120'],
        ], $this->signatureRules());
    }
}
