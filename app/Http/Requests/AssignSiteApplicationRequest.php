<?php

namespace App\Http\Requests;

use App\Models\SiteApplication;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AssignSiteApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $application = $this->route('application');

        return $application instanceof SiteApplication
            && $this->user()?->can('assign', $application);
    }

    public function rules(): array
    {
        return [
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $application = $this->route('application');

            if (! $application instanceof SiteApplication) {
                return;
            }

            $assignee = User::query()->with('regions')->find($this->integer('assigned_to'));

            if (! $assignee || ! SiteApplication::assigneesForRegion((int) $application->region_id)->contains('id', $assignee->id)) {
                $validator->errors()->add('assigned_to', __('app.applications.assignee_invalid'));
            }
        });
    }
}
