<?php

namespace Database\Seeders;

use App\Models\License;
use App\Models\Tower;
use Illuminate\Database\Seeder;

class LicenseSeeder extends Seeder
{
    public function run(): void
    {
        License::query()->delete();

        $types = ['A', 'B', 'C'];

        foreach (Tower::query()->orderBy('id')->get() as $i => $tower) {
            $expires = match ($i % 3) {
                0 => now()->addDays(9 + ($i % 18)),
                1 => now()->subDays(5 + ($i % 40)),
                default => now()->addMonths(6 + ($i % 6)),
            };

            License::query()->create([
                'tower_id' => $tower->id,
                'operator_id' => $tower->operator_id,
                'license_type' => $types[$i % 3],
                'issued_at' => $expires->copy()->subYear(),
                'expires_at' => $expires,
            ]);
        }
    }
}
