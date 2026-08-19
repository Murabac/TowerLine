<?php

namespace Tests\Feature;

use App\Models\Operator;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAdminAndAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_users_and_see_the_audit_log(): void
    {
        $region = Region::query()->create(['name_en' => 'Sahil', 'name_so' => 'Saaxil']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee(__('app.users.title'), false);

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'Sahil Inspector',
                'email' => 'inspector.sahil.test@mocit.local',
                'password' => 'password',
                'role' => 'inspector',
                'region_id' => $region->id,
            ])
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'inspector.sahil.test@mocit.local',
            'role' => 'inspector',
            'region_id' => $region->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'model_type' => User::class,
            'user_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('audit-logs.index'))
            ->assertOk()
            ->assertSee('Sahil Inspector', false)
            ->assertSee(__('app.audit.actions.created'), false)
            ->assertSee(__('app.audit.details'), false);
    }

    public function test_inspector_cannot_open_user_admin_or_audit_log(): void
    {
        $region = Region::query()->create(['name_en' => 'Sahil', 'name_so' => 'Saaxil']);
        $inspector = User::factory()->create([
            'role' => 'inspector',
            'region_id' => $region->id,
        ]);

        $this->actingAs($inspector)
            ->get(route('users.index'))
            ->assertForbidden();

        $this->actingAs($inspector)
            ->get(route('audit-logs.index'))
            ->assertForbidden();
    }

    public function test_operator_viewer_cannot_create_users(): void
    {
        $operator = Operator::query()->create(['name' => 'Telesom', 'category' => 'telecom', 'color' => '#0F766E']);
        $viewer = User::factory()->create([
            'role' => 'operator_viewer',
            'operator_id' => $operator->id,
        ]);

        $this->actingAs($viewer)
            ->post(route('users.store'), [
                'name' => 'Hacker',
                'email' => 'hacker@example.com',
                'password' => 'password',
                'role' => 'admin',
            ])
            ->assertForbidden();
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->delete(route('users.destroy', $admin))
            ->assertForbidden();
    }

    public function test_audit_log_stores_and_shows_change_details(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $log = \App\Models\AuditLog::query()->create([
            'user_id' => $admin->id,
            'action' => 'created',
            'model_type' => User::class,
            'model_id' => $admin->id,
            'changes' => ['name' => 'Demo tower', 'status' => 'active'],
            'created_at' => now(),
        ]);

        $this->assertSame('Demo tower', $log->fresh()->changes['name']);
        $this->assertNotEmpty($log->fresh()->detailLines());

        $this->actingAs($admin)
            ->get(route('audit-logs.index'))
            ->assertOk()
            ->assertSee('Demo tower', false)
            ->assertSee(__('app.audit.details'), false)
            ->assertSee(__('app.audit.fields.status'), false)
            ->assertSee('bg-emerald-100', false);
    }

    public function test_audit_log_seeder_includes_visible_details(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $this->assertGreaterThan(10, \App\Models\AuditLog::query()->count());

        $admin = \App\Models\User::query()->where('email', 'admin@mocit.local')->first();

        $this->actingAs($admin)
            ->get(route('audit-logs.index'))
            ->assertOk()
            ->assertSee(__('app.audit.details'), false)
            ->assertDontSee(__('app.audit.no_details'), false);
    }
}
