<?php

use App\Models\District;
use App\Models\SubDistrict;
use App\Support\GeographyReference;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sub_districts', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('name');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->decimal('bounds_south', 10, 7)->nullable()->after('longitude');
            $table->decimal('bounds_west', 10, 7)->nullable()->after('bounds_south');
            $table->decimal('bounds_north', 10, 7)->nullable()->after('bounds_west');
            $table->decimal('bounds_east', 10, 7)->nullable()->after('bounds_north');
        });

        GeographyReference::reset();

        SubDistrict::query()
            ->with('district')
            ->each(function (SubDistrict $subDistrict) {
                $district = $subDistrict->district;

                if (! $district) {
                    return;
                }

                $centroid = GeographyReference::centroidFromJson($district->name, $subDistrict->name);

                if (! $centroid) {
                    return;
                }

                $bounds = GeographyReference::boundsAroundPoint(
                    $centroid['latitude'],
                    $centroid['longitude'],
                    $centroid['zoom_pad'],
                );

                $subDistrict->update([
                    'latitude' => $centroid['latitude'],
                    'longitude' => $centroid['longitude'],
                    'bounds_south' => $bounds['south'],
                    'bounds_west' => $bounds['west'],
                    'bounds_north' => $bounds['north'],
                    'bounds_east' => $bounds['east'],
                ]);
            });

        District::query()
            ->with(['subDistricts', 'towers'])
            ->each(function (District $district) {
                $towerBounds = $district->towers
                    ->filter(fn ($tower) => $tower->latitude !== null && $tower->longitude !== null);

                if ($towerBounds->isEmpty()) {
                    return;
                }

                $south = (float) $towerBounds->min('latitude');
                $north = (float) $towerBounds->max('latitude');
                $west = (float) $towerBounds->min('longitude');
                $east = (float) $towerBounds->max('longitude');
                $pad = 0.004;

                $district->subDistricts
                    ->filter(fn (SubDistrict $subDistrict) => $subDistrict->bounds_south === null)
                    ->each(function (SubDistrict $subDistrict) use ($south, $north, $west, $east, $pad) {
                        $subDistrict->update([
                            'latitude' => ($south + $north) / 2,
                            'longitude' => ($west + $east) / 2,
                            'bounds_south' => $south - $pad,
                            'bounds_west' => $west - $pad,
                            'bounds_north' => $north + $pad,
                            'bounds_east' => $east + $pad,
                        ]);
                    });
            });
    }

    public function down(): void
    {
        Schema::table('sub_districts', function (Blueprint $table) {
            $table->dropColumn([
                'latitude',
                'longitude',
                'bounds_south',
                'bounds_west',
                'bounds_north',
                'bounds_east',
            ]);
        });
    }
};
