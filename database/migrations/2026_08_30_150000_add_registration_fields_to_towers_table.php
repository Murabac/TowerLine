<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('towers', function (Blueprint $table) {
            $table->string('city')->nullable()->after('sub_district_id');
            $table->string('land_area')->nullable()->after('city');
            $table->unsignedInteger('nearest_school_m')->nullable()->after('height_m');
            $table->unsignedInteger('nearest_hospital_m')->nullable()->after('nearest_school_m');
            $table->unsignedInteger('nearest_house_m')->nullable()->after('nearest_hospital_m');
            $table->decimal('fence_distance_m', 8, 2)->nullable()->after('nearest_house_m');
            $table->text('other_towers_nearby')->nullable()->after('fence_distance_m');
            $table->text('site_map_notes')->nullable()->after('other_towers_nearby');
            $table->date('application_date')->nullable()->after('commissioned_at');
            $table->text('registration_inspector_notes')->nullable()->after('application_date');
            $table->text('registration_director_notes')->nullable()->after('registration_inspector_notes');
        });
    }

    public function down(): void
    {
        Schema::table('towers', function (Blueprint $table) {
            $table->dropColumn([
                'city',
                'land_area',
                'nearest_school_m',
                'nearest_hospital_m',
                'nearest_house_m',
                'fence_distance_m',
                'other_towers_nearby',
                'site_map_notes',
                'application_date',
                'registration_inspector_notes',
                'registration_director_notes',
            ]);
        });
    }
};
