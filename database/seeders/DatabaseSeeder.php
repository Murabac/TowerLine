<?php

namespace Database\Seeders;

use App\Models\Operator;
use App\Models\Region;
use App\Models\Role;
use App\Models\User;
use App\Support\OperatorPalette;
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

        Operator::query()->where('name', 'Sogasho')->update(['name' => 'Somcable']);

        $operators = [
            ['name' => 'Telesom', 'category' => 'telecom', 'contact_info' => 'Hargeisa'],
            ['name' => 'Somtel', 'category' => 'telecom', 'contact_info' => 'Hargeisa'],
            ['name' => 'Somcable', 'category' => 'telecom', 'contact_info' => 'Hargeisa'],
            ['name' => 'Truecable', 'category' => 'broadcast', 'contact_info' => 'Hargeisa'],
            ['name' => 'Astaan', 'category' => 'broadcast', 'contact_info' => 'Hargeisa'],
            ['name' => 'Horncable', 'category' => 'broadcast', 'contact_info' => 'Hargeisa'],
        ];

        foreach ($operators as $operator) {
            $operator['color'] = OperatorPalette::forName($operator['name']);
            $row = Operator::query()->firstOrCreate(['name' => $operator['name']], $operator);

            if ($row->color !== $operator['color']) {
                $row->update(['color' => $operator['color']]);
            }
        }

        Operator::query()->each(fn (Operator $operator) => $operator->regions()->sync([]));

        $maroodi = Region::query()->where('name_en', 'Maroodi Jeex')->first();

        $users = [
            [
                'email' => 'admin@mocit.local',
                'name' => 'MoCIT Admin',
                'role' => 'admin',
                'region_ids' => [],
            ],
            [
                'email' => 'ops@mocit.local',
                'name' => 'Operations Manager',
                'role' => 'operations_manager',
                'region_ids' => [],
            ],
            [
                'email' => 'inspector.maroodi@mocit.local',
                'name' => 'Inspector Maroodi Jeex',
                'role' => 'inspector',
                'region_ids' => array_filter([$maroodi?->id]),
            ],
        ];

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

        $this->call(PermissionSeeder::class);

        $roleIds = Role::query()->pluck('id', 'key');

        $hqUsers = [
            [
                'email' => 'section.head@mocit.local',
                'name' => 'Section Head',
                'role' => Role::KEY_SECTION_HEAD,
                'region_ids' => [],
            ],
            [
                'email' => 'coordinator.maroodi@mocit.local',
                'name' => 'Coordinator Maroodi Jeex',
                'role' => Role::KEY_REGIONAL_COORDINATOR,
                'region_ids' => array_filter([$maroodi?->id]),
            ],
            [
                'email' => 'director@mocit.local',
                'name' => 'Department Director',
                'role' => Role::KEY_DEPARTMENT_DIRECTOR,
                'region_ids' => [],
            ],
            [
                'email' => 'dg@mocit.local',
                'name' => 'Director General',
                'role' => Role::KEY_DIRECTOR_GENERAL,
                'region_ids' => [],
            ],
            [
                'email' => 'complaints@mocit.local',
                'name' => 'Complaints Officer',
                'role' => Role::KEY_COMPLAINTS_OFFICER,
                'region_ids' => [],
            ],
        ];

        foreach ($hqUsers as $user) {
            $record = User::query()->updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => Hash::make('password'),
                    'role' => $user['role'],
                    'role_id' => $roleIds[$user['role']] ?? null,
                    'region_id' => $user['region_ids'][0] ?? null,
                    'operator_id' => null,
                    'email_verified_at' => now(),
                ]
            );

            $record->syncInspectorRegions($user['region_ids']);
        }

        $telesom = Operator::query()->where('name', 'Telesom')->first();

        if ($telesom) {
            $viewer = User::query()->updateOrCreate(
                ['email' => 'viewer.telesom@mocit.local'],
                [
                    'name' => 'Telesom Viewer',
                    'password' => Hash::make('password'),
                    'role' => Role::KEY_OPERATOR_VIEWER,
                    'role_id' => $roleIds[Role::KEY_OPERATOR_VIEWER] ?? null,
                    'region_id' => null,
                    'operator_id' => $telesom->id,
                    'email_verified_at' => now(),
                ]
            );
            $viewer->syncInspectorRegions([]);
        }

        User::query()->whereIn('email', [
            'inspector.sahil@mocit.local',
            'inspector.west@mocit.local',
            'officer@mocit.local',
            'coordinator.sahil@mocit.local',
            'analyst.maroodi@mocit.local',
        ])->delete();

        $this->call(TowerSeeder::class);
        $this->call(InspectionSeeder::class);
        $this->call(MinistrySettingSeeder::class);
        $this->call(BuildApprovalLetterSeeder::class);
        $this->call(FrequencyAllocationSeeder::class);
        $this->call(LicenseSeeder::class);
        $this->call(ApprovalRequestSeeder::class);
        $this->call(AuditLogSeeder::class);
        $this->call(SiteApplicationSeeder::class);
    }
}
