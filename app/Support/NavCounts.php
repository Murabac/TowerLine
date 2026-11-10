<?php

namespace App\Support;

use App\Models\ApprovalRequest;
use App\Models\Complaint;
use App\Models\SiteApplication;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class NavCounts
{
    public static function approvals(User $user): int
    {
        return self::remember($user, 'approvals', fn () => ApprovalRequest::query()
            ->visibleTo($user)
            ->pending()
            ->count());
    }

    public static function applications(User $user): int
    {
        return self::remember($user, 'applications', function () use ($user) {
            if ($user->canTask('applications.assign')) {
                return SiteApplication::query()
                    ->whereIn('status', [SiteApplication::STATUS_RECEIVED, SiteApplication::STATUS_RETURNED])
                    ->count();
            }

            if ($user->canTask('applications.review')) {
                return SiteApplication::query()
                    ->visibleTo($user)
                    ->where('status', SiteApplication::STATUS_ASSIGNED)
                    ->count();
            }

            if ($user->canTask('applications.concur')) {
                return SiteApplication::query()
                    ->whereIn('status', [SiteApplication::STATUS_DIRECTOR_REVIEW, SiteApplication::STATUS_RETURNED_TO_DIRECTOR])
                    ->count();
            }

            if ($user->canTask('applications.grant')) {
                return SiteApplication::query()
                    ->where('status', SiteApplication::STATUS_DG_REVIEW)
                    ->count();
            }

            return 0;
        });
    }

    public static function complaints(User $user): int
    {
        return self::remember($user, 'complaints', fn () => Complaint::query()
            ->needingStaffAction($user)
            ->count());
    }

    private static function remember(User $user, string $badge, \Closure $count): int
    {
        return (int) Cache::remember(
            "nav.{$badge}.{$user->id}",
            now()->addSeconds(30),
            $count,
        );
    }
}
