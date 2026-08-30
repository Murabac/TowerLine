<?php

namespace App\Http\Requests;

use App\Models\District;
use App\Models\SubDistrict;
use App\Models\Tower;
use App\Support\TowerCapacity;
use App\Support\TowerFenceDistance;
use App\Support\TowerLandArea;
use App\Support\TowerPowerSource;
use App\Support\TowerSignalRadius;
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
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'region_id' => $regionRule,
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'sub_district_id' => ['nullable', 'integer', 'exists:sub_districts,id'],
            'city' => ['required', 'string', 'max:255'],
            'land_area_preset' => ['nullable', 'string', Rule::in([...array_keys(TowerLandArea::PRESETS), 'custom'])],
            'land_area_custom' => ['nullable', 'string', 'max:255'],
            'operator_id' => ['required', 'exists:operators,id'],
            'type' => ['required', Rule::in(['guyed', 'monopole', 'rooftop'])],
            'height_m' => ['required', 'numeric', 'min:1', 'max:500'],
            'nearest_school_name' => ['nullable', 'string', 'max:255'],
            'nearest_school_m' => ['nullable', 'integer', 'min:0', 'max:50000'],
            'nearest_hospital_name' => ['nullable', 'string', 'max:255'],
            'nearest_hospital_m' => ['nullable', 'integer', 'min:0', 'max:50000'],
            'nearest_house_name' => ['nullable', 'string', 'max:255'],
            'nearest_house_m' => ['nullable', 'integer', 'min:0', 'max:50000'],
            'fence_distance_preset' => ['nullable', Rule::in([...array_map('strval', TowerFenceDistance::PRESETS), 'custom'])],
            'fence_distance_custom' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'site_map_notes' => ['nullable', 'string', 'max:2000'],
            'site_map' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'capacity' => ['required', 'string', Rule::in(TowerCapacity::OPTIONS)],
            'power_sources' => ['nullable', 'array'],
            'power_sources.*' => ['string', Rule::in(TowerPowerSource::OPTIONS)],
            'signal_radius_m' => ['nullable', 'integer', 'min:100', 'max:100000'],
            'status' => ['required', Rule::in(['active', 'under_construction', 'decommissioned'])],
            'commissioned_at' => ['nullable', 'date'],
            'application_date' => ['nullable', 'date'],
            'registration_inspector_notes' => ['nullable', 'string', 'max:2000'],
            'registration_director_notes' => ['nullable', 'string', 'max:2000'],
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

            if ($this->input('land_area_preset') === 'custom' && ! trim((string) $this->input('land_area_custom'))) {
                $validator->errors()->add('land_area_custom', __('app.towers.land_area_custom_required'));
            }

            if ($this->input('fence_distance_preset') === 'custom' && ! is_numeric($this->input('fence_distance_custom'))) {
                $validator->errors()->add('fence_distance_custom', __('app.towers.fence_distance_custom_required'));
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $capacity = TowerCapacity::normalize($this->input('capacity'));

        $this->merge([
            'power_sources' => TowerPowerSource::normalize($this->input('power_sources', [])),
            'capacity' => $capacity,
            'application_date' => $this->input('application_date') ?: now()->toDateString(),
            'land_area_preset' => $this->input('land_area_preset') ?: '20x20',
            'fence_distance_preset' => $this->input('fence_distance_preset') ?: '6',
            'signal_radius_m' => $this->integer('signal_radius_m') ?: TowerSignalRadius::defaultForCapacity($capacity),
        ]);
    }
}
