<?php

namespace Tests\Feature;

use App\Models\Operator;
use App\Models\Region;
use App\Models\Role;
use App\Models\Tower;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_custom_role_and_assign_it(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $region = Region::query()->create(['name_en' => 'Maroodi Jeex', 'name_so' => 'Maroodi Jeex']);
        $operator = Operator::query()->create(['name' => 'Telesom', 'category' => 'telecom', 'color' => '#0F766E']);
        $inRegion = Tower::query()->create($this->towerAttributes($region, $operator, 'HRG-MAR-001', 'Hargeisa'));
        $otherRegion = Region::query()->create(['name_en' => 'Sahil', 'name_so' => 'Saaxil']);
        Tower::query()->create($this->towerAttributes($otherRegion, $operator, 'BER-SAH-001', 'Berbera'));

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('roles.index'))
            ->assertOk()
            ->assertSee(__('app.role_admin.title'), false);

        $this->actingAs($admin)
            ->post(route('roles.store'), [
                'name' => 'Regional analyst',
                'requires_regions' => '1',
                'tasks' => ['map.view', 'towers.view', 'reports.view'],
            ])
            ->assertRedirect(route('roles.index'));

        $role = Role::query()->where('key', 'regional-analyst')->firstOrFail();
        $this->assertTrue($role->requires_regions);
        $this->assertFalse($role->is_system);
        $this->assertEqualsCanonicalizing(
            ['map.view', 'towers.view', 'reports.view'],
            Permissions::tasksForRole($role->key),
        );

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'Analyst Demo',
                'email' => 'analyst.demo@mocit.local',
                'password' => 'password',
                'role' => $role->key,
                'region_ids' => [$region->id],
            ])
            ->assertRedirect(route('users.index'));

        $analyst = User::query()->where('email', 'analyst.demo@mocit.local')->firstOrFail();
        $this->assertTrue($analyst->canTask('towers.view'));
        $this->assertTrue($analyst->canTask('map.view'));
        $this->assertFalse($analyst->canTask('towers.create'));
        $this->assertFalse($analyst->canTask('users.manage'));
        $this->assertTrue($analyst->requiresRegions());

        $this->actingAs($analyst)
            ->get(route('roles.index'))
            ->assertForbidden();

        $this->actingAs($analyst)
            ->get(route('users.index'))
            ->assertForbidden();

        $this->actingAs($analyst)
            ->get(route('towers.index'))
            ->assertOk()
            ->assertSee($inRegion->name, false)
            ->assertDontSee('BER-SAH-001', false);

        $this->actingAs($analyst)
            ->get(route('map'))
            ->assertOk();

        $this->actingAs($analyst)
            ->getJson(route('map.towers'))
            ->assertOk()
            ->assertJsonCount(1, 'towers');

        $this->actingAs($analyst)
            ->get(route('towers.create'))
            ->assertForbidden();
    }

    public function test_system_roles_cannot_be_deleted(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);
        $inspectorRole = Role::query()->where('key', 'inspector')->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('roles.destroy', $inspectorRole))
            ->assertForbidden();

        $this->assertDatabaseHas('roles', ['key' => 'inspector']);
    }

    public function test_operations_manager_cannot_manage_roles(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $ops = User::factory()->create(['role' => 'operations_manager']);

        $this->actingAs($ops)
            ->get(route('roles.index'))
            ->assertForbidden();
    }

    public function test_seeded_regional_analyst_is_region_scoped(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $analyst = User::query()->where('email', 'analyst.maroodi@mocit.local')->firstOrFail();

        $this->assertTrue($analyst->canTask('towers.view'));
        $this->assertTrue($analyst->canTask('map.view'));
        $this->assertTrue($analyst->canTask('reports.view'));
        $this->assertFalse($analyst->canTask('towers.create'));
        $this->assertTrue($analyst->requiresRegions());

        $this->actingAs($analyst)
            ->get(route('map'))
            ->assertOk();
    }

    /**
     * @return array<string, mixed>
     */
    private function towerAttributes(Region $region, Operator $operator, string $name, string $city): array
    {
        return [
            'name' => $name,
            'latitude' => 9.56,
            'longitude' => 44.06,
            'region_id' => $region->id,
            'city' => $city,
            'operator_id' => $operator->id,
            'type' => 'monopole',
            'height_m' => 40,
            'capacity' => '4g',
            'signal_radius_m' => 12000,
            'status' => 'active',
            'health_status' => 'unknown',
        ];
    }
}
