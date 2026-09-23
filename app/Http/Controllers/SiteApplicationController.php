<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSiteApplicationRequest;
use App\Models\Operator;
use App\Models\Region;
use App\Models\SiteApplication;
use App\Support\GeographyReference;
use App\Support\SiteApplicationNumberGenerator;
use App\Support\TowerFenceDistance;
use App\Support\TowerLandArea;
use App\Support\TowerPowerSource;
use App\Support\TowerSignalRadius;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SiteApplicationController extends Controller
{
    public function create(): View
    {
        app()->setLocale('en');

        $operators = Operator::query()->orderBy('name')->get();
        $regions = Region::query()->with(['districts.subDistricts'])->orderBy('name_en')->get();

        $geography = $regions->map(function (Region $region) {
            $districts = $region->districts->map(function ($district) {
                $subDistricts = $district->subDistricts->map(fn ($sub) => [
                    'id' => $sub->id,
                    'name' => $sub->name,
                    'bounds' => $sub->mapBounds(),
                ])->values();

                return [
                    'id' => $district->id,
                    'name' => $district->name,
                    'bounds' => GeographyReference::unionBounds($subDistricts->pluck('bounds')),
                    'sub_districts' => $subDistricts,
                ];
            })->values();

            return [
                'id' => $region->id,
                'name' => $region->name_en,
                'bounds' => GeographyReference::unionBounds($districts->pluck('bounds')),
                'districts' => $districts,
            ];
        })->values();

        return view('apply.create', [
            'operators' => $operators,
            'geography' => $geography,
        ]);
    }

    public function store(StoreSiteApplicationRequest $request): RedirectResponse
    {
        app()->setLocale('en');

        $folder = 'site-applications/'.now()->format('YmdHis').'-'.bin2hex(random_bytes(4));
        $paths = [];

        foreach (array_keys(SiteApplication::DOCUMENT_FIELDS) as $field) {
            $paths[SiteApplication::DOCUMENT_FIELDS[$field]] = $request->file($field)->store($folder, 'local');
        }

        $application = SiteApplication::query()->create([
            ...$request->safe()->only([
                'contact_name',
                'telephone',
                'operator_id',
                'license_class_no',
                'email',
                'address',
                'site_name',
                'region_id',
                'district_id',
                'sub_district_id',
                'latitude',
                'longitude',
                'type',
                'height_m',
                'capacity',
                'nearest_school_name',
                'nearest_school_m',
                'nearest_hospital_name',
                'nearest_hospital_m',
                'nearest_house_name',
                'nearest_house_m',
                'site_map_notes',
            ]),
            ...$paths,
            'land_area' => TowerLandArea::resolve($request->input('land_area_preset'), $request->input('land_area_custom')),
            'fence_distance_m' => TowerFenceDistance::resolve($request->input('fence_distance_preset'), $request->input('fence_distance_custom')),
            'power_sources' => TowerPowerSource::normalize($request->input('power_sources', [])),
            'signal_radius_m' => TowerSignalRadius::defaultForCapacity($request->input('capacity')),
            'reference_number' => SiteApplicationNumberGenerator::generate(),
            'status' => SiteApplication::STATUS_RECEIVED,
            'ip_address' => $request->ip(),
        ]);

        $request->session()->put('application_id', $application->id);

        return redirect()->route('apply.received');
    }

    public function received(): View|RedirectResponse
    {
        app()->setLocale('en');

        $id = session('application_id');

        if (! $id) {
            return redirect()->route('apply.create');
        }

        $application = SiteApplication::query()->with(['operator', 'region', 'district'])->findOrFail($id);

        return view('apply.received', compact('application'));
    }
}
