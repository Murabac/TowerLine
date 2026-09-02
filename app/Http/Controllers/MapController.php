<?php

namespace App\Http\Controllers;

use App\Models\District;
use App\Models\Operator;
use App\Models\Region;
use App\Models\SubDistrict;
use App\Models\Tower;
use App\Support\GeographyReference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class MapController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Tower::class);
        abort_unless($request->user()->canTask('map.view'), 403);

        $user = $request->user();

        $regions = Region::query()->orderBy('name_en');
        $operators = Operator::query()->with('regions')->orderBy('name');

        if ($user->requiresRegions()) {
            $regions->whereIn('id', $user->regionIds() ?: [0]);
        }

        if ($user->isOperatorViewer()) {
            $operators->whereKey($user->operator_id);
        }

        $regionId = $request->integer('region_id') ?: null;
        $districtId = $request->integer('district_id') ?: null;

        $districts = $regionId
            ? District::query()->where('region_id', $regionId)->orderBy('name')->get()
            : collect();

        $subDistricts = $districtId
            ? District::query()->find($districtId)?->subDistricts()->orderBy('name')->get() ?? collect()
            : collect();

        $operatorCollection = $operators->get();

        if ($regionId) {
            $operatorCollection = $operatorCollection
                ->filter(fn (Operator $operator) => $operator->servesRegion($regionId))
                ->values();
        }

        return view('map.index', [
            'regions' => $regions->get(),
            'districts' => $districts,
            'subDistricts' => $subDistricts,
            'operators' => $operatorCollection,
            'operatorOptions' => Operator::query()->with('regions')->orderBy('name')->get()->map->toFormOption()->values(),
            'filters' => $request->only(['region_id', 'district_id', 'sub_district_id', 'operator_id', 'category', 'status', 'license_state', 'health_status', 'overdue', 'power_source']),
        ]);
    }

    public function towers(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Tower::class);
        abort_unless($request->user()->canTask('map.view'), 403);

        $query = Tower::query()
            ->visibleTo($request->user())
            ->with(['operator', 'region', 'district', 'subDistrict', 'latestInspection', 'currentLicense', 'currentApprovalLetter']);

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

        if ($request->filled('category')) {
            $query->whereHas('operator', fn ($operators) => $operators->where('category', $request->string('category')));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('health_status')) {
            $query->where('health_status', $request->string('health_status'));
        }

        if ($request->filled('power_source')) {
            $query->withPowerSource($request->string('power_source')->toString());
        }

        if ($request->boolean('overdue')) {
            $query->inspectionOverdue();
        }

        if ($request->filled('license_state')) {
            $state = $request->string('license_state')->toString();

            if ($state === 'none') {
                $query->whereDoesntHave('currentLicense');
            } else {
                $query->whereHas('currentLicense', function ($licenses) use ($state) {
                    if ($state === 'expired') {
                        $licenses->whereDate('expires_at', '<', now()->toDateString());
                    } elseif ($state === 'expiring_soon') {
                        $licenses->whereDate('expires_at', '>=', now()->toDateString())
                            ->whereDate('expires_at', '<=', now()->addDays(30)->toDateString());
                    } elseif ($state === 'active') {
                        $licenses->whereDate('expires_at', '>', now()->addDays(30)->toDateString());
                    }
                });
            }
        }

        $towers = $query->get()->map(function (Tower $tower) {
            $license = $tower->currentLicense;
            $approvalLetter = $tower->currentApprovalLetter;

            return [
                'id' => $tower->id,
                'name' => $tower->name,
                'lat' => (float) $tower->latitude,
                'lng' => (float) $tower->longitude,
                'status' => $tower->status,
                'health_status' => $tower->health_status,
                'color' => $tower->statusColor(),
                'signal_radius_m' => $tower->signal_radius_m,
                'type' => $tower->type,
                'operator' => [
                    'name' => $tower->operator->name,
                    'color' => $tower->operator->color,
                    'category' => $tower->operator->category,
                ],
                'region' => $tower->region->localizedName(),
                'district' => $tower->district?->localizedName(),
                'sub_district' => $tower->subDistrict?->localizedName(),
                'power_sources' => $tower->powerSourceKeys(),
                'power_sources_label' => $tower->powerSourceLabel(),
                'last_inspection_at' => $tower->latestInspection?->inspected_at?->toDateString(),
                'overdue' => $tower->isInspectionOverdue(),
                'license' => $license ? [
                    'type' => $license->license_type,
                    'expires_at' => $license->expires_at->toDateString(),
                    'status' => $license->display_status,
                    'status_label' => __('app.status.'.$license->display_status),
                ] : null,
                'approval_letter' => $approvalLetter ? [
                    'reference_number' => $approvalLetter->reference_number,
                    'issued_at' => $approvalLetter->issued_at->toDateString(),
                    'status' => $approvalLetter->status,
                    'url' => route('towers.approval-letter.show', $tower),
                ] : null,
                'url' => route('towers.show', $tower),
            ];
        });

        $bounds = $this->boundsFromTowers($towers)
            ?? $this->geographyBounds($request);

        return response()->json([
            'towers' => $towers,
            'bounds' => $bounds,
        ]);
    }

    /**
     * @param  Collection<int, array{lat: float, lng: float}>  $towers
     * @return array{south: float, west: float, north: float, east: float}|null
     */
    private function boundsFromTowers(Collection $towers): ?array
    {
        if ($towers->isEmpty()) {
            return null;
        }

        return [
            'south' => (float) $towers->min('lat'),
            'west' => (float) $towers->min('lng'),
            'north' => (float) $towers->max('lat'),
            'east' => (float) $towers->max('lng'),
        ];
    }

    /**
     * @return array{south: float, west: float, north: float, east: float}|null
     */
    private function geographyBounds(Request $request): ?array
    {
        if (! $request->filled('region_id') && ! $request->filled('district_id') && ! $request->filled('sub_district_id')) {
            return null;
        }

        $query = Tower::query()
            ->visibleTo($request->user())
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        if ($request->filled('sub_district_id')) {
            $query->where('sub_district_id', $request->integer('sub_district_id'));
        } elseif ($request->filled('district_id')) {
            $query->where('district_id', $request->integer('district_id'));
        } else {
            $query->where('region_id', $request->integer('region_id'));
        }

        $coordinates = $query->get(['latitude', 'longitude']);

        if ($coordinates->isEmpty() && $request->filled('sub_district_id')) {
            $subDistrict = SubDistrict::query()->find($request->integer('sub_district_id'));

            if ($subDistrict) {
                $centroidBounds = GeographyReference::boundsForSubDistrict($subDistrict);

                if ($centroidBounds) {
                    return $centroidBounds;
                }

                $coordinates = Tower::query()
                    ->visibleTo($request->user())
                    ->where('district_id', $subDistrict->district_id)
                    ->whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->get(['latitude', 'longitude']);
            }
        }

        if ($coordinates->isEmpty() && $request->filled('district_id')) {
            $district = District::query()->find($request->integer('district_id'));

            if ($district) {
                $coordinates = Tower::query()
                    ->visibleTo($request->user())
                    ->where('region_id', $district->region_id)
                    ->whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->get(['latitude', 'longitude']);
            }
        }

        if ($coordinates->isEmpty()) {
            return null;
        }

        return [
            'south' => (float) $coordinates->min('latitude'),
            'west' => (float) $coordinates->min('longitude'),
            'north' => (float) $coordinates->max('latitude'),
            'east' => (float) $coordinates->max('longitude'),
        ];
    }
}
