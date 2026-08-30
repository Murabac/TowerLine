<?php

namespace App\Support;

use App\Models\FrequencyAllocationLetter;

class FrequencyAllocationLetterNumberGenerator
{
    public const PREFIX = 'MoCIT/FRQ';

    public static function generate(?int $year = null): string
    {
        $year ??= (int) now()->format('Y');
        $prefix = self::PREFIX.'/'.$year.'/';

        $latest = FrequencyAllocationLetter::query()
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
