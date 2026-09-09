<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_applications', function (Blueprint $table) {
            $table->string('type')->nullable()->after('longitude');
            $table->decimal('height_m', 6, 1)->nullable()->after('type');
            $table->string('capacity')->nullable()->after('height_m');
            $table->unsignedInteger('signal_radius_m')->nullable()->after('capacity');
            $table->string('land_area')->nullable()->after('signal_radius_m');
            $table->decimal('fence_distance_m', 6, 1)->nullable()->after('land_area');
            $table->json('power_sources')->nullable()->after('fence_distance_m');
            $table->string('nearest_school_name')->nullable()->after('power_sources');
            $table->unsignedInteger('nearest_school_m')->nullable()->after('nearest_school_name');
            $table->string('nearest_hospital_name')->nullable()->after('nearest_school_m');
            $table->unsignedInteger('nearest_hospital_m')->nullable()->after('nearest_hospital_name');
            $table->string('nearest_house_name')->nullable()->after('nearest_hospital_m');
            $table->unsignedInteger('nearest_house_m')->nullable()->after('nearest_house_name');
            $table->text('site_map_notes')->nullable()->after('nearest_house_m');
        });
    }

    public function down(): void
    {
        Schema::table('site_applications', function (Blueprint $table) {
            $table->dropColumn([
                'type',
                'height_m',
                'capacity',
                'signal_radius_m',
                'land_area',
                'fence_distance_m',
                'power_sources',
                'nearest_school_name',
                'nearest_school_m',
                'nearest_hospital_name',
                'nearest_hospital_m',
                'nearest_house_name',
                'nearest_house_m',
                'site_map_notes',
            ]);
        });
    }
};
