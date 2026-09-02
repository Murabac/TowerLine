<?php

namespace Database\Seeders;

use App\Models\ApprovalRequest;
use App\Models\Tower;
use App\Models\User;
use Illuminate\Database\Seeder;

class ApprovalRequestSeeder extends Seeder
{
    public function run(): void
    {
        $inspector = User::query()->where('email', 'inspector.maroodi@mocit.local')->first();
        $tower = Tower::query()
            ->whereHas('region', fn ($query) => $query->where('name_en', 'Maroodi Jeex'))
            ->orderBy('name')
            ->first();

        if (! $inspector || ! $tower) {
            return;
        }

        ApprovalRequest::query()->updateOrCreate(
            [
                'type' => ApprovalRequest::TYPE_TOWER_UPDATE,
                'tower_id' => $tower->id,
                'submitted_by' => $inspector->id,
                'status' => ApprovalRequest::STATUS_PENDING,
            ],
            [
                'payload' => [
                    'attributes' => [
                        ...$tower->only([
                            'name', 'latitude', 'longitude', 'region_id', 'district_id', 'sub_district_id',
                            'city', 'operator_id', 'type', 'height_m', 'capacity', 'status', 'signal_radius_m',
                        ]),
                        'registration_inspector_notes' => 'Fence gap on the south side needs follow-up.',
                    ],
                    'snapshot' => $tower->only([
                        'name', 'latitude', 'longitude', 'region_id', 'district_id', 'sub_district_id',
                        'city', 'operator_id', 'type', 'height_m', 'capacity', 'status', 'signal_radius_m',
                        'registration_inspector_notes',
                    ]),
                    'summary' => $tower->name,
                ],
            ],
        );

        ApprovalRequest::query()->updateOrCreate(
            [
                'type' => ApprovalRequest::TYPE_INSPECTION,
                'tower_id' => $tower->id,
                'submitted_by' => $inspector->id,
                'status' => ApprovalRequest::STATUS_PENDING,
            ],
            [
                'payload' => [
                    'attributes' => [
                        'power_status' => 'generator',
                        'generator_condition' => 'fair',
                        'physical_condition' => 'good',
                        'notes' => 'Grid down this morning. Generator running, oil checked.',
                    ],
                    'photos' => [],
                    'summary' => $tower->name,
                ],
            ],
        );
    }
}
