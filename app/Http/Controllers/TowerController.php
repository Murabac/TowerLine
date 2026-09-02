<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTowerRequest;
use App\Models\District;
use App\Models\Operator;
use App\Models\Region;
use App\Models\Tower;
use App\Support\Audits;
use App\Support\InspectorApprovals;
use App\Support\TowerAmenityProximity;
use App\Support\TowerFenceDistance;
use App\Support\TowerLandArea;
use App\Support\TowerNameGenerator;
use App\Support\TowerPowerSource;
use App\Support\TowerProximity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TowerController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Tower::class);

        $query = Tower::query()
            ->visibleTo($request->user())
            ->with(['region', 'district', 'subDistrict', 'operator', 'latestInspection'])
            ->orderBy('name');

        if ($request->filled('q')) {
            $query->where('name', 'like', '%'.$request->string('q').'%');
        }

        if ($request->filled('region_id')) {
            $query->where('region_id', $request->integer('region_id'));
        }

        if ($request->filled('district_id')) {
            $query->where('district_id', $request->integer('district_id'));
        }

        if ($request->filled('sub_district_id')) {
            $query->where('sub_district_id', $request->integer('sub_district_id'));
        }

        if ($request->filled('operator_id')) {
            $query->where('operator_id', $request->integer('operator_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('power_source')) {
            $query->withPowerSource($request->string('power_source')->toString());
        }

        return view('towers.index', [
            'towers' => $query->paginate(15)->withQueryString(),
            'regions' => $this->scopedRegions(),
            'operators' => Operator::query()->with('regions')->orderBy('name')->get(),
            'powerSources' => TowerPowerSource::OPTIONS,
            'initialDistricts' => $this->districtOptionsForRegion($request->integer('region_id') ?: null),
            'initialSubDistricts' => $this->subDistrictOptionsForDistrict($request->integer('district_id') ?: null),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Tower::class);

        return view('towers.create', $this->formData());
    }

    public function locationPreview(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Tower::class);

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'except_tower_id' => ['nullable', 'integer', 'exists:towers,id'],
        ]);

        $latitude = (float) $validated['latitude'];
        $longitude = (float) $validated['longitude'];
        $exceptTowerId = $validated['except_tower_id'] ?? null;
        $amenities = TowerAmenityProximity::detect($latitude, $longitude);

        $nearby = TowerProximity::nearbyAt(
            $latitude,
            $longitude,
            $exceptTowerId,
            scopeQuery: fn ($query) => $query->visibleTo($request->user()),
        )->map(fn (array $item) => [
            'id' => $item['tower']->id,
            'name' => $item['tower']->name,
            'operator' => $item['tower']->operator->name,
            'distance_m' => $item['distance_m'],
            'url' => route('towers.show', $item['tower']),
        ])->values();

        return response()->json([
            'school' => $amenities['school'],
            'hospital' => $amenities['hospital'],
            'house' => $amenities['house'],
            'nearby_towers' => $nearby,
            'nearby_radius_m' => TowerProximity::NEARBY_RADIUS_METERS,
        ]);
    }

    public function store(StoreTowerRequest $request): RedirectResponse
    {
        $attributes = $this->towerAttributes($request);

        if (! $request->user()->publishesDirectly()) {
            InspectorApprovals::submitTowerCreate($request->user(), $attributes, $request->file('site_map'));

            return redirect()->route('towers.index')->with('status', __('app.approvals.submitted_tower_create'));
        }

        $tower = Tower::query()->create($attributes);
        $this->storeSiteMapFile($request, $tower);
        Audits::log('created', $tower, $tower->only(['name', 'region_id', 'district_id', 'sub_district_id', 'operator_id', 'status']));

        return redirect()->route('towers.show', $tower)->with('status', __('app.towers.created'));
    }

    public function show(Tower $tower): View
    {
        $this->authorize('view', $tower);

        $tower->load([
            'region',
            'district',
            'subDistrict',
            'operator',
            'inspections.inspector',
            'licenses.operator',
            'currentApprovalLetter.issuer',
            'pendingApprovalRequests.submitter',
        ]);

        return view('towers.show', compact('tower'));
    }

    public function edit(Tower $tower): View
    {
        $this->authorize('update', $tower);

        return view('towers.edit', array_merge($this->formData($tower), compact('tower')));
    }

    public function update(StoreTowerRequest $request, Tower $tower): RedirectResponse
    {
        $this->authorize('update', $tower);

        $attributes = $this->towerAttributes($request, $tower);

        if (! $request->user()->publishesDirectly()) {
            InspectorApprovals::submitTowerUpdate($request->user(), $tower, $attributes, $request->file('site_map'));

            return redirect()->route('towers.show', $tower)->with('status', __('app.approvals.submitted_tower_update'));
        }

        $before = $tower->only(['name', 'status', 'latitude', 'longitude', 'district_id', 'sub_district_id']);
        $tower->update($attributes);
        $this->storeSiteMapFile($request, $tower);
        Audits::log('updated', $tower, ['before' => $before, 'after' => $tower->only(array_keys($before))]);

        return redirect()->route('towers.show', $tower)->with('status', __('app.towers.updated'));
    }

    public function destroy(Tower $tower): RedirectResponse
    {
        $this->authorize('delete', $tower);

        if ($tower->site_map_path) {
            Storage::disk('public')->delete($tower->site_map_path);
        }

        Audits::log('deleted', $tower, ['name' => $tower->name]);
        $tower->delete();

        return redirect()->route('towers.index')->with('status', __('app.towers.deleted'));
    }

    public function formData(?Tower $tower = null): array
    {
        $user = request()->user();
        $districtId = old('district_id', $tower?->district_id);

        return [
            'regions' => $this->scopedRegions(),
            'operators' => Operator::query()->with('regions')->orderBy('name')->get(),
            'operatorOptions' => Operator::query()->with('regions')->orderBy('name')->get()->map->toFormOption()->values(),
            'powerSources' => TowerPowerSource::OPTIONS,
            'initialDistricts' => $this->districtOptionsForRegion(
                old('region_id', $tower?->region_id ?? $user->regionIds()[0] ?? null)
            ),
            'initialSubDistricts' => $this->subDistrictOptionsForDistrict($districtId),
            'initialCities' => $districtId
                ? \App\Support\TowerCityOptions::forDistrict(District::query()->find($districtId))
                : [],
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, Region>
     */
    private function scopedRegions()
    {
        $user = request()->user();
        $regions = Region::query()->orderBy('name_en')->get();

        if ($user->isInspector()) {
            return $regions->whereIn('id', $user->regionIds() ?: [0])->values();
        }

        return $regions;
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function districtOptionsForRegion(mixed $regionId): array
    {
        if (! $regionId) {
            return [];
        }

        return District::query()
            ->where('region_id', $regionId)
            ->orderBy('name')
            ->get()
            ->map(fn (District $district) => ['id' => $district->id, 'name' => $district->name])
            ->all();
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function subDistrictOptionsForDistrict(mixed $districtId): array
    {
        if (! $districtId) {
            return [];
        }

        return District::query()
            ->find($districtId)
            ?->subDistricts()
            ->orderBy('name')
            ->get()
            ->map(fn ($subDistrict) => ['id' => $subDistrict->id, 'name' => $subDistrict->name])
            ->all() ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function towerAttributes(StoreTowerRequest $request, ?Tower $tower = null): array
    {
        $validated = $request->validated();
        $latitude = (float) $validated['latitude'];
        $longitude = (float) $validated['longitude'];
        $status = $validated['status'];

        $nearbyTowers = TowerProximity::nearbyAt(
            $latitude,
            $longitude,
            $tower?->id,
            scopeQuery: fn ($query) => $query->visibleTo($request->user()),
        );

        $attributes = [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'region_id' => $validated['region_id'],
            'district_id' => $validated['district_id'] ?? null,
            'sub_district_id' => $validated['sub_district_id'] ?? null,
            'city' => $validated['city'],
            'land_area' => TowerLandArea::resolve(
                $request->input('land_area_preset'),
                $request->input('land_area_custom'),
            ),
            'operator_id' => $validated['operator_id'],
            'type' => $validated['type'],
            'height_m' => $validated['height_m'],
            'nearest_school_name' => $validated['nearest_school_name'] ?? null,
            'nearest_school_m' => $validated['nearest_school_m'] ?? null,
            'nearest_hospital_name' => $validated['nearest_hospital_name'] ?? null,
            'nearest_hospital_m' => $validated['nearest_hospital_m'] ?? null,
            'nearest_house_name' => $validated['nearest_house_name'] ?? null,
            'nearest_house_m' => $validated['nearest_house_m'] ?? null,
            'other_towers_nearby' => TowerProximity::formatSummary($nearbyTowers),
            'fence_distance_m' => TowerFenceDistance::resolve(
                $request->input('fence_distance_preset'),
                $request->input('fence_distance_custom'),
            ),
            'site_map_notes' => $validated['site_map_notes'] ?? null,
            'capacity' => $validated['capacity'],
            'power_sources' => $validated['power_sources'] ?? [],
            'signal_radius_m' => $validated['signal_radius_m'],
            'status' => $status,
            'application_date' => $validated['application_date'] ?? null,
            'registration_inspector_notes' => $validated['registration_inspector_notes'] ?? null,
            'registration_director_notes' => $validated['registration_director_notes'] ?? null,
            'name' => TowerNameGenerator::generate(
                operatorId: $request->integer('operator_id'),
                regionId: $request->integer('region_id'),
                city: $request->input('city'),
                districtId: $request->integer('district_id') ?: null,
                subDistrictId: $request->integer('sub_district_id') ?: null,
                exceptTowerId: $tower?->id,
            ),
        ];

        if ($status === 'active' && empty($validated['commissioned_at'])) {
            $attributes['commissioned_at'] = now()->toDateString();
        } elseif (! empty($validated['commissioned_at'])) {
            $attributes['commissioned_at'] = $validated['commissioned_at'];
        } elseif ($tower) {
            $attributes['commissioned_at'] = $tower->commissioned_at;
        }

        return $attributes;
    }

    private function storeSiteMapFile(StoreTowerRequest $request, Tower $tower): void
    {
        $file = $request->file('site_map');

        if (! $file instanceof UploadedFile) {
            return;
        }

        if ($tower->site_map_path) {
            Storage::disk('public')->delete($tower->site_map_path);
        }

        $tower->update([
            'site_map_path' => $file->store("towers/{$tower->id}", 'public'),
        ]);
    }
}
