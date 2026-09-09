<?php

namespace App\Http\Requests;

use App\Models\SiteApplication;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewSiteApplicationRequest extends FormRequest
{
    use ValidatesDrawnSignature;

    public function authorize(): bool
    {
        $application = $this->route('application');

        return $application instanceof SiteApplication
            && $this->user()?->can('review', $application);
    }

    public function rules(): array
    {
        return array_merge([
            'decision' => ['required', Rule::in([SiteApplication::DECISION_APPROVE, SiteApplication::DECISION_REJECT])],
            'site_visit_on' => ['required', 'date', 'before_or_equal:today'],
            'site_visit_notes' => ['required', 'string', 'max:5000'],
            'officer_remarks' => ['required', 'string', 'max:5000'],
        ], $this->signatureRules());
    }
}
