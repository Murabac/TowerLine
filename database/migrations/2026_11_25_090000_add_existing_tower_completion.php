<?php

use App\Support\Permissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Towers confirmed from an existing-tower registration are flagged, and the Director of
 * Telecom (admin as backup) may complete their remaining details directly.
 *
 * Only the new task is granted; Permissions::syncToDatabase() is deliberately not called.
 */
return new class extends Migration
{
    private const TASK = 'towers.complete_existing';

    private const ROLES = ['admin', 'department_director'];

    public function up(): void
    {
        Schema::table('towers', function (Blueprint $table) {
            $table->boolean('registered_existing')->default(false)->after('status');
        });

        // Towers already confirmed from a registration.
        $towerIds = DB::table('approval_requests')
            ->where('type', 'tower_existing')
            ->where('status', 'approved')
            ->whereNotNull('applied_id')
            ->pluck('applied_id');

        if ($towerIds->isNotEmpty()) {
            DB::table('towers')->whereIn('id', $towerIds)->update(['registered_existing' => true]);
        }

        $now = now();
        $permissionId = DB::table('permissions')->where('key', self::TASK)->value('id')
            ?? DB::table('permissions')->insertGetId([
                'key' => self::TASK,
                'label' => Permissions::TASKS[self::TASK],
                'created_at' => $now,
                'updated_at' => $now,
            ]);

        foreach (self::ROLES as $role) {
            $exists = DB::table('role_permission')->where('role', $role)->where('permission_id', $permissionId)->exists();

            if (! $exists) {
                DB::table('role_permission')->insert([
                    'role' => $role,
                    'permission_id' => $permissionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            Permissions::forgetRole($role);
        }
    }

    public function down(): void
    {
        $id = DB::table('permissions')->where('key', self::TASK)->value('id');

        if ($id) {
            DB::table('role_permission')->where('permission_id', $id)->delete();
            DB::table('permissions')->where('id', $id)->delete();
        }

        Schema::table('towers', function (Blueprint $table) {
            $table->dropColumn('registered_existing');
        });
    }
};
