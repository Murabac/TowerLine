<?php

namespace Tests\Feature;

use App\Models\ApprovalRequest;
use App\Models\Operator;
use App\Models\Region;
use App\Models\Tower;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_inspector_edit_does_not_change_live_tower_until_approved(): void
    {
        [$region, $operator, $tower, $inspector] = $this->inspectorSetup();

        $this->actingAs($inspector)
            ->put(route('towers.update', $tower), $this->towerPayload($region, $operator, [
                'city' => 'Berbera Port',
                'height_m' => 55,
            ]))
            ->assertRedirect(route('towers.show', $tower));

        $this->assertSame('Berbera', $tower->fresh()->city);
        $this->assertEquals(40.0, (float) $tower->fresh()->height_m);
        $this->assertDatabaseHas('approval_requests', [
            'type' => ApprovalRequest::TYPE_TOWER_UPDATE,
            'tower_id' => $tower->id,
            'status' => ApprovalRequest::STATUS_PENDING,
            'submitted_by' => $inspector->id,
        ]);

        $pending = ApprovalRequest::query()->pending()->firstOrFail();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('approvals.show', $pending))
            ->assertOk()
            ->assertSee('Berbera Port', false);

        $this->actingAs($admin)
            ->post(route('approvals.approve', $pending))
            ->assertRedirect(route('towers.show', $tower));

        $this->assertSame('Berbera Port', $tower->fresh()->city);
        $this->assertEquals(55.0, (float) $tower->fresh()->height_m);
        $this->assertSame(ApprovalRequest::STATUS_APPROVED, $pending->fresh()->status);
    }

    public function test_rejection_keeps_live_data_unchanged(): void
    {
        [$region, $operator, $tower, $inspector] = $this->inspectorSetup();

        $this->actingAs($inspector)
            ->put(route('towers.update', $tower), $this->towerPayload($region, $operator, [
                'city' => 'Rejected City',
            ]))
            ->assertRedirect();

        $pending = ApprovalRequest::query()->pending()->firstOrFail();
        $ops = User::factory()->create(['role' => 'operations_manager']);

        $this->actingAs($ops)
            ->post(route('approvals.reject', $pending), [
                'reviewer_comment' => 'Coordinates do not match the site visit.',
            ])
            ->assertRedirect(route('approvals.index'));

        $this->assertSame('Berbera', $tower->fresh()->city);
        $this->assertSame(ApprovalRequest::STATUS_REJECTED, $pending->fresh()->status);
        $this->assertSame('Coordinates do not match the site visit.', $pending->fresh()->reviewer_comment);
    }

    public function test_approval_writes_audit_entry(): void
    {
        [$region, $operator, $tower, $inspector] = $this->inspectorSetup();

        $this->actingAs($inspector)
            ->put(route('towers.update', $tower), $this->towerPayload($region, $operator, [
                'city' => 'Audited City',
            ]));

        $pending = ApprovalRequest::query()->pending()->firstOrFail();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('approvals.approve', $pending))
            ->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'approved',
            'model_type' => ApprovalRequest::class,
            'model_id' => $pending->id,
            'user_id' => $admin->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'updated',
            'model_type' => Tower::class,
            'model_id' => $tower->id,
            'user_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('audit-logs.index'))
            ->assertOk()
            ->assertSee(__('app.audit.actions.approved'), false);
    }

    public function test_inspector_tower_create_is_pending_until_approved(): void
    {
        [$region, $operator] = $this->basics();
        $inspector = User::factory()->create([
            'role' => 'inspector',
            'region_id' => $region->id,
        ]);
        $inspector->syncInspectorRegions([$region->id]);

        $this->actingAs($inspector)
            ->post(route('towers.store'), $this->towerPayload($region, $operator))
            ->assertRedirect(route('towers.index'));

        $this->assertDatabaseCount('towers', 0);
        $this->assertDatabaseHas('approval_requests', [
            'type' => ApprovalRequest::TYPE_TOWER_CREATE,
            'status' => ApprovalRequest::STATUS_PENDING,
        ]);

        $pending = ApprovalRequest::query()->pending()->firstOrFail();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('approvals.approve', $pending))
            ->assertRedirect();

        $this->assertDatabaseCount('towers', 1);
        $this->assertDatabaseHas('towers', ['city' => 'Berbera']);
    }

    public function test_inspector_sees_own_pending_queue_and_can_correct_it(): void
    {
        [$region, $operator, $tower, $inspector] = $this->inspectorSetup();
        $otherRegion = Region::query()->create(['name_en' => 'Maroodi Jeex', 'name_so' => 'Maroodi Jeex']);
        $otherInspector = User::factory()->create([
            'role' => 'inspector',
            'region_id' => $otherRegion->id,
        ]);
        $otherInspector->syncInspectorRegions([$otherRegion->id]);
        $otherTower = Tower::query()->create([
            'name' => 'HRG-MAR-001',
            'latitude' => 9.56,
            'longitude' => 44.06,
            'region_id' => $otherRegion->id,
            'city' => 'Hargeisa',
            'operator_id' => $operator->id,
            'type' => 'monopole',
            'height_m' => 40,
            'capacity' => '4g',
            'signal_radius_m' => 12000,
            'status' => 'active',
            'health_status' => 'unknown',
        ]);

        $this->actingAs($inspector)
            ->put(route('towers.update', $tower), $this->towerPayload($region, $operator, [
                'city' => 'Wrong City',
                'registration_inspector_notes' => 'Fence gap needs follow-up.',
            ]));

        $this->actingAs($otherInspector)
            ->put(route('towers.update', $otherTower), $this->towerPayload($otherRegion, $operator, [
                'city' => 'Secret City',
            ]));

        $own = ApprovalRequest::query()->where('submitted_by', $inspector->id)->firstOrFail();
        $other = ApprovalRequest::query()->where('submitted_by', $otherInspector->id)->firstOrFail();

        $this->actingAs($inspector)
            ->get(route('approvals.index'))
            ->assertOk()
            ->assertSee(__('app.approvals.title_own'), false)
            ->assertSee('BER-SAH-001', false)
            ->assertDontSee('HRG-MAR-001', false);

        $this->actingAs($inspector)
            ->get(route('approvals.show', $other))
            ->assertForbidden();

        $this->actingAs($inspector)
            ->get(route('approvals.edit', $own))
            ->assertOk()
            ->assertSee('Wrong City', false)
            ->assertSee('Fence gap needs follow-up.', false);

        $this->actingAs($inspector)
            ->put(route('approvals.update', $own), $this->towerPayload($region, $operator, [
                'city' => 'Berbera Port',
                'registration_inspector_notes' => 'Corrected: fence is intact.',
            ]))
            ->assertRedirect(route('approvals.show', $own));

        $this->assertSame('Berbera', $tower->fresh()->city);
        $this->assertNull($tower->fresh()->registration_inspector_notes);
        $this->assertSame('Berbera Port', $own->fresh()->attributes()['city']);
        $this->assertSame('Corrected: fence is intact.', $own->fresh()->attributes()['registration_inspector_notes']);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->get(route('approvals.show', $own))
            ->assertOk()
            ->assertSee('Berbera Port', false)
            ->assertSee('Corrected: fence is intact.', false);

        $this->actingAs($inspector)
            ->post(route('approvals.approve', $own))
            ->assertForbidden();
    }

    public function test_inspector_cannot_correct_another_inspectors_submission(): void
    {
        [$region, $operator, $tower, $inspector] = $this->inspectorSetup();
        $other = User::factory()->create([
            'role' => 'inspector',
            'region_id' => $region->id,
        ]);
        $other->syncInspectorRegions([$region->id]);

        $this->actingAs($inspector)
            ->put(route('towers.update', $tower), $this->towerPayload($region, $operator, [
                'city' => 'Owned City',
            ]));

        $pending = ApprovalRequest::query()->pending()->firstOrFail();

        $this->actingAs($other)
            ->get(route('approvals.edit', $pending))
            ->assertForbidden();

        $this->actingAs($other)
            ->put(route('approvals.update', $pending), $this->towerPayload($region, $operator, [
                'city' => 'Hijacked City',
            ]))
            ->assertForbidden();

        $this->assertSame('Owned City', $pending->fresh()->attributes()['city']);
    }

    public function test_inspector_can_correct_a_pending_inspection(): void
    {
        [$region, $operator, $tower, $inspector] = $this->inspectorSetup();

        $this->actingAs($inspector)
            ->post(route('towers.inspections.store', $tower), [
                'notes' => 'Wrong visit notes',
                'power_status' => 'down',
            ])
            ->assertRedirect(route('towers.show', $tower));

        $pending = ApprovalRequest::query()
            ->pending()
            ->where('type', ApprovalRequest::TYPE_INSPECTION)
            ->firstOrFail();

        $this->actingAs($inspector)
            ->get(route('approvals.edit', $pending))
            ->assertOk()
            ->assertSee('Wrong visit notes', false);

        $this->actingAs($inspector)
            ->put(route('approvals.update', $pending), [
                'notes' => 'Corrected visit notes',
                'power_status' => 'on_grid',
            ])
            ->assertRedirect(route('approvals.show', $pending));

        $this->assertSame(0, $tower->inspections()->count());
        $this->assertSame('Corrected visit notes', $pending->fresh()->attributes()['notes']);
        $this->assertSame('on_grid', $pending->fresh()->attributes()['power_status']);
    }

    public function test_admin_still_publishes_towers_immediately(): void
    {
        [$region, $operator] = $this->basics();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('towers.store'), $this->towerPayload($region, $operator))
            ->assertRedirect();

        $this->assertDatabaseCount('towers', 1);
        $this->assertDatabaseCount('approval_requests', 0);
    }

    public function test_dashboard_shows_pending_count_and_queue_for_reviewers(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@mocit.local')->firstOrFail();
        $ops = User::query()->where('email', 'ops@mocit.local')->firstOrFail();
        $pending = ApprovalRequest::query()->pending()->count();

        $this->assertGreaterThan(0, $pending);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('app.dashboard.pending_approvals_banner', ['count' => $pending]), false);

        $this->actingAs($admin)
            ->get(route('approvals.index'))
            ->assertOk()
            ->assertSee(__('app.approvals.title'), false);

        $this->actingAs($ops)
            ->get(route('approvals.index'))
            ->assertOk();

        $inspector = User::query()->where('email', 'inspector.maroodi@mocit.local')->firstOrFail();
        $ownPending = ApprovalRequest::query()->visibleTo($inspector)->pending()->count();

        $this->actingAs($inspector)
            ->get(route('approvals.index'))
            ->assertOk()
            ->assertSee(__('app.approvals.title_own'), false);

        $this->actingAs($inspector)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('app.dashboard.pending_own_submissions_banner', ['count' => $ownPending]), false);
    }

    /**
     * @return array{0: Region, 1: Operator, 2: Tower, 3: User}
     */
    private function inspectorSetup(): array
    {
        [$region, $operator] = $this->basics();
        $tower = Tower::query()->create([
            'name' => 'BER-SAH-001',
            'latitude' => 10.43,
            'longitude' => 45.01,
            'region_id' => $region->id,
            'city' => 'Berbera',
            'operator_id' => $operator->id,
            'type' => 'monopole',
            'height_m' => 40,
            'capacity' => '4g',
            'signal_radius_m' => 12000,
            'status' => 'active',
            'health_status' => 'unknown',
        ]);
        $inspector = User::factory()->create([
            'role' => 'inspector',
            'region_id' => $region->id,
        ]);
        $inspector->syncInspectorRegions([$region->id]);

        return [$region, $operator, $tower, $inspector];
    }

    /**
     * @return array{0: Region, 1: Operator}
     */
    private function basics(): array
    {
        $region = Region::query()->create(['name_en' => 'Sahil', 'name_so' => 'Saaxil']);
        $operator = Operator::query()->create([
            'name' => 'Telesom',
            'category' => 'telecom',
            'color' => '#0F766E',
        ]);

        return [$region, $operator];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function towerPayload(Region $region, Operator $operator, array $overrides = []): array
    {
        return array_merge([
            'latitude' => 10.43,
            'longitude' => 45.01,
            'region_id' => $region->id,
            'city' => 'Berbera',
            'operator_id' => $operator->id,
            'type' => 'monopole',
            'height_m' => 40,
            'capacity' => '4g',
            'power_sources' => ['grid'],
            'signal_radius_m' => 5000,
            'status' => 'active',
            'land_area_preset' => '20x20',
            'fence_distance_preset' => '6',
        ], $overrides);
    }
}
