<?php

namespace Tests\Feature;

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
            'name' => 'Berbera site',
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
