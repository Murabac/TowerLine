<?php

namespace Database\Seeders;

use App\Models\Operator;
use App\Models\Region;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $regions = [
            ['name_en' => 'Awdal', 'name_so' => 'Awdal'],
            ['name_en' => 'Maroodi Jeex', 'name_so' => 'Maroodi Jeex'],
            ['name_en' => 'Sahil', 'name_so' => 'Saaxil'],
            ['name_en' => 'Togdheer', 'name_so' => 'Togdheer'],
            ['name_en' => 'Sanaag', 'name_so' => 'Sanaag'],
            ['name_en' => 'Sool', 'name_so' => 'Sool'],
        ];

        foreach ($regions as $region) {
            Region::query()->firstOrCreate(['name_en' => $region['name_en']], $region);
        }

        $this->call(GeographySeeder::class);

        $operators = [
            ['name' => 'Telesom', 'category' => 'telecom', 'color' => '#0F766E', 'contact_info' => 'Hargeisa'],
            ['name' => 'Somtel', 'category' => 'telecom', 'color' => '#1D4ED8', 'contact_info' => 'Hargeisa'],
            ['name' => 'Sogasho', 'category' => 'telecom', 'color' => '#7C3AED', 'contact_info' => 'Hargeisa'],
            ['name' => 'Truecable', 'category' => 'broadcast', 'color' => '#C2410C', 'contact_info' => 'Hargeisa'],
            ['name' => 'Astaan', 'category' => 'broadcast', 'color' => '#BE185D', 'contact_info' => 'Hargeisa'],
            ['name' => 'Horncable', 'category' => 'broadcast', 'color' => '#B45309', 'contact_info' => 'Hargeisa'],
        ];

        foreach ($operators as $operator) {
            Operator::query()->firstOrCreate(['name' => $operator['name']], $operator);
        }

        $maroodi = Region::query()->where('name_en', 'Maroodi Jeex')->first();
        $sahil = Region::query()->where('name_en', 'Sahil')->first();
        $awdal = Region::query()->where('name_en', 'Awdal')->first();

        $users = [
            [
                'email' => 'admin@mocit.local',
                'name' => 'MoCIT Admin',
                'role' => 'admin',
                'region_ids' => [],
            ],
            [
                'email' => 'inspector.maroodi@mocit.local',
                'name' => 'Inspector Maroodi Jeex',
                'role' => 'inspector',
                'region_ids' => array_filter([$maroodi?->id]),
            ],
            [
                'email' => 'inspector.sahil@mocit.local',
                'name' => 'Inspector Sahil',
                'role' => 'inspector',
                'region_ids' => array_filter([$sahil?->id]),
            ],
            [
                'email' => 'inspector.west@mocit.local',
                'name' => 'Inspector West',
                'role' => 'inspector',
                'region_ids' => array_filter([$awdal?->id, $maroodi?->id, $sahil?->id]),
            ],
        ];

        User::query()->where('role', 'operator_viewer')->delete();

        foreach ($users as $user) {
            $record = User::query()->updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => Hash::make('password'),
                    'role' => $user['role'],
                    'region_id' => $user['region_ids'][0] ?? null,
                    'operator_id' => null,
                    'email_verified_at' => now(),
                ]
            );

            $record->syncInspectorRegions($user['region_ids']);
        }

        $this->call(TowerSeeder::class);
        $this->call(InspectionSeeder::class);
        $this->call(MinistrySettingSeeder::class);
        $this->call(BuildApprovalLetterSeeder::class);
        $this->call(LicenseSeeder::class);
        $this->call(AuditLogSeeder::class);
    }
}
