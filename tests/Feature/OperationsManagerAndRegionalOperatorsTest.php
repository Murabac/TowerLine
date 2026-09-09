<?php

namespace Tests\Feature;

use App\Models\Operator;
use App\Models\Region;
use App\Models\Tower;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationsManagerAndRegionalOperatorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_operations_manager_logs_in_to_dashboard_and_manages_registry_without_audit_or_users(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $ops = User::query()->where('email', 'ops@mocit.local')->firstOrFail();

        $this->post('/login', [
            'email' => $ops->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->actingAs($ops)
            ->get(route('dashboard'))
            ->assertOk();

        $this->actingAs($ops)
            ->get(route('towers.index'))
            ->assertOk();

        $this->actingAs($ops)
            ->get(route('districts.index'))
            ->assertOk();

        $this->actingAs($ops)
            ->get(route('users.index'))
            ->assertForbidden();

        $this->actingAs($ops)
            ->get(route('audit-logs.index'))
            ->assertForbidden();

        $this->actingAs($ops)
            ->get(route('settings.ministry.edit'))
            ->assertForbidden();
    }

    public function test_operations_manager_cannot_delete_towers_but_admin_can(): void
    {
        $region = Region::query()->create(['name_en' => 'Sahil', 'name_so' => 'Saaxil']);
        $operator = Operator::query()->create(['name' => 'Telesom', 'category' => 'telecom', 'color' => '#0F766E']);
        $tower = Tower::query()->create([
            'name' => 'SAH-TEL-001',
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

        $ops = User::factory()->create(['role' => 'operations_manager']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($ops)
            ->delete(route('towers.destroy', $tower))
            ->assertForbidden();

        $this->actingAs($admin)
            ->delete(route('towers.destroy', $tower))
            ->assertRedirect(route('towers.index'));
    }

    public function test_can_task_checks_role_permissions(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $ops = User::factory()->create(['role' => 'operations_manager']);
        $admin = User::factory()->create(['role' => 'admin']);
        $inspector = User::factory()->create(['role' => 'inspector']);

        $this->assertTrue($ops->canTask('approvals.review'));
        $this->assertTrue($ops->canTask('geography.manage'));
        $this->assertFalse($ops->canTask('audit.view'));
        $this->assertFalse($ops->canTask('users.manage'));
        $this->assertFalse($ops->canTask('towers.delete'));

        $this->assertTrue($admin->canTask('audit.view'));
        $this->assertTrue($admin->canTask('users.manage'));
        $this->assertTrue($admin->canTask('applications.view'));
        $this->assertTrue($admin->canTask('applications.assign'));
        $this->assertFalse($ops->canTask('applications.view'));

        $this->assertTrue($inspector->canTask('towers.create'));
        $this->assertFalse($inspector->canTask('approvals.review'));
    }

    public function test_all_operators_are_listed_regardless_of_region(): void
    {
        $region = Region::query()->create(['name_en' => 'Sahil', 'name_so' => 'Saaxil']);
        $other = Region::query()->create(['name_en' => 'Awdal', 'name_so' => 'Awdal']);

        Operator::query()->create(['name' => 'Telesom', 'category' => 'telecom', 'color' => '#0F766E']);
        Operator::query()->create(['name' => 'Truecable', 'category' => 'broadcast', 'color' => '#C2410C']);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->getJson(route('geography.operators', ['region_id' => $region->id]))
            ->assertOk()
            ->assertJsonFragment(['name' => 'Telesom'])
            ->assertJsonFragment(['name' => 'Truecable'])
            ->assertJsonCount(2, 'operators');

        $this->actingAs($admin)
            ->getJson(route('geography.operators', ['region_id' => $other->id]))
            ->assertOk()
            ->assertJsonFragment(['name' => 'Telesom'])
            ->assertJsonFragment(['name' => 'Truecable'])
            ->assertJsonCount(2, 'operators');
    }

    public function test_tower_store_accepts_any_operator_in_any_region(): void
    {
        $region = Region::query()->create(['name_en' => 'Sahil', 'name_so' => 'Saaxil']);
        $operator = Operator::query()->create(['name' => 'Truecable', 'category' => 'broadcast', 'color' => '#C2410C']);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('towers.store'), $this->minimalTowerPayload($region, $operator))
            ->assertRedirect();

        $this->assertDatabaseHas('towers', [
            'operator_id' => $operator->id,
            'region_id' => $region->id,
        ]);
    }

    public function test_seeded_operators_are_not_limited_to_regions(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $this->assertNotNull(Operator::query()->where('name', 'Somcable')->first());
        $this->assertNull(Operator::query()->where('name', 'Sogasho')->first());

        Operator::query()->each(function (Operator $operator): void {
            $this->assertFalse($operator->regions()->exists(), $operator->name.' should not be limited to a region');
            $this->assertSame($operator->name, $operator->displayName());
        });
    }

    public function test_permissions_are_seeded_in_database(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $this->assertDatabaseHas('permissions', ['key' => 'approvals.review']);
        $this->assertDatabaseHas('role_permission', [
            'role' => 'operations_manager',
        ]);

        $opsTasks = Permissions::tasksForRole('operations_manager');

        $this->assertContains('approvals.review', $opsTasks);
        $this->assertNotContains('audit.view', $opsTasks);
        $this->assertNotContains('applications.view', $opsTasks);
        $this->assertContains('applications.assign', Permissions::tasksForRole('admin'));
        $this->assertContains('applications.review', Permissions::tasksForRole('admin'));
        $this->assertContains('applications.concur', Permissions::tasksForRole('admin'));
        $this->assertContains('applications.grant', Permissions::tasksForRole('admin'));
        $this->assertContains('applications.review', Permissions::tasksForRole('regional_coordinator'));
        $this->assertNotContains('applications.review', Permissions::tasksForRole('section_head'));
        $this->assertContains('applications.assign', Permissions::tasksForRole('section_head'));
        $this->assertContains('applications.concur', Permissions::tasksForRole('department_director'));
        $this->assertContains('applications.grant', Permissions::tasksForRole('director_general'));
        $this->assertNotContains('applications.concur', Permissions::tasksForRole('section_head'));
        $this->assertNotContains('applications.grant', Permissions::tasksForRole('department_director'));
    }

    /**
     * @return array<string, mixed>
     */
    private function minimalTowerPayload(Region $region, Operator $operator): array
    {
        return [
            'latitude' => 9.56,
            'longitude' => 44.06,
            'region_id' => $region->id,
            'city' => 'Berbera',
            'operator_id' => $operator->id,
            'type' => 'monopole',
            'height_m' => 30,
            'capacity' => '4g',
            'power_sources' => ['grid'],
            'signal_radius_m' => 5000,
            'status' => 'active',
            'land_area_preset' => '20x20',
            'fence_distance_preset' => '6',
        ];
    }
}
