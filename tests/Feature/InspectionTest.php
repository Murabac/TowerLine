<?php

namespace Tests\Feature;

use App\Models\Inspection;
use App\Models\Operator;
use App\Models\Region;
use App\Models\Tower;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InspectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_inspector_can_submit_an_inspection(): void
    {
        [$region, $operator] = $this->seedBasics();
        $tower = $this->makeTower($region, $operator);
        $inspector = User::factory()->create([
            'role' => 'inspector',
            'region_id' => $region->id,
        ]);

        $this->actingAs($inspector)
            ->post(route('towers.inspections.store', $tower), [
                'power_status' => 'down',
                'generator_condition' => 'poor',
                'physical_condition' => 'poor',
                'notes' => 'Generator failed on site.',
            ])
            ->assertRedirect(route('towers.show', $tower));

        $this->assertDatabaseHas('inspections', [
            'tower_id' => $tower->id,
            'inspector_id' => $inspector->id,
            'power_status' => 'down',
        ]);

        $this->assertSame('critical', $tower->fresh()->health_status);
    }

    public function test_inspector_can_submit_comment_only_inspection(): void
    {
        [$region, $operator] = $this->seedBasics();
        $tower = $this->makeTower($region, $operator);
        $inspector = User::factory()->create([
            'role' => 'inspector',
            'region_id' => $region->id,
        ]);

        $this->actingAs($inspector)
            ->post(route('towers.inspections.store', $tower), [
                'notes' => 'Gate locked. Site viewed from the access road only.',
            ])
            ->assertRedirect(route('towers.show', $tower));

        $inspection = $tower->inspections()->first();

        $this->assertSame('Gate locked. Site viewed from the access road only.', $inspection->notes);
        $this->assertNull($inspection->power_status);
        $this->assertNull($inspection->generator_condition);
        $this->assertNull($inspection->physical_condition);
        $this->assertSame('unknown', $inspection->derivedHealth());
        $this->assertSame('unknown', $tower->fresh()->health_status);
    }

    public function test_inspector_can_submit_minimal_empty_visit(): void
    {
        [$region, $operator] = $this->seedBasics();
        $tower = $this->makeTower($region, $operator);
        $inspector = User::factory()->create([
            'role' => 'inspector',
            'region_id' => $region->id,
        ]);

        $this->actingAs($inspector)
            ->post(route('towers.inspections.store', $tower), [])
            ->assertRedirect(route('towers.show', $tower));

        $this->assertDatabaseCount('inspections', 1);
        $this->assertSame('unknown', $tower->fresh()->health_status);
    }

    public function test_partial_inspection_does_not_mark_tower_critical(): void
    {
        [$region, $operator] = $this->seedBasics();
        $tower = $this->makeTower($region, $operator);
        $inspector = User::factory()->create([
            'role' => 'inspector',
            'region_id' => $region->id,
        ]);

        $this->actingAs($inspector)
            ->post(route('towers.inspections.store', $tower), [
                'physical_condition' => 'fair',
                'notes' => 'Only structure visible from outside fence.',
            ])
            ->assertRedirect(route('towers.show', $tower));

        $this->assertSame('needs_attention', $tower->fresh()->health_status);
    }

    public function test_inspector_can_upload_multiple_photos_per_type(): void
    {
        Storage::fake('public');

        [$region, $operator] = $this->seedBasics();
        $tower = $this->makeTower($region, $operator);
        $inspector = User::factory()->create([
            'role' => 'inspector',
            'region_id' => $region->id,
        ]);

        $this->actingAs($inspector)
            ->post(route('towers.inspections.store', $tower), [
                'power_status' => 'on_grid',
                'generator_condition' => 'n_a',
                'physical_condition' => 'good',
                'photos' => [
                    'wide' => [
                        UploadedFile::fake()->image('wide-1.jpg'),
                        UploadedFile::fake()->image('wide-2.jpg'),
                    ],
                    'power' => [
                        UploadedFile::fake()->image('power-1.jpg'),
                    ],
                ],
            ])
            ->assertRedirect(route('towers.show', $tower));

        $inspection = $tower->inspections()->first();
        $this->assertCount(2, $inspection->photos['wide']);
        $this->assertCount(1, $inspection->photos['power']);
        $this->assertCount(3, $inspection->photoUrls());
        Storage::disk('public')->assertExists($inspection->photos['wide'][0]);
    }

    public function test_operator_viewer_cannot_submit_an_inspection(): void
    {
        [$region, $operator] = $this->seedBasics();
        $tower = $this->makeTower($region, $operator);
        $viewer = User::factory()->create([
            'role' => 'operator_viewer',
            'operator_id' => $operator->id,
        ]);

        $this->actingAs($viewer)
            ->get(route('towers.inspections.create', $tower))
            ->assertForbidden();
    }

    public function test_stale_inspection_threshold_is_one_hundred_eighty_days(): void
    {
        $this->assertSame(180, Tower::INSPECTION_STALE_DAYS);

        [$region, $operator] = $this->seedBasics();
        $tower = $this->makeTower($region, $operator);

        Inspection::query()->create([
            'tower_id' => $tower->id,
            'inspector_id' => User::factory()->create(['role' => 'admin'])->id,
            'notes' => 'Older visit',
            'inspected_at' => now()->subDays(181),
        ]);

        $this->assertTrue($tower->fresh()->isInspectionOverdue());

        $tower->inspections()->delete();
        Inspection::query()->create([
            'tower_id' => $tower->id,
            'inspector_id' => User::factory()->create(['role' => 'admin'])->id,
            'notes' => 'Recent visit',
            'inspected_at' => now()->subDays(30),
        ]);

        $this->assertFalse($tower->fresh()->isInspectionOverdue());
    }

    /**
     * @return array{0: Region, 1: Operator}
     */
    private function seedBasics(): array
    {
        $region = Region::query()->create(['name_en' => 'Sahil', 'name_so' => 'Saaxil']);
        $operator = Operator::query()->create([
            'name' => 'Telesom',
            'category' => 'telecom',
            'color' => '#0F766E',
        ]);

        return [$region, $operator];
    }

    private function makeTower(Region $region, Operator $operator): Tower
    {
        return Tower::query()->create([
            'name' => 'BER-SAH-001',
            'latitude' => 10.43,
            'longitude' => 45.01,
            'region_id' => $region->id,
            'operator_id' => $operator->id,
            'type' => 'monopole',
            'height_m' => 40,
            'capacity' => '4g',
            'signal_radius_m' => 12000,
            'status' => 'active',
            'health_status' => 'unknown',
        ]);
    }
}
