<?php

namespace App\Support;

use App\Models\Complaint;
use Illuminate\Support\Facades\DB;

class ComplaintNumberGenerator
{
    public const PREFIX = 'CMP';

    public static function generate(?int $year = null): string
    {
        $year ??= (int) now()->format('Y');
        $prefix = self::PREFIX.'-'.$year.'-';

        return DB::transaction(function () use ($prefix) {
            $latest = Complaint::query()
                ->where('reference_number', 'like', $prefix.'%')
                ->lockForUpdate()
                ->orderByDesc('reference_number')
                ->value('reference_number');

            $sequence = 1;

            if ($latest && preg_match('/-(\d+)$/', $latest, $matches)) {
                $sequence = ((int) $matches[1]) + 1;
            }

            return $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
        });
    }
}
