<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Support\Permissions;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        Permissions::syncToDatabase();

        $analyst = Role::query()->firstOrCreate(
            ['key' => 'regional_analyst'],
            [
                'name' => 'Regional analyst',
                'is_system' => false,
                'requires_regions' => true,
            ],
        );

        if ($analyst->wasRecentlyCreated) {
            Permissions::syncRoleTasks($analyst->key, [
                'map.view',
                'towers.view',
                'reports.view',
            ]);
        }
    }
}
