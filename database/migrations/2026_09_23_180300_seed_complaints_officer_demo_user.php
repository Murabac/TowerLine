<?php

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Permissions::syncToDatabase();

        $roleId = DB::table('roles')->where('key', 'complaints_officer')->value('id');

        if (! $roleId || User::query()->where('email', 'complaints@mocit.local')->exists()) {
            return;
        }

        User::query()->create([
            'name' => 'Complaints Officer',
            'email' => 'complaints@mocit.local',
            'password' => 'password',
            'role' => 'complaints_officer',
            'role_id' => $roleId,
            'email_verified_at' => now(),
        ]);
    }

    public function down(): void
    {
        User::query()->where('email', 'complaints@mocit.local')->delete();
    }
};
