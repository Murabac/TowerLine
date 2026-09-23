<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('roles')->where('key', 'complaints_officer')->exists()) {
            return;
        }

        $now = now();

        DB::table('roles')->insert([
            'key' => 'complaints_officer',
            'name' => 'Complaints officer',
            'is_system' => true,
            'requires_regions' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('roles')->where('key', 'complaints_officer')->delete();
    }
};
