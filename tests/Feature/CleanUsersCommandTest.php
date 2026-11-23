<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Inspection;
use App\Models\Region;
use App\Models\Role;
use App\Models\Tower;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CleanUsersCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $planPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->planPath = sys_get_temp_dir().'/towerline-plan-'.uniqid().'.json';
    }

    protected function tearDown(): void
    {
        @unlink($this->planPath);

        parent::tearDown();
    }

    private function writePlan(array $accounts): void
    {
        file_put_contents($this->planPath, json_encode(['accounts' => $accounts]));
    }

    private function realPlan(): array
    {
        return [
            ['match' => null, 'name' => 'M2B Support', 'email' => 'support@m2b.example', 'role' => 'admin'],
            ['match' => 'section.head@mocit.local', 'name' => 'Real Section Head', 'email' => 'sectionhead@mocit.example', 'role' => 'section_head'],
            ['match' => 'coordinator.maroodi@mocit.local', 'name' => 'Real Coordinator', 'email' => 'coordinator@mocit.example', 'role' => 'regional_coordinator', 'regions' => ['Maroodi Jeex']],
            ['match' => 'director@mocit.local', 'name' => 'Real Director', 'email' => 'director@mocit.example', 'role' => 'department_director'],
            ['match' => 'dg@mocit.local', 'name' => 'Real DG', 'email' => 'dg@mocit.example', 'role' => 'director_general'],
        ];
    }

    public function test_dry_run_changes_nothing(): void
    {
        $this->writePlan($this->realPlan());
        $before = User::query()->orderBy('id')->get(['id', 'email', 'password', 'deactivated_at'])->toArray();
        $auditCount = AuditLog::query()->count();

        $this->artisan('towers:clean-users', ['--plan' => $this->planPath])
            ->expectsOutputToContain('Dry run: nothing was changed')
            ->assertSuccessful();

        $this->assertSame($before, User::query()->orderBy('id')->get(['id', 'email', 'password', 'deactivated_at'])->toArray());
        $this->assertSame($auditCount, AuditLog::query()->count());
    }

    public function test_force_converts_real_accounts_and_retires_demo_logins(): void
    {
        $this->writePlan($this->realPlan());
        $sectionHead = User::query()->where('email', 'section.head@mocit.local')->firstOrFail();
        $superadmin = User::query()->where('email', 'superadmin@mocit.local')->firstOrFail();
        Inspection::query()->create([
            'tower_id' => Tower::query()->firstOrFail()->id,
            'inspector_id' => $superadmin->id,
            'inspected_at' => now(),
        ]);
        $inspections = Inspection::query()->count();

        $this->artisan('towers:clean-users', ['--plan' => $this->planPath, '--force' => true])
            ->expectsOutputToContain('Done.')
            ->assertSuccessful();

        // Converted in place: same id, so signatures and history stay attached.
        $sectionHead->refresh();
        $this->assertSame('sectionhead@mocit.example', $sectionHead->email);
        $this->assertSame('Real Section Head', $sectionHead->name);
        $this->assertNull($sectionHead->deactivated_at);
        $this->assertFalse(Hash::check('password', $sectionHead->password));

        $coordinator = User::query()->where('email', 'coordinator@mocit.example')->firstOrFail();
        $this->assertSame(['Maroodi Jeex'], $coordinator->regions->pluck('name_en')->all());

        $admin = User::query()->where('email', 'support@m2b.example')->firstOrFail();
        $this->assertSame('admin', $admin->role);
        $this->assertNull($admin->deactivated_at);

        // Linked demo account: deactivated, not deleted, records untouched.
        $superadmin->refresh();
        $this->assertNotNull($superadmin->deactivated_at);
        $this->assertFalse(Hash::check('password', $superadmin->password));
        $this->assertSame($inspections, Inspection::query()->count());

        // Demo account with nothing linked: deleted.
        $this->assertDatabaseMissing('users', ['email' => 'ops@mocit.local']);

        // No demo login can still sign in with "password".
        $this->assertSame(0, User::query()->active()->where('email', 'like', '%.local')->count());

        // Unused custom role removed, built-in roles kept.
        $this->assertDatabaseMissing('roles', ['key' => 'regional_analyst']);
        $this->assertDatabaseMissing('role_permission', ['role' => 'regional_analyst']);
        $this->assertDatabaseHas('roles', ['key' => 'operator_viewer']);

        // Audited as System.
        $this->assertDatabaseHas('audit_logs', ['action' => 'deactivated', 'model_id' => $superadmin->id, 'user_id' => null]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'updated', 'model_id' => $sectionHead->id, 'user_id' => null]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'created', 'model_id' => $admin->id, 'user_id' => null]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'deleted', 'model_type' => Role::class, 'user_id' => null]);
    }

    public function test_it_refuses_a_plan_without_an_admin(): void
    {
        $plan = array_values(array_filter($this->realPlan(), fn ($row) => $row['role'] !== 'admin'));
        $this->writePlan($plan);
        $before = DB::table('users')->count();

        $this->artisan('towers:clean-users', ['--plan' => $this->planPath, '--force' => true])
            ->expectsOutputToContain('at least one admin')
            ->assertFailed();

        $this->assertSame($before, DB::table('users')->count());
        $this->assertDatabaseHas('users', ['email' => 'superadmin@mocit.local', 'deactivated_at' => null]);
    }

    public function test_it_refuses_demo_addresses_and_missing_regions(): void
    {
        $plan = $this->realPlan();
        $plan[1]['email'] = 'someone@mocit.local';
        $this->writePlan($plan);

        $this->artisan('towers:clean-users', ['--plan' => $this->planPath, '--force' => true])
            ->expectsOutputToContain('demo address')
            ->assertFailed();

        $plan = $this->realPlan();
        unset($plan[2]['regions']);
        $this->writePlan($plan);

        $this->artisan('towers:clean-users', ['--plan' => $this->planPath, '--force' => true])
            ->expectsOutputToContain('needs at least one region')
            ->assertFailed();

        $this->assertDatabaseHas('users', ['email' => 'section.head@mocit.local']);
    }

    public function test_seeding_on_production_creates_no_demo_logins(): void
    {
        User::query()->delete();
        DB::table('roles')->where('key', 'regional_analyst')->delete();
        $this->app['env'] = 'production';

        $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

        $this->assertSame(0, User::query()->count());
        $this->assertDatabaseMissing('roles', ['key' => 'regional_analyst']);
        $this->assertDatabaseHas('roles', ['key' => 'section_head']);
    }

    public function test_deactivated_users_cannot_sign_in_and_are_signed_out(): void
    {
        $region = Region::query()->firstOrFail();
        $user = User::factory()->create(['role' => 'admin', 'deactivated_at' => now()]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->assertGuest();

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();

        $coordinator = User::factory()->create([
            'role' => Role::KEY_REGIONAL_COORDINATOR,
            'region_id' => $region->id,
            'deactivated_at' => now(),
        ]);
        $this->assertFalse(\App\Models\SiteApplication::assigneesForRegion($region->id)->contains('id', $coordinator->id));
    }
}
