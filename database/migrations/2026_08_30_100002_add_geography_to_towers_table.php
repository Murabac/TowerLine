<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('towers', function (Blueprint $table) {
            $table->foreignId('district_id')->nullable()->after('region_id')->constrained()->nullOnDelete();
            $table->foreignId('sub_district_id')->nullable()->after('district_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('towers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sub_district_id');
            $table->dropConstrainedForeignId('district_id');
        });
    }
};
