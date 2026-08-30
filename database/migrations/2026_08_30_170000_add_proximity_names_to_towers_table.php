<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('towers', function (Blueprint $table) {
            $table->string('nearest_school_name')->nullable()->after('nearest_school_m');
            $table->string('nearest_hospital_name')->nullable()->after('nearest_hospital_m');
            $table->string('nearest_house_name')->nullable()->after('nearest_house_m');
        });
    }

    public function down(): void
    {
        Schema::table('towers', function (Blueprint $table) {
            $table->dropColumn([
                'nearest_school_name',
                'nearest_hospital_name',
                'nearest_house_name',
            ]);
        });
    }
};
