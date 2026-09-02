<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->boolean('is_system')->default(false);
            $table->boolean('requires_regions')->default(false);
            $table->timestamps();
        });

        $now = now();

        DB::table('roles')->insert([
            ['key' => 'admin', 'name' => 'Ministry admin', 'is_system' => true, 'requires_regions' => false, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'operations_manager', 'name' => 'Operations manager', 'is_system' => true, 'requires_regions' => false, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'inspector', 'name' => 'Regional inspector', 'is_system' => true, 'requires_regions' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'operator_viewer', 'name' => 'Operator viewer', 'is_system' => true, 'requires_regions' => false, 'created_at' => $now, 'updated_at' => $now],
        ]);

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('role')->constrained('roles')->nullOnDelete();
        });

        $roles = DB::table('roles')->pluck('id', 'key');

        foreach ($roles as $key => $id) {
            DB::table('users')->where('role', $key)->update(['role_id' => $id]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
        });

        Schema::dropIfExists('roles');
    }
};
