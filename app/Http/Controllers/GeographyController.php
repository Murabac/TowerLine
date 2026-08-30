<?php

namespace App\Http\Controllers;

use App\Models\District;
use App\Models\SubDistrict;
use App\Support\TowerCityOptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GeographyController extends Controller
{
    public function districts(Request $request): JsonResponse
    {
        $this->authorize('viewAny', District::class);

        $request->validate([
            'region_id' => ['required', 'integer', 'exists:regions,id'],
        ]);

        $user = $request->user();

        if ($user->isInspector() && ! in_array($request->integer('region_id'), $user->regionIds(), true)) {
            abort(403);
        }

        $districts = District::query()
            ->where('region_id', $request->integer('region_id'))
            ->orderBy('name')
            ->get()
            ->map(fn (District $district) => [
                'id' => $district->id,
                'name' => $district->name,
            ]);

        return response()->json(['districts' => $districts]);
    }

    public function subDistricts(Request $request): JsonResponse
    {
        $this->authorize('viewAny', District::class);

        $request->validate([
            'district_id' => ['required', 'integer', 'exists:districts,id'],
        ]);

        $district = District::query()->findOrFail($request->integer('district_id'));
        $user = $request->user();

        if ($user->isInspector() && ! in_array($district->region_id, $user->regionIds(), true)) {
            abort(403);
        }

        $subDistricts = SubDistrict::query()
            ->where('district_id', $district->id)
            ->orderBy('name')
            ->get()
            ->map(fn (SubDistrict $subDistrict) => [
                'id' => $subDistrict->id,
                'name' => $subDistrict->name,
            ]);

        return response()->json(['sub_districts' => $subDistricts]);
    }

    public function cities(Request $request): JsonResponse
    {
        $this->authorize('viewAny', District::class);

        $request->validate([
            'district_id' => ['required', 'integer', 'exists:districts,id'],
        ]);

        $district = District::query()->findOrFail($request->integer('district_id'));
        $user = $request->user();

        if ($user->isInspector() && ! in_array($district->region_id, $user->regionIds(), true)) {
            abort(403);
        }

        return response()->json([
            'cities' => TowerCityOptions::forDistrict($district),
        ]);
    }
}
