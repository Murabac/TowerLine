<?php

namespace App\Http\Requests;

use App\Models\ApprovalRequest;
use App\Models\District;
use App\Support\ExistingTowerRegistration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreExistingTowerRequest extends FormRequest
{
    /** Possible duplicates found during validation, shown back on the form. */
    public array $duplicates = [];

    public function authorize(): bool
    {
        $approval = $this->approval();

        return $approval
            ? $this->user()->can('update', $approval)
            : $this->user()->canTask('towers.register_existing');
    }

    public function rules(): array
    {
        $regionRule = ['required', 'integer', 'exists:regions,id'];

        if ($this->user()->requiresRegions()) {
            $regionRule[] = Rule::in($this->user()->regionIds());
        }

        return [
            'operator_id' => ['required', 'integer', 'exists:operators,id'],
            'region_id' => $regionRule,
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'type' => ['required', Rule::in(ExistingTowerRegistration::TYPES)],
            'height_m' => ['required', 'numeric', 'min:1', 'max:500'],
            'site_name' => ['nullable', 'string', 'max:255'],
            'not_duplicate' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'region_id.in' => __('app.existing_towers.region_not_yours'),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->filled('district_id')) {
                $district = District::query()->find($this->integer('district_id'));

                if (! $district || (int) $district->region_id !== $this->integer('region_id')) {
                    $validator->errors()->add('district_id', __('app.geography.district_region_mismatch'));

                    return;
                }
            }

            $this->duplicates = ExistingTowerRegistration::possibleDuplicates(
                (float) $this->input('latitude'),
                (float) $this->input('longitude'),
                $this->approval()?->id,
            );

            if ($this->duplicates !== [] && ! $this->boolean('not_duplicate')) {
                $validator->errors()->add('not_duplicate', __('app.existing_towers.duplicate_confirm_required'));
            }
        });
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        session()->flash('existing_tower_duplicates', $this->duplicates);

        parent::failedValidation($validator);
    }

    private function approval(): ?ApprovalRequest
    {
        $approval = $this->route('approval');

        return $approval instanceof ApprovalRequest ? $approval : null;
    }
}
