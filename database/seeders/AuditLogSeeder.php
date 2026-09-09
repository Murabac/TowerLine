<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Inspection;
use App\Models\License;
use App\Models\Tower;
use App\Models\User;
use Illuminate\Database\Seeder;

class AuditLogSeeder extends Seeder
{
    public function run(): void
    {
        AuditLog::query()->delete();

        $admin = User::query()->where('email', 'admin@mocit.local')->first();
        $maroodi = User::query()->where('email', 'inspector.maroodi@mocit.local')->first();

        if (! $admin) {
            return;
        }

        $towers = Tower::query()->with(['operator', 'region'])->orderBy('id')->get();
        $licenses = License::query()->with('tower')->orderBy('id')->get();
        $inspections = Inspection::query()->with('tower')->orderBy('id')->get();

        foreach ($towers->take(8) as $i => $tower) {
            $this->write(
                userId: $admin->id,
                action: 'created',
                model: $tower,
                changes: [
                    'name' => $tower->name,
                    'status' => $tower->status,
                    'operator_id' => $tower->operator_id,
                    'region_id' => $tower->region_id,
                ],
                at: now()->subDays(24 - $i)->setTime(8, 15 + $i),
            );
        }

        foreach ($inspections->take(6) as $i => $inspection) {
            $inspector = $maroodi ?? $admin;

            $this->write(
                userId: $inspector->id,
                action: 'created',
                model: $inspection,
                changes: [
                    'name' => $inspection->tower?->name,
                    'tower_id' => $inspection->tower_id,
                    'power_status' => $inspection->power_status,
                    'physical_condition' => $inspection->physical_condition,
                    'generator_condition' => $inspection->generator_condition,
                    'health' => $inspection->derivedHealth(),
                    'notes' => $inspection->notes,
                ],
                at: now()->subDays(18 - $i)->setTime(11, 20 + ($i * 3)),
            );
        }

        foreach ($licenses->take(6) as $i => $license) {
            $this->write(
                userId: $admin->id,
                action: 'created',
                model: $license,
                changes: [
                    'name' => $license->tower?->name,
                    'tower_id' => $license->tower_id,
                    'license_type' => $license->license_type,
                    'issued_at' => $license->issued_at?->toDateString(),
                    'expires_at' => $license->expires_at?->toDateString(),
                ],
                at: now()->subDays(12 - $i)->setTime(14, 5 + $i),
            );
        }

        $updatedTower = $towers->first();
        if ($updatedTower) {
            $this->write(
                userId: $admin->id,
                action: 'updated',
                model: $updatedTower,
                changes: [
                    'name' => $updatedTower->name,
                    'before' => ['status' => 'under_construction', 'health_status' => 'unknown'],
                    'after' => ['status' => $updatedTower->status, 'health_status' => $updatedTower->health_status],
                ],
                at: now()->subDays(4)->setTime(9, 40),
            );
        }

        $updatedLicense = $licenses->first();
        if ($updatedLicense) {
            $this->write(
                userId: $admin->id,
                action: 'updated',
                model: $updatedLicense,
                changes: [
                    'name' => $updatedLicense->tower?->name,
                    'before' => ['license_type' => 'C', 'expires_at' => now()->subMonths(2)->toDateString()],
                    'after' => [
                        'license_type' => $updatedLicense->license_type,
                        'expires_at' => $updatedLicense->expires_at?->toDateString(),
                    ],
                ],
                at: now()->subDays(2)->setTime(16, 10),
            );
        }

        $updatedUser = $sahil ?? $maroodi;
        if ($updatedUser) {
            $this->write(
                userId: $admin->id,
                action: 'updated',
                model: $updatedUser,
                changes: [
                    'name' => $updatedUser->name,
                    'email' => $updatedUser->email,
                    'role' => $updatedUser->role,
                    'region_id' => $updatedUser->region_id,
                ],
                at: now()->subDay()->setTime(10, 5),
            );
        }

        $removedTower = $towers->last();
        if ($removedTower) {
            $this->write(
                userId: $admin->id,
                action: 'deleted',
                model: $removedTower,
                changes: [
                    'name' => 'Old '.$removedTower->operator->name.' site (demo)',
                    'status' => 'decommissioned',
                    'region_id' => $removedTower->region_id,
                ],
                at: now()->subHours(6),
            );
        }
    }

    private function write(int $userId, string $action, object $model, array $changes, $at): void
    {
        AuditLog::query()->create([
            'user_id' => $userId,
            'action' => $action,
            'model_type' => $model::class,
            'model_id' => $model->getKey(),
            'changes' => $changes,
            'created_at' => $at,
        ]);
    }
}
