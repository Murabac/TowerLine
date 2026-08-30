<?php

namespace App\Http\Requests;

use App\Models\District;
use App\Models\SubDistrict;
use App\Models\Tower;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTowerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $tower = $this->route('tower');

        return $tower
            ? $this->user()->can('update', $tower)
            : $this->user()->can('create', Tower::class);
    }

    public function rules(): array
    {
        $regionRule = ['required', 'exists:regions,id'];

        if ($this->user()->isInspector()) {
            $regionRule[] = Rule::in($this->user()->regionIds());
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'region_id' => $regionRule,
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'sub_district_id' => ['nullable', 'integer', 'exists:sub_districts,id'],
            'operator_id' => ['required', 'exists:operators,id'],
            'type' => ['required', Rule::in(['guyed', 'monopole', 'rooftop'])],
            'height_m' => ['required', 'numeric', 'min:1', 'max:500'],
            'capacity' => ['nullable', 'string', 'max:255'],
            'signal_radius_m' => ['required', 'integer', 'min:100', 'max:100000'],
            'status' => ['required', Rule::in(['active', 'under_construction', 'decommissioned'])],
            'commissioned_at' => ['nullable', 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $regionId = $this->integer('region_id');
            $districtId = $this->input('district_id');
            $subDistrictId = $this->input('sub_district_id');

            if ($districtId) {
                $district = District::query()->find($districtId);

                if (! $district || $district->region_id !== $regionId) {
                    $validator->errors()->add('district_id', __('app.geography.district_region_mismatch'));
                }
            }

            if ($subDistrictId) {
                $subDistrict = SubDistrict::query()->with('district')->find($subDistrictId);

                if (! $subDistrict) {
                    return;
                }

                if ($districtId && (int) $subDistrict->district_id !== (int) $districtId) {
                    $validator->errors()->add('sub_district_id', __('app.geography.sub_district_mismatch'));
                }

                if ($subDistrict->district->region_id !== $regionId) {
                    $validator->errors()->add('sub_district_id', __('app.geography.sub_district_region_mismatch'));
                }
            }
        });
    }
}
