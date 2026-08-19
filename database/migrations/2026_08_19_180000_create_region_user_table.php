<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('region_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['region_id', 'user_id']);
        });

        $now = now();

        foreach (DB::table('users')->whereNotNull('region_id')->get(['id', 'region_id']) as $user) {
            DB::table('region_user')->insert([
                'region_id' => $user->region_id,
                'user_id' => $user->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('region_user');
    }
};
