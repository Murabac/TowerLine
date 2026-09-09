<?php

namespace App\Support;

use App\Models\BuildApprovalLetter;
use App\Models\SiteApplication;
use App\Models\Tower;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SiteApplicationPermit
{
    public static function issue(SiteApplication $application, User $grantedBy): Tower
    {
        return DB::transaction(function () use ($application, $grantedBy) {
            $application->refresh();

            if ($application->tower_id) {
                return $application->tower()->firstOrFail();
            }

            $capacity = TowerCapacity::normalize($application->capacity) ?? '4g';
            $type = in_array($application->type, ['guyed', 'monopole', 'rooftop'], true)
                ? $application->type
                : 'monopole';

            $tower = Tower::query()->create([
                'name' => TowerNameGenerator::generate(
                    operatorId: (int) $application->operator_id,
                    regionId: (int) $application->region_id,
                    city: $application->city,
                    districtId: $application->district_id,
                    subDistrictId: $application->sub_district_id,
                ),
                'latitude' => $application->latitude,
                'longitude' => $application->longitude,
                'region_id' => $application->region_id,
                'district_id' => $application->district_id,
                'sub_district_id' => $application->sub_district_id,
                'city' => $application->city,
                'land_area' => $application->land_area,
                'operator_id' => $application->operator_id,
                'type' => $type,
                'height_m' => $application->height_m ?: 30,
                'nearest_school_m' => $application->nearest_school_m,
                'nearest_school_name' => $application->nearest_school_name,
                'nearest_hospital_m' => $application->nearest_hospital_m,
                'nearest_hospital_name' => $application->nearest_hospital_name,
                'nearest_house_m' => $application->nearest_house_m,
                'nearest_house_name' => $application->nearest_house_name,
                'fence_distance_m' => $application->fence_distance_m,
                'site_map_notes' => $application->site_map_notes,
                'capacity' => $capacity,
                'power_sources' => $application->power_sources ?: [],
                'signal_radius_m' => $application->signal_radius_m ?: TowerSignalRadius::defaultForCapacity($capacity),
                'status' => 'under_construction',
                'health_status' => 'unknown',
                'application_date' => $application->created_at?->toDateString(),
                'registration_inspector_notes' => $application->site_visit_notes,
                'registration_director_notes' => $application->director_remarks,
            ]);

            BuildApprovalLetter::query()->create([
                'tower_id' => $tower->id,
                'operator_id' => $tower->operator_id,
                'reference_number' => BuildApprovalLetterNumberGenerator::generate(),
                'status' => BuildApprovalLetter::STATUS_APPROVED,
                'issued_at' => now()->toDateString(),
                'issued_by' => $grantedBy->id,
                'template_version' => BuildApprovalLetter::TEMPLATE_VERSION,
            ]);

            $application->forceFill(['tower_id' => $tower->id])->save();

            return $tower->fresh(['operator', 'region', 'currentApprovalLetter']) ?? $tower;
        });
    }
}
