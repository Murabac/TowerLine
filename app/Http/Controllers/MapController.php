<?php

namespace App\Http\Controllers;

use App\Models\Operator;
use App\Models\Region;
use App\Models\Tower;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MapController extends Controller
{
    public function index(Request $request): View
    {
        return view('map.index', [
            'regions' => Region::query()->orderBy('name_en')->get(),
            'operators' => Operator::query()->orderBy('name')->get(),
            'filters' => $request->only(['region_id', 'operator_id', 'category', 'status', 'license_state', 'health_status']),
        ]);
    }

    public function towers(Request $request): JsonResponse
    {
        $query = Tower::query()
            ->visibleTo($request->user())
            ->with(['operator', 'region', 'latestInspection', 'currentLicense']);

        if ($request->filled('region_id')) {
            $query->where('region_id', $request->integer('region_id'));
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

        if ($request->filled('license_state')) {
            $state = $request->string('license_state')->toString();
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

        $towers = $query->get()->map(function (Tower $tower) {
            $license = $tower->currentLicense;

            return [
                'id' => $tower->id,
                'name' => $tower->name,
                'lat' => $tower->latitude,
                'lng' => $tower->longitude,
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
                'last_inspection_at' => $tower->latestInspection?->inspected_at?->toDateString(),
                'license' => $license ? [
                    'type' => $license->license_type,
                    'expires_at' => $license->expires_at->toDateString(),
                    'status' => $license->display_status,
                ] : null,
                'url' => route('towers.show', $tower),
            ];
        });

        return response()->json(['towers' => $towers]);
    }
}
