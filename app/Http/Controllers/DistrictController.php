<?php

namespace App\Http\Controllers;

use App\Models\District;
use App\Models\Region;
use App\Models\SubDistrict;
use App\Support\Audits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DistrictController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', District::class);

        $regions = Region::query()
            ->withCount('districts')
            ->orderBy('name_en')
            ->get();

        $selectedRegionId = $request->integer('region_id') ?: null;
        $search = trim($request->string('q')->toString());

        $districts = collect();

        if ($selectedRegionId) {
            $districts = District::query()
                ->where('region_id', $selectedRegionId)
                ->with('region')
                ->withCount(['subDistricts', 'towers'])
                ->withCount([
                    'subDistricts as mapped_sub_districts_count' => fn ($query) => $query->whereNotNull('bounds_south'),
                ])
                ->when($search !== '', function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%');
                })
                ->orderBy('name')
                ->get();
        }

        return view('districts.index', [
            'regions' => $regions,
            'districts' => $districts,
            'selectedRegionId' => $selectedRegionId,
            'search' => $search,
        ]);
    }

    public function edit(District $district): View
    {
        $this->authorize('update', $district);

        $district->load([
            'region',
            'subDistricts' => fn ($query) => $query->orderBy('name'),
            'towers' => fn ($query) => $query
                ->select(['id', 'district_id', 'latitude', 'longitude', 'name'])
                ->whereNotNull('latitude')
                ->whereNotNull('longitude'),
        ]);

        return view('districts.edit', [
            'district' => $district,
            'mapCenter' => $this->mapCenterForDistrict($district),
            'subDistrictPayload' => $district->subDistricts->map(fn (SubDistrict $subDistrict) => [
                'id' => $subDistrict->id,
                'name' => $subDistrict->name,
                'label' => $subDistrict->name,
                'has_area' => $subDistrict->hasMapArea(),
                'bounds' => $subDistrict->mapBounds(),
            ])->values(),
            'towerPayload' => $district->towers->map(fn ($tower) => [
                'name' => $tower->name,
                'lat' => (float) $tower->latitude,
                'lng' => (float) $tower->longitude,
            ])->values(),
        ]);
    }

    public function update(Request $request, District $district): RedirectResponse
    {
        $this->authorize('update', $district);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'grade' => ['nullable', 'string', 'max:1'],
        ]);

        $district->update($validated);

        return redirect()
            ->route('districts.edit', $district)
            ->with('status', __('app.geography.district_updated'));
    }

    public function storeSubDistrict(Request $request, District $district): RedirectResponse
    {
        $this->authorize('update', $district);

        $validated = $this->validateSubDistrict($request);

        $subDistrict = $district->subDistricts()->create([
            'name' => $validated['name'],
        ]);

        $this->applyArea($subDistrict, $validated);

        return redirect()
            ->route('districts.edit', $district)
            ->with('status', __('app.geography.sub_district_created'));
    }

    public function updateSubDistrict(Request $request, District $district, SubDistrict $subDistrict): RedirectResponse
    {
        $this->authorize('update', $district);

        abort_unless($subDistrict->district_id === $district->id, 404);

        $validated = $this->validateSubDistrict($request);

        $subDistrict->update([
            'name' => $validated['name'],
        ]);

        $this->applyArea($subDistrict, $validated);

        return redirect()
            ->route('districts.edit', ['district' => $district, 'sub_district' => $subDistrict->id])
            ->with('status', __('app.geography.sub_district_updated'));
    }

    public function destroy(District $district): RedirectResponse
    {
        $this->authorize('delete', $district);

        $regionId = $district->region_id;
        $name = $district->name;

        Audits::log('deleted', $district, [
            'name' => $district->name,
            'sub_districts_count' => $district->subDistricts()->count(),
            'towers_count' => $district->towers()->count(),
        ]);

        $district->delete();

        return redirect()
            ->route('districts.index', ['region_id' => $regionId])
            ->with('status', __('app.geography.district_deleted', ['name' => $name]));
    }

    public function destroySubDistrict(District $district, SubDistrict $subDistrict): RedirectResponse
    {
        $this->authorize('delete', $district);

        abort_unless($subDistrict->district_id === $district->id, 404);

        $name = $subDistrict->name;

        Audits::log('deleted', $subDistrict, [
            'name' => $subDistrict->name,
            'district_id' => $district->id,
            'towers_count' => $subDistrict->towers()->count(),
        ]);

        $subDistrict->delete();

        return redirect()
            ->route('districts.edit', $district)
            ->with('status', __('app.geography.sub_district_deleted', ['name' => $name]));
    }

    /**
     * @return array{lat: float, lng: float, zoom: int}
     */
    private function mapCenterForDistrict(District $district): array
    {
        $bounds = [];

        foreach ($district->subDistricts as $subDistrict) {
            $area = $subDistrict->mapBounds();

            if ($area) {
                $bounds[] = $area;
            }
        }

        if ($bounds !== []) {
            return [
                'lat' => (min(array_column($bounds, 'south')) + max(array_column($bounds, 'north'))) / 2,
                'lng' => (min(array_column($bounds, 'west')) + max(array_column($bounds, 'east'))) / 2,
                'zoom' => 11,
            ];
        }

        $towers = $district->towers;

        if ($towers->isNotEmpty()) {
            return [
                'lat' => (float) $towers->avg('latitude'),
                'lng' => (float) $towers->avg('longitude'),
                'zoom' => 11,
            ];
        }

        return ['lat' => 9.562, 'lng' => 44.077, 'zoom' => 7];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateSubDistrict(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'clear_area' => ['sometimes', 'boolean'],
            'bounds_south' => ['nullable', 'numeric', 'between:-90,90', 'required_with:bounds_west,bounds_north,bounds_east'],
            'bounds_west' => ['nullable', 'numeric', 'between:-180,180', 'required_with:bounds_south,bounds_north,bounds_east'],
            'bounds_north' => ['nullable', 'numeric', 'between:-90,90', 'required_with:bounds_south,bounds_west,bounds_east'],
            'bounds_east' => ['nullable', 'numeric', 'between:-180,180', 'required_with:bounds_south,bounds_west,bounds_north'],
        ]);

        if ($request->boolean('clear_area')) {
            return $validated;
        }

        if (isset($validated['bounds_south'], $validated['bounds_north'])
            && (float) $validated['bounds_north'] <= (float) $validated['bounds_south']) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'bounds_north' => __('app.geography.bounds_invalid'),
            ]);
        }

        if (isset($validated['bounds_west'], $validated['bounds_east'])
            && (float) $validated['bounds_east'] <= (float) $validated['bounds_west']) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'bounds_east' => __('app.geography.bounds_invalid'),
            ]);
        }

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function applyArea(SubDistrict $subDistrict, array $validated): void
    {
        if (! empty($validated['clear_area'])) {
            $subDistrict->clearMapArea();
            $subDistrict->save();

            return;
        }

        if (! isset(
            $validated['bounds_south'],
            $validated['bounds_west'],
            $validated['bounds_north'],
            $validated['bounds_east'],
        )) {
            return;
        }

        $subDistrict->applyMapBounds([
            'south' => (float) $validated['bounds_south'],
            'west' => (float) $validated['bounds_west'],
            'north' => (float) $validated['bounds_north'],
            'east' => (float) $validated['bounds_east'],
        ]);
        $subDistrict->save();
    }
}
