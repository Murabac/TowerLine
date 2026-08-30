<?php

namespace Database\Seeders;

use App\Models\MinistrySetting;
use Illuminate\Database\Seeder;

class MinistrySettingSeeder extends Seeder
{
    public function run(): void
    {
        MinistrySetting::query()->firstOrCreate([], [
            'approval_director_name' => config('ministry.approval_letter.director_name'),
            'approval_director_title_so' => config('ministry.approval_letter.director_title_so'),
            'approval_director_title_en' => config('ministry.approval_letter.director_title_en'),
        ]);
    }
}
