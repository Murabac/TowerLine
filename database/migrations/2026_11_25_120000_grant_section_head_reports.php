<?php

use App\Support\Permissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The Section Head can view, print and export reports (not the audit-log report).
 *
 * Only these grants are added; Permissions::syncToDatabase() is deliberately not called,
 * so role changes made in the app are kept.
 */
return new class extends Migration
{
    private const ROLE = 'section_head';

    private const TASKS = ['reports.view', 'reports.print', 'reports.export_excel'];

    public function up(): void
    {
        $now = now();

        foreach (self::TASKS as $task) {
            $permissionId = DB::table('permissions')->where('key', $task)->value('id')
                ?? DB::table('permissions')->insertGetId([
                    'key' => $task,
                    'label' => Permissions::TASKS[$task],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

            $exists = DB::table('role_permission')
                ->where('role', self::ROLE)
                ->where('permission_id', $permissionId)
                ->exists();

            if (! $exists) {
                DB::table('role_permission')->insert([
                    'role' => self::ROLE,
                    'permission_id' => $permissionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        Permissions::forgetRole(self::ROLE);
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->whereIn('key', self::TASKS)->pluck('id');

        DB::table('role_permission')->where('role', self::ROLE)->whereIn('permission_id', $ids)->delete();

        Permissions::forgetRole(self::ROLE);
    }
};
