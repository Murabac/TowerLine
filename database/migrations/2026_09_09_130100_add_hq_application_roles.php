<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach ([
            ['key' => 'section_head', 'name' => 'Section Head', 'requires_regions' => false],
            ['key' => 'assigned_officer', 'name' => 'Assigned officer', 'requires_regions' => false],
            ['key' => 'regional_coordinator', 'name' => 'Regional coordinator', 'requires_regions' => true],
            ['key' => 'department_director', 'name' => 'Department Director', 'requires_regions' => false],
            ['key' => 'director_general', 'name' => 'Director General', 'requires_regions' => false],
        ] as $role) {
            if (DB::table('roles')->where('key', $role['key'])->exists()) {
                continue;
            }

            DB::table('roles')->insert([
                'key' => $role['key'],
                'name' => $role['name'],
                'is_system' => true,
                'requires_regions' => $role['requires_regions'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('roles')->whereIn('key', [
            'section_head',
            'assigned_officer',
            'regional_coordinator',
            'department_director',
            'director_general',
        ])->delete();
    }
};
