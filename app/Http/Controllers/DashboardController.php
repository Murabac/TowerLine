<?php

namespace App\Http\Controllers;

use App\Models\License;
use App\Models\Tower;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $towers = Tower::query()->visibleTo($user);
        $licenses = License::query()->visibleTo($user)->with('tower');

        $totalTowers = (clone $towers)->count();
        $critical = (clone $towers)->where('health_status', 'critical')->count();
        $needingInspection = (clone $towers)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->where('health_status', 'unknown')
                    ->orWhereDoesntHave('inspections', function ($inspections) {
                        $inspections->where('inspected_at', '>=', now()->subDays(90));
                    });
            })
            ->count();

        $expiringLicenses = (clone $licenses)->expiringSoon()->with(['tower', 'operator'])->orderBy('expires_at')->get();
        $expiredLicenses = (clone $licenses)->expired()->count();

        $mapTowers = (clone $towers)
            ->with(['operator', 'region', 'latestInspection', 'currentLicense'])
            ->get();

        return view('dashboard', [
            'totalTowers' => $totalTowers,
            'critical' => $critical,
            'needingInspection' => $needingInspection,
            'expiringLicenses' => $expiringLicenses,
            'expiredLicenses' => $expiredLicenses,
            'mapTowers' => $mapTowers,
        ]);
    }
}
