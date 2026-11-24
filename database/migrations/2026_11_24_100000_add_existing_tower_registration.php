<?php

use App\Support\Permissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Existing-tower registration: towers confirmed from a quick registration may not know
 * their capacity yet, and two new tasks are granted to the roles that use them.
 *
 * Only the new tasks are added. Permissions::syncToDatabase() is deliberately not called,
 * because it would reset any task changes the Ministry has made to built-in roles.
 */
return new class extends Migration
{
    private const GRANTS = [
        'towers.register_existing' => ['admin', 'section_head', 'regional_coordinator'],
        'towers.confirm_existing' => ['admin', 'department_director'],
    ];

    public function up(): void
    {
        Schema::table('towers', function (Blueprint $table) {
            $table->string('capacity')->nullable()->change();
        });

        $now = now();

        foreach (self::GRANTS as $task => $roles) {
            $permissionId = DB::table('permissions')->where('key', $task)->value('id')
                ?? DB::table('permissions')->insertGetId([
                    'key' => $task,
                    'label' => Permissions::TASKS[$task],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

            foreach ($roles as $role) {
                $exists = DB::table('role_permission')
                    ->where('role', $role)
                    ->where('permission_id', $permissionId)
                    ->exists();

                if (! $exists) {
                    DB::table('role_permission')->insert([
                        'role' => $role,
                        'permission_id' => $permissionId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        foreach (['admin', 'section_head', 'regional_coordinator', 'department_director'] as $role) {
            Permissions::forgetRole($role);
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->whereIn('key', array_keys(self::GRANTS))->pluck('id');

        DB::table('role_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();

        // capacity stays nullable: confirmed towers may already have no capacity.
    }
};
