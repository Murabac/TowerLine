<?php

namespace App\Http\Requests;

use App\Support\SomalilandPhone;
use App\Support\SiteRegistrationGuidelines;
use App\Support\TowerCapacity;
use App\Support\TowerFenceDistance;
use App\Support\TowerLandArea;
use App\Support\TowerPowerSource;
use App\Support\TowerProximity;
use App\Support\TowerSignalRadius;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

class StoreSiteApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        app()->setLocale('en');

        $capacity = TowerCapacity::normalize($this->input('capacity'));

        $this->merge([
            'telephone' => SomalilandPhone::format($this->input('telephone')) ?: $this->input('telephone'),
            'power_sources' => TowerPowerSource::normalize($this->input('power_sources', [])),
            'capacity' => $capacity,
            'land_area_preset' => $this->input('land_area_preset') ?: '18x24',
            'fence_distance_preset' => $this->input('fence_distance_preset') ?: '6',
            'signal_radius_m' => $this->integer('signal_radius_m') ?: TowerSignalRadius::defaultForCapacity($capacity),
        ]);
    }

    public function rules(): array
    {
        $regionId = $this->integer('region_id');
        $districtId = $this->integer('district_id');

        return [
            'contact_name' => ['required', 'string', 'max:120'],
            'telephone' => ['required', 'string', 'regex:/^\+252\d{9}$/'],
            'operator_id' => ['required', 'integer', 'exists:operators,id'],
            'license_class_no' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:120'],
            'address' => ['required', 'string', 'max:255'],
            'site_name' => ['required', 'string', 'max:120'],
            'region_id' => ['required', 'integer', 'exists:regions,id'],
            'district_id' => [
                'required',
                'integer',
                Rule::exists('districts', 'id')->where(fn ($query) => $query->where('region_id', $regionId)),
            ],
            'sub_district_id' => [
                'required',
                'integer',
                Rule::exists('sub_districts', 'id')->where(fn ($query) => $query->where('district_id', $districtId)),
            ],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'type' => ['required', Rule::in(['guyed', 'monopole', 'rooftop'])],
            'height_m' => ['required', 'integer', 'min:1', 'max:500'],
            'capacity' => ['required', 'string', Rule::in(TowerCapacity::OPTIONS)],
            'signal_radius_m' => ['nullable', 'integer', 'min:100', 'max:100000'],
            'land_area_preset' => ['nullable', 'string', Rule::in([...array_keys(TowerLandArea::APPLY_PRESETS), 'custom'])],
            'land_area_custom' => ['nullable', 'string', 'max:255'],
            'fence_distance_preset' => ['nullable', Rule::in([...array_map('strval', TowerFenceDistance::PRESETS), 'custom'])],
            'fence_distance_custom' => ['nullable', 'integer', 'min:'.SiteRegistrationGuidelines::MIN_FENCE_M, 'max:500'],
            'power_sources' => ['nullable', 'array'],
            'power_sources.*' => ['string', Rule::in(TowerPowerSource::OPTIONS)],
            'nearest_school_name' => ['nullable', 'string', 'max:255'],
            'nearest_school_m' => ['nullable', 'integer', 'min:'.SiteRegistrationGuidelines::MIN_SENSITIVE_M, 'max:50000'],
            'nearest_hospital_name' => ['nullable', 'string', 'max:255'],
            'nearest_hospital_m' => ['nullable', 'integer', 'min:'.SiteRegistrationGuidelines::MIN_SENSITIVE_M, 'max:50000'],
            'nearest_house_name' => ['nullable', 'string', 'max:255'],
            'nearest_house_m' => ['nullable', 'integer', 'min:'.SiteRegistrationGuidelines::MIN_PUBLIC_M, 'max:50000'],
            'site_map_notes' => ['nullable', 'string', 'max:2000'],
            'letter' => ['required', $this->documentFile()],
            'layout' => ['required', $this->documentFile()],
            'radio' => ['required', $this->documentFile()],
            'icnirp' => ['required', $this->documentFile()],
        ];
    }

    public function messages(): array
    {
        $messages = [];

        foreach (['letter', 'layout', 'radio', 'icnirp'] as $field) {
            $label = $this->attributes()[$field];
            $messages["{$field}.mimes"] = __('app.apply.file_types', ['field' => $label], 'en');
            $messages["{$field}.extensions"] = __('app.apply.file_types', ['field' => $label], 'en');
            $messages["{$field}.max"] = __('app.apply.file_too_large', ['field' => $label], 'en');
        }

        foreach (SiteRegistrationGuidelines::distanceMins() as $field => $metres) {
            $messages["{$field}.min"] = __('app.apply.below_guideline', [
                'field' => $this->attributes()[$field],
                'min' => $metres,
            ], 'en');
        }

        $messages['telephone.regex'] = __('app.apply.telephone_invalid', [], 'en');
        $messages['telephone.required'] = __('app.apply.named_required', ['field' => $this->attributes()['telephone']], 'en');

        $messages['fence_distance_custom.min'] = __('app.apply.below_guideline', [
            'field' => $this->attributes()['fence_distance_custom'],
            'min' => SiteRegistrationGuidelines::MIN_FENCE_M,
        ], 'en');

        $messages['height_m.min'] = __('app.apply.below_guideline', [
            'field' => $this->attributes()['height_m'],
            'min' => SiteRegistrationGuidelines::MIN_HEIGHT_INHABITED_M,
        ], 'en');

        return $messages;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->input('land_area_preset') === 'custom' && ! trim((string) $this->input('land_area_custom'))) {
                $validator->errors()->add('land_area_custom', __('app.towers.land_area_custom_required', [], 'en'));
            } elseif ($this->filled('land_area_preset')
                && ! SiteRegistrationGuidelines::plotMeetsGuideline(
                    $this->input('land_area_preset'),
                    $this->input('land_area_custom')
                )) {
                $field = $this->input('land_area_preset') === 'custom' ? 'land_area_custom' : 'land_area_preset';
                $validator->errors()->add($field, __('app.apply.plot_too_small', [
                    'short' => SiteRegistrationGuidelines::MIN_PLOT_SHORT_M,
                    'long' => SiteRegistrationGuidelines::MIN_PLOT_LONG_M,
                ], 'en'));
            }

            if ($this->input('fence_distance_preset') === 'custom' && ! is_numeric($this->input('fence_distance_custom'))) {
                $validator->errors()->add('fence_distance_custom', __('app.towers.fence_distance_custom_required', [], 'en'));
            }

            if (SiteRegistrationGuidelines::requiresInhabitedHeight($this->input('type'))
                && is_numeric($this->input('height_m'))
                && (float) $this->input('height_m') < SiteRegistrationGuidelines::MIN_HEIGHT_INHABITED_M) {
                $validator->errors()->add('height_m', __('app.apply.below_guideline', [
                    'field' => $this->attributes()['height_m'],
                    'min' => SiteRegistrationGuidelines::MIN_HEIGHT_INHABITED_M,
                ], 'en'));
            }

            if ($this->filled(['latitude', 'longitude', 'operator_id'])) {
                $nearby = TowerProximity::nearbyAt(
                    (float) $this->input('latitude'),
                    (float) $this->input('longitude'),
                    null,
                    SiteRegistrationGuidelines::MIN_SAME_OPERATOR_M,
                    fn ($query) => $query->where('operator_id', $this->integer('operator_id')),
                );

                if ($nearby->isNotEmpty()) {
                    $validator->errors()->add('location', __('app.apply.too_close_own_site', [
                        'min' => SiteRegistrationGuidelines::MIN_SAME_OPERATOR_M,
                    ], 'en'));
                }
            }
        });
    }

    public function attributes(): array
    {
        return [
            'contact_name' => __('app.apply.contact_name', [], 'en'),
            'telephone' => __('app.apply.telephone', [], 'en'),
            'operator_id' => __('app.apply.operator', [], 'en'),
            'license_class_no' => __('app.apply.license_class_no', [], 'en'),
            'email' => __('app.apply.email', [], 'en'),
            'address' => __('app.apply.address', [], 'en'),
            'site_name' => __('app.apply.site_name', [], 'en'),
            'region_id' => __('app.apply.region', [], 'en'),
            'district_id' => __('app.apply.district', [], 'en'),
            'sub_district_id' => __('app.apply.sub_district', [], 'en'),
            'latitude' => __('app.apply.latitude', [], 'en'),
            'longitude' => __('app.apply.longitude', [], 'en'),
            'type' => __('app.towers.type', [], 'en'),
            'height_m' => __('app.towers.form_fields.tower_height', [], 'en'),
            'capacity' => __('app.towers.capacity', [], 'en'),
            'land_area_preset' => __('app.towers.form_fields.land_area', [], 'en'),
            'land_area_custom' => __('app.towers.land_area_custom', [], 'en'),
            'fence_distance_preset' => __('app.towers.form_fields.fence_distance_m', [], 'en'),
            'fence_distance_custom' => __('app.towers.form_fields.fence_distance_m', [], 'en'),
            'power_sources' => __('app.towers.power_source', [], 'en'),
            'nearest_school_name' => __('app.towers.form_fields.nearest_school', [], 'en'),
            'nearest_school_m' => __('app.towers.form_fields.nearest_school', [], 'en'),
            'nearest_hospital_name' => __('app.towers.form_fields.nearest_hospital', [], 'en'),
            'nearest_hospital_m' => __('app.towers.form_fields.nearest_hospital', [], 'en'),
            'nearest_house_name' => __('app.towers.form_fields.nearest_house', [], 'en'),
            'nearest_house_m' => __('app.towers.form_fields.nearest_house', [], 'en'),
            'site_map_notes' => __('app.towers.form_fields.site_map_notes', [], 'en'),
            'letter' => __('app.apply.letter', [], 'en'),
            'layout' => __('app.apply.layout', [], 'en'),
            'radio' => __('app.apply.radio', [], 'en'),
            'icnirp' => __('app.apply.icnirp', [], 'en'),
        ];
    }

    private function documentFile(): File
    {
        return File::types(['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'])->max(10 * 1024);
    }
}
