<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequest;
use App\Models\FrequencyAllocation;
use App\Models\License;
use App\Models\SiteApplication;
use App\Models\Tower;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $towers = Tower::query()->visibleTo($user);
        $licenses = License::query()->visibleTo($user);
        $frequencies = FrequencyAllocation::query()->visibleTo($user);

        $preview = Tower::query()
            ->visibleTo($user)
            ->with('operator')
            ->orderBy('name')
            ->get(['id', 'name', 'latitude', 'longitude', 'status', 'health_status', 'operator_id']);

        return view('dashboard', [
            'towerCount' => (clone $towers)->count(),
            'criticalCount' => (clone $towers)->where('health_status', 'critical')->count(),
            'attentionCount' => (clone $towers)->where('health_status', 'needs_attention')->count(),
            'overdueCount' => (clone $towers)->inspectionOverdue()->count(),
            'expiringCount' => (clone $licenses)->expiringSoon()->count(),
            'expiredCount' => (clone $licenses)->expired()->count(),
            'frequencyExpiringCount' => $user->canAccessGroup('frequencies')
                ? (clone $frequencies)->expiringSoon()->count()
                : 0,
            'frequencyExpiredCount' => $user->canAccessGroup('frequencies')
                ? (clone $frequencies)->expired()->count()
                : 0,
            'pendingApprovalCount' => ($user->canTask('approvals.review') || $user->isInspector())
                ? ApprovalRequest::query()->visibleTo($user)->pending()->count()
                : 0,
            'pendingApprovalIsOwn' => $user->isInspector() && ! $user->canTask('approvals.review'),
            'pendingApplicationCount' => $user->canTask('applications.assign')
                ? SiteApplication::query()->whereIn('status', [SiteApplication::STATUS_RECEIVED, SiteApplication::STATUS_RETURNED])->count()
                : 0,
            'pendingOfficerReviewCount' => $user->canTask('applications.review') && ! $user->canTask('applications.assign')
                ? SiteApplication::query()->visibleTo($user)->where('status', SiteApplication::STATUS_ASSIGNED)->count()
                : 0,
            'pendingDirectorReviewCount' => $user->canTask('applications.concur') && ! $user->canTask('applications.assign')
                ? SiteApplication::query()->where('status', SiteApplication::STATUS_DIRECTOR_REVIEW)->count()
                : 0,
            'pendingDgReviewCount' => $user->canTask('applications.grant') && ! $user->canTask('applications.assign')
                ? SiteApplication::query()->where('status', SiteApplication::STATUS_DG_REVIEW)->count()
                : 0,
            'alerts' => Tower::query()
                ->visibleTo($user)
                ->with(['region', 'operator'])
                ->where(function ($query) {
                    $query->whereIn('health_status', ['critical', 'needs_attention'])
                        ->orWhere(fn ($overdue) => $overdue->inspectionOverdue());
                })
                ->limit(20)
                ->get()
                ->sortBy(fn (Tower $tower) => match ($tower->health_status) {
                    'critical' => 0,
                    'needs_attention' => 1,
                    default => 2,
                })
                ->take(8)
                ->values(),
            'previewTowers' => $preview->map(fn (Tower $tower) => [
                'name' => $tower->name,
                'lat' => (float) $tower->latitude,
                'lng' => (float) $tower->longitude,
                'color' => $tower->statusColor(),
                'url' => route('towers.show', $tower),
            ]),
        ]);
    }
}
