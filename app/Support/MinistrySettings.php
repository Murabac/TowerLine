<?php

namespace App\Support;

use App\Models\MinistrySetting;

class MinistrySettings
{
    /**
     * @return array{name: ?string, title_so: string, title_en: string}
     */
    public static function approvalLetterDirector(): array
    {
        $settings = MinistrySetting::query()->first();

        return [
            'name' => $settings?->approval_director_name ?: config('ministry.approval_letter.director_name') ?: null,
            'title_so' => $settings?->approval_director_title_so ?: config('ministry.approval_letter.director_title_so'),
            'title_en' => $settings?->approval_director_title_en ?: config('ministry.approval_letter.director_title_en'),
        ];
    }
}
