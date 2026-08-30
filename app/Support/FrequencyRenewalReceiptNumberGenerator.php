<?php

namespace App\Support;

use App\Models\FrequencyRenewalReceipt;

class FrequencyRenewalReceiptNumberGenerator
{
    public const PREFIX = 'MoCIT/FRQ-RCP';

    public static function generate(?int $year = null): string
    {
        $year ??= (int) now()->format('Y');
        $prefix = self::PREFIX.'/'.$year.'/';

        $latest = FrequencyRenewalReceipt::query()
            ->where('reference_number', 'like', $prefix.'%')
            ->orderByDesc('reference_number')
            ->value('reference_number');

        $sequence = 1;

        if ($latest && preg_match('/\/(\d+)$/', $latest, $matches)) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
