<?php

namespace Tests\Feature;

use App\Models\License;
use App\Models\Operator;
use App\Models\Region;
use App\Models\Tower;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleScopeAndHandoverTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_public_registration_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();

        $this->post('/register', [
            'name' => 'Hacker',
            'email' => 'hacker@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();
    }

    public function test_admin_lands_on_the_dashboard_after_login(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_inspector_cannot_open_a_tower_outside_their_region(): void
    {
        [$awdal, $sahil, $telesom] = $this->fixtures();
        $home = $this->tower('Awdal site', $awdal, $telesom);
        $away = $this->tower('Sahil site', $sahil, $telesom);

        $inspector = User::factory()->create([
            'role' => 'inspector',
            'region_id' => $awdal->id,
        ]);

        $this->actingAs($inspector)
            ->get(route('towers.index'))
            ->assertOk()
            ->assertSee('Awdal site', false)
            ->assertDontSee('Sahil site', false);

        $this->actingAs($inspector)
            ->get(route('towers.show', $home))
            ->assertOk();

        $this->actingAs($inspector)
            ->get(route('towers.show', $away))
            ->assertForbidden();

        $this->actingAs($inspector)
            ->get(route('towers.edit', $away))
            ->assertForbidden();
    }

    public function test_inspector_with_several_regions_can_open_those_towers_only(): void
    {
        [$awdal, $sahil, $telesom] = $this->fixtures();
        $togdheer = Region::query()->create(['name_en' => 'Togdheer', 'name_so' => 'Togdheer']);

        $west = $this->tower('Awdal site', $awdal, $telesom);
        $coast = $this->tower('Sahil site', $sahil, $telesom);
        $east = $this->tower('Burao site', $togdheer, $telesom);

        $inspector = User::factory()->create([
            'role' => 'inspector',
            'region_id' => $awdal->id,
        ]);
        $inspector->syncInspectorRegions([$awdal->id, $sahil->id]);

        $this->actingAs($inspector->fresh())
            ->get(route('towers.index'))
            ->assertOk()
            ->assertSee('Awdal site', false)
            ->assertSee('Sahil site', false)
            ->assertDontSee('Burao site', false);

        $this->actingAs($inspector->fresh())
            ->get(route('towers.show', $west))
            ->assertOk();

        $this->actingAs($inspector->fresh())
            ->get(route('towers.show', $coast))
            ->assertOk();

        $this->actingAs($inspector->fresh())
            ->get(route('towers.show', $east))
            ->assertForbidden();
    }

    public function test_operator_viewer_cannot_create_towers(): void
    {
        [$awdal, , $telesom] = $this->fixtures();
        $viewer = User::factory()->create([
            'role' => 'operator_viewer',
            'operator_id' => $telesom->id,
        ]);

        $this->actingAs($viewer)
            ->get(route('towers.create'))
            ->assertForbidden();

        $this->actingAs($viewer)
            ->post(route('towers.store'), [
                'name' => 'Unauthorized',
                'latitude' => 9.56,
                'longitude' => 44.07,
                'region_id' => $awdal->id,
                'operator_id' => $telesom->id,
                'type' => 'monopole',
                'status' => 'active',
            ])
            ->assertForbidden();
    }

    public function test_map_json_filters_expired_licenses(): void
    {
        [$awdal, , $telesom] = $this->fixtures();
        $expiredTower = $this->tower('Expired site', $awdal, $telesom);
        $activeTower = $this->tower('Active site', $awdal, $telesom);

        License::query()->create([
            'tower_id' => $expiredTower->id,
            'operator_id' => $telesom->id,
            'license_type' => 'A',
            'issued_at' => now()->subYears(2),
            'expires_at' => now()->subMonth(),
        ]);
        License::query()->create([
            'tower_id' => $activeTower->id,
            'operator_id' => $telesom->id,
            'license_type' => 'B',
            'issued_at' => now()->subYear(),
            'expires_at' => now()->addYear(),
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->getJson(route('map.towers', ['license_state' => 'expired']))
            ->assertOk()
            ->assertJsonCount(1, 'towers')
            ->assertJsonPath('towers.0.name', 'Expired site');
    }

    public function test_staff_can_switch_language_and_open_help(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('help'))
            ->post(route('locale.update'), ['locale' => 'so'])
            ->assertRedirect(route('help'));

        $this->actingAs($admin)
            ->get(route('help'))
            ->assertOk()
            ->assertSee(__('app.help.title', [], 'so'), false);
    }

    public function test_seeded_demo_covers_the_main_staff_screens(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@mocit.local')->first();
        $inspector = User::query()->where('email', 'inspector.maroodi@mocit.local')->first();

        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('approvals.index'))->assertOk();
        $this->actingAs($admin)->get(route('map'))->assertOk();
        $this->actingAs($admin)->get(route('towers.index'))->assertOk();
        $this->actingAs($admin)->get(route('licenses.index'))->assertOk();
        $this->actingAs($admin)->get(route('users.index'))->assertOk();
        $this->actingAs($admin)->get(route('audit-logs.index'))->assertOk();
        $this->actingAs($admin)->get(route('help'))->assertOk();
        $this->actingAs($admin)->get(route('reports.index'))->assertOk();
        $this->actingAs($admin)->get(route('frequencies.dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('districts.index'))->assertOk();

        $this->assertGreaterThan(50, \App\Models\District::query()->count());
        $this->assertTrue(\App\Models\Operator::query()->where('name', 'Truecable')->first()?->regions()->exists());
        $this->assertGreaterThan(0, \App\Models\BuildApprovalLetter::query()->count());
        $this->assertGreaterThan(0, \App\Models\FrequencyAllocation::query()->count());
        $this->assertGreaterThan(0, \App\Models\ApprovalRequest::query()->pending()->count());
        $this->assertNotNull(User::query()->where('email', 'ops@mocit.local')->first());
        $this->assertNotNull(User::query()->where('email', 'analyst.maroodi@mocit.local')->first());

        $this->actingAs($inspector)->get(route('map'))->assertOk();
        $this->actingAs($inspector)->get(route('approvals.index'))->assertOk();
        $this->actingAs($inspector)->get(route('users.index'))->assertForbidden();
    }

    /**
     * @return array{0: Region, 1: Region, 2: Operator}
     */
    private function fixtures(): array
    {
        return [
            Region::query()->create(['name_en' => 'Awdal', 'name_so' => 'Awdal']),
            Region::query()->create(['name_en' => 'Sahil', 'name_so' => 'Saaxil']),
            Operator::query()->create(['name' => 'Telesom', 'category' => 'telecom', 'color' => '#0F766E']),
        ];
    }

    private function tower(string $name, Region $region, Operator $operator): Tower
    {
        return Tower::query()->create([
            'name' => $name,
            'latitude' => 9.56,
            'longitude' => 44.07,
            'region_id' => $region->id,
            'operator_id' => $operator->id,
            'type' => 'monopole',
            'height_m' => 40,
            'capacity' => '4g',
            'signal_radius_m' => 10000,
            'status' => 'active',
            'health_status' => 'unknown',
        ]);
    }
}
