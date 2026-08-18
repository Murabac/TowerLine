<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('inspector')->after('password');
            $table->foreignId('region_id')->nullable()->after('role')->constrained()->nullOnDelete();
            $table->foreignId('operator_id')->nullable()->after('region_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('operator_id');
            $table->dropConstrainedForeignId('region_id');
            $table->dropColumn('role');
        });
    }
};
