<?php

namespace Database\Seeders;

use App\Models\BuildApprovalLetter;
use App\Models\Tower;
use App\Models\User;
use App\Support\BuildApprovalLetterNumberGenerator;
use Illuminate\Database\Seeder;

class BuildApprovalLetterSeeder extends Seeder
{
    public function run(): void
    {
        BuildApprovalLetter::query()->delete();

        $admin = User::query()->where('role', 'admin')->first();

        foreach (Tower::query()->where('status', 'active')->orderBy('id')->get() as $i => $tower) {
            if ($i % 4 === 3) {
                continue;
            }

            BuildApprovalLetter::query()->create([
                'tower_id' => $tower->id,
                'operator_id' => $tower->operator_id,
                'reference_number' => BuildApprovalLetterNumberGenerator::generate((int) now()->format('Y')),
                'status' => BuildApprovalLetter::STATUS_APPROVED,
                'issued_at' => $tower->application_date ?? now()->subDays(10 + $i),
                'issued_by' => $admin?->id,
                'template_version' => BuildApprovalLetter::TEMPLATE_VERSION,
            ]);
        }
    }
}
