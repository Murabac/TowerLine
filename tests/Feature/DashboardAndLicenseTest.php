<?php

namespace Tests\Feature;

use App\Models\License;
use App\Models\Operator;
use App\Models\Region;
use App\Models\Tower;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DashboardAndLicenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_the_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('app.dashboard.total_towers'), false);
    }

    public function test_admin_can_create_a_license(): void
    {
        [$region, $operator] = $this->basics();
        $tower = $this->tower($region, $operator);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('licenses.store'), [
                'tower_id' => $tower->id,
                'license_type' => 'B',
                'issued_at' => now()->subYear()->toDateString(),
                'expires_at' => now()->addMonths(6)->toDateString(),
            ])
            ->assertRedirect(route('licenses.index'));

        $this->assertDatabaseHas('licenses', [
            'tower_id' => $tower->id,
            'license_type' => 'B',
            'operator_id' => $operator->id,
        ]);
    }

    public function test_admin_can_open_the_edit_license_page(): void
    {
        [$region, $operator] = $this->basics();
        $tower = $this->tower($region, $operator);
        $license = License::query()->create([
            'tower_id' => $tower->id,
            'operator_id' => $operator->id,
            'license_type' => 'A',
            'issued_at' => now()->subYear(),
            'expires_at' => now()->addMonths(6),
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('licenses.edit', $license))
            ->assertOk()
            ->assertSee($tower->name, false)
            ->assertSee(__('app.licenses.edit'), false)
            ->assertSee(__('app.licenses.state_preview'), false)
            ->assertSee(__('app.licenses.documents'), false);
    }

    public function test_admin_can_upload_and_replace_license_documents(): void
    {
        Storage::fake('public');

        [$region, $operator] = $this->basics();
        $tower = $this->tower($region, $operator);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('licenses.store'), [
                'tower_id' => $tower->id,
                'license_type' => 'B',
                'issued_at' => now()->subYear()->toDateString(),
                'expires_at' => now()->addMonths(6)->toDateString(),
                'documents' => [
                    UploadedFile::fake()->image('permit.jpg'),
                    UploadedFile::fake()->image('scan.jpg'),
                ],
            ])
            ->assertRedirect(route('licenses.index'));

        $license = License::query()->first();
        $this->assertCount(2, $license->documentRecords());
        Storage::disk('public')->assertExists($license->documentRecords()[0]['path']);

        $keep = $license->documentRecords()[1]['path'];
        $remove = $license->documentRecords()[0]['path'];

        $this->actingAs($admin)
            ->put(route('licenses.update', $license), [
                'license_type' => 'B',
                'issued_at' => now()->subYear()->toDateString(),
                'expires_at' => now()->addMonths(6)->toDateString(),
                'remove_documents' => [$remove],
                'documents' => [
                    UploadedFile::fake()->image('renewal.jpg'),
                ],
            ])
            ->assertRedirect(route('licenses.index'));

        $license->refresh();
        $paths = collect($license->documentRecords())->pluck('path');
        $this->assertTrue($paths->contains($keep));
        $this->assertFalse($paths->contains($remove));
        $this->assertCount(2, $license->documentRecords());
        Storage::disk('public')->assertMissing($remove);
    }

    public function test_license_list_is_visible_to_operator_viewers_for_their_operator(): void
    {
        [$region, $operator] = $this->basics();
        $other = Operator::query()->create(['name' => 'Somtel', 'category' => 'telecom', 'color' => '#1D4ED8']);
        $ownTower = $this->tower($region, $operator, 'Own');
        $otherTower = $this->tower($region, $other, 'Other');

        License::query()->create([
            'tower_id' => $ownTower->id,
            'operator_id' => $operator->id,
            'license_type' => 'A',
            'issued_at' => now()->subYear(),
            'expires_at' => now()->addMonth(),
        ]);
        License::query()->create([
            'tower_id' => $otherTower->id,
            'operator_id' => $other->id,
            'license_type' => 'C',
            'issued_at' => now()->subYear(),
            'expires_at' => now()->addMonth(),
        ]);

        $viewer = User::factory()->create([
            'role' => 'operator_viewer',
            'operator_id' => $operator->id,
        ]);

        $this->actingAs($viewer)
            ->get(route('licenses.index'))
            ->assertOk()
            ->assertSee('Own')
            ->assertDontSee('Other');
    }

    public function test_license_seeder_fills_every_tower_with_a_license_state(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $this->assertSame(
            \App\Models\Tower::query()->count(),
            \App\Models\License::query()->count()
        );

        $states = \App\Models\License::query()->get()->map->display_status->unique()->sort()->values();

        $this->assertEqualsCanonicalizing(
            ['active', 'expired', 'expiring_soon'],
            $states->all()
        );
    }

    /**
     * @return array{0: Region, 1: Operator}
     */
    private function basics(): array
    {
        return [
            Region::query()->create(['name_en' => 'Sahil', 'name_so' => 'Saaxil']),
            Operator::query()->create(['name' => 'Telesom', 'category' => 'telecom', 'color' => '#0F766E']),
        ];
    }

    private function tower(Region $region, Operator $operator, string $name = 'Berbera site'): Tower
    {
        return Tower::query()->create([
            'name' => $name,
            'latitude' => 10.43,
            'longitude' => 45.01,
            'region_id' => $region->id,
            'operator_id' => $operator->id,
            'type' => 'monopole',
            'height_m' => 40,
            'capacity' => '4G',
            'signal_radius_m' => 12000,
            'status' => 'active',
            'health_status' => 'unknown',
        ]);
    }
}
