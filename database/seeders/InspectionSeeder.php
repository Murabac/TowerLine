<?php

namespace Database\Seeders;

use App\Models\Inspection;
use App\Models\Tower;
use App\Models\User;
use Illuminate\Database\Seeder;

class InspectionSeeder extends Seeder
{
    public function run(): void
    {
        Inspection::query()->delete();

        $admin = User::query()->where('role', 'admin')->first();
        $inspectors = User::query()->where('role', 'inspector')->with('regions')->get();

        $profiles = [
            ['on_grid', 'good', 'good', 20],
            ['on_grid', 'fair', 'fair', 40],
            ['generator', 'poor', 'fair', 15],
            ['battery', 'n_a', 'good', 70],
            ['down', 'poor', 'poor', 110],
            ['on_grid', 'good', 'good', 5],
        ];

        $i = 0;

        foreach (Tower::query()->where('status', 'active')->orderBy('id')->get() as $tower) {
            if ($i % 5 === 4) {
                $i++;
                continue;
            }

            $profile = $profiles[$i % count($profiles)];
            $inspector = $inspectors->first(fn (User $user) => $user->coversRegion((int) $tower->region_id)) ?? $admin;

            if (! $inspector) {
                continue;
            }

            Inspection::query()->create([
                'tower_id' => $tower->id,
                'inspector_id' => $inspector->id,
                'power_status' => $profile[0],
                'generator_condition' => $profile[1],
                'physical_condition' => $profile[2],
                'notes' => 'Sample field inspection for demo.',
                'photos' => [],
                'inspected_at' => now()->subDays($profile[3]),
            ]);

            $i++;
        }
    }
}
