<?php

namespace Tests\Feature;

use App\Models\ApprovalRequest;
use App\Models\AuditLog;
use App\Models\Operator;
use App\Models\Region;
use App\Models\Tower;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExistingTowerRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function user(string $email): User
    {
        return User::query()->where('email', $email)->firstOrFail();
    }

    private function coordinator(): User
    {
        return $this->user('coordinator.maroodi@mocit.local');
    }

    private function coordinatorRegion(): Region
    {
        return Region::query()->findOrFail($this->coordinator()->regionIds()[0]);
    }

    /**
     * A point well away from every seeded tower and pending registration.
     */
    private function form(array $overrides = []): array
    {
        return array_merge([
            'operator_id' => Operator::query()->value('id'),
            'region_id' => $this->coordinatorRegion()->id,
            'district_id' => null,
            'latitude' => 9.9001234,
            'longitude' => 43.2004321,
            'type' => 'monopole',
            'height_m' => 30,
            'site_name' => '',
        ], $overrides);
    }

    public function test_coordinator_registers_a_tower_that_waits_for_the_director(): void
    {
        $towers = Tower::query()->count();

        $response = $this->actingAs($this->coordinator())->post(route('towers.existing.store'), $this->form());

        $approval = ApprovalRequest::query()->where('type', ApprovalRequest::TYPE_TOWER_EXISTING)->latest('id')->firstOrFail();
        $response->assertRedirect(route('approvals.show', $approval));
        $this->assertSame(ApprovalRequest::STATUS_PENDING, $approval->status);
        $this->assertSame(30.0, (float) $approval->attributes()['height_m']);
        $this->assertSame($towers, Tower::query()->count(), 'No live tower before confirmation.');

        $this->actingAs($this->coordinator())->get(route('approvals.show', $approval))->assertOk();
    }

    public function test_form_opens_for_registering_roles_only(): void
    {
        $this->actingAs($this->coordinator())->get(route('towers.existing.create'))
            ->assertOk()
            ->assertSee(__('app.existing_towers.title'));
        $this->actingAs($this->user('section.head@mocit.local'))->get(route('towers.existing.create'))->assertOk();
        $this->actingAs($this->user('director@mocit.local'))->get(route('towers.existing.create'))->assertForbidden();
        $this->actingAs($this->user('viewer.telesom@mocit.local'))->get(route('towers.existing.create'))->assertForbidden();
        $this->actingAs($this->user('viewer.telesom@mocit.local'))->post(route('towers.existing.store'), $this->form())->assertForbidden();
    }

    public function test_coordinator_cannot_register_outside_their_region(): void
    {
        $other = Region::query()->whereKeyNot($this->coordinatorRegion()->id)->firstOrFail();

        $this->actingAs($this->coordinator())
            ->post(route('towers.existing.store'), $this->form(['region_id' => $other->id]))
            ->assertSessionHasErrors('region_id');

        $this->assertSame(0, ApprovalRequest::query()->where('type', ApprovalRequest::TYPE_TOWER_EXISTING)->count());
    }

    public function test_section_head_registers_anywhere_but_cannot_confirm(): void
    {
        $sectionHead = $this->user('section.head@mocit.local');
        $other = Region::query()->whereKeyNot($this->coordinatorRegion()->id)->firstOrFail();

        $this->actingAs($sectionHead)->post(route('towers.existing.store'), $this->form(['region_id' => $other->id]))->assertSessionHasNoErrors();
        $approval = ApprovalRequest::query()->where('type', ApprovalRequest::TYPE_TOWER_EXISTING)->firstOrFail();

        $this->actingAs($sectionHead)->post(route('approvals.approve', $approval))->assertForbidden();
        $this->actingAs($this->user('dg@mocit.local'))->post(route('approvals.approve', $approval))->assertForbidden();
        $this->assertTrue($approval->fresh()->isPending());
    }

    public function test_a_tower_within_50_m_needs_an_explicit_confirmation(): void
    {
        $existing = Tower::query()->firstOrFail();
        $nearby = $this->form([
            'region_id' => $existing->region_id,
            'latitude' => (float) $existing->latitude + 0.0002, // about 22 m north
            'longitude' => (float) $existing->longitude,
        ]);
        $sectionHead = $this->user('section.head@mocit.local');

        $this->actingAs($sectionHead)->post(route('towers.existing.store'), $nearby)
            ->assertSessionHasErrors('not_duplicate')
            ->assertSessionHas('existing_tower_duplicates', fn ($matches) => $matches[0]['name'] === $existing->name);
        $this->assertSame(0, ApprovalRequest::query()->where('type', ApprovalRequest::TYPE_TOWER_EXISTING)->count());

        $this->actingAs($sectionHead)->post(route('towers.existing.store'), $nearby + ['not_duplicate' => '1'])
            ->assertSessionHasNoErrors();
        $this->assertSame(1, ApprovalRequest::query()->where('type', ApprovalRequest::TYPE_TOWER_EXISTING)->count());
    }

    public function test_director_confirms_and_a_live_tower_is_created(): void
    {
        $this->actingAs($this->coordinator())->post(route('towers.existing.store'), $this->form());
        $approval = ApprovalRequest::query()->where('type', ApprovalRequest::TYPE_TOWER_EXISTING)->firstOrFail();
        $director = $this->user('director@mocit.local');

        $this->actingAs($director)->get(route('approvals.index'))->assertOk()->assertSee($approval->summary());
        $this->actingAs($director)->get(route('approvals.show', $approval))->assertOk()->assertSee(__('app.existing_towers.confirm'));

        $this->actingAs($director)->post(route('approvals.approve', $approval))->assertRedirect();

        $tower = Tower::query()->latest('id')->firstOrFail();
        $this->assertSame(ApprovalRequest::STATUS_APPROVED, $approval->fresh()->status);
        $this->assertSame('active', $tower->status);
        $this->assertSame('monopole', $tower->type);
        $this->assertNull($tower->capacity);
        $this->assertSame(300, (int) $tower->signal_radius_m, 'Blank radius falls back to the default.');
        $this->assertSame($this->coordinatorRegion()->id, (int) $tower->region_id);
        $this->assertNotNull($tower->district_id, 'District is worked out from the location.');
        $this->assertNotSame('', $tower->name);
        $this->assertTrue(AuditLog::query()->where('model_type', Tower::class)->where('model_id', $tower->id)->where('action', 'created')->exists());

        $this->actingAs($director)->get(route('towers.show', $tower))->assertOk();
    }

    public function test_optional_signal_radius_is_kept_and_validated(): void
    {
        $this->actingAs($this->coordinator())
            ->post(route('towers.existing.store'), $this->form(['signal_radius_m' => 50]))
            ->assertSessionHasErrors('signal_radius_m');

        $this->actingAs($this->coordinator())
            ->post(route('towers.existing.store'), $this->form(['signal_radius_m' => 2500]))
            ->assertSessionHasNoErrors();
        $approval = ApprovalRequest::query()->where('type', ApprovalRequest::TYPE_TOWER_EXISTING)->firstOrFail();
        $this->assertSame(2500, $approval->attributes()['signal_radius_m']);

        $director = $this->user('director@mocit.local');
        $this->actingAs($director)->get(route('approvals.show', $approval))->assertSee('2,500 m');
        $this->actingAs($director)->post(route('approvals.approve', $approval));

        $this->assertSame(2500, (int) Tower::query()->latest('id')->value('signal_radius_m'));
    }

    private function confirmedTower(): Tower
    {
        $this->actingAs($this->coordinator())->post(route('towers.existing.store'), $this->form());
        $approval = ApprovalRequest::query()->where('type', ApprovalRequest::TYPE_TOWER_EXISTING)->latest('id')->firstOrFail();
        $this->actingAs($this->user('director@mocit.local'))->post(route('approvals.approve', $approval));

        return Tower::query()->latest('id')->firstOrFail();
    }

    private function completionForm(Tower $tower, array $overrides = []): array
    {
        return array_merge([
            'latitude' => $tower->latitude,
            'longitude' => $tower->longitude,
            'region_id' => $tower->region_id,
            'district_id' => $tower->district_id,
            'sub_district_id' => $tower->sub_district_id,
            'city' => $tower->city,
            'operator_id' => $tower->operator_id,
            'type' => $tower->type,
            'height_m' => $tower->height_m,
            'capacity' => '4g',
            'power_sources' => ['grid', 'generator'],
            'signal_radius_m' => 5000,
            'status' => 'active',
            'land_area_preset' => '20x20',
            'fence_distance_preset' => '6',
        ], $overrides);
    }

    public function test_director_completes_a_confirmed_existing_tower_directly(): void
    {
        $tower = $this->confirmedTower();
        $director = $this->user('director@mocit.local');
        $this->assertTrue($tower->registered_existing);

        $this->actingAs($director)->get(route('towers.show', $tower))
            ->assertSee(__('app.existing_towers.complete_details'));
        $this->actingAs($director)->get(route('towers.edit', $tower))->assertOk();

        $this->actingAs($director)
            ->put(route('towers.update', $tower), $this->completionForm($tower))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('towers.show', $tower));

        $tower->refresh();
        $this->assertSame('4g', $tower->capacity);
        $this->assertSame(['grid', 'generator'], $tower->power_sources);
        $this->assertSame(0, ApprovalRequest::query()->where('type', ApprovalRequest::TYPE_TOWER_UPDATE)->where('tower_id', $tower->id)->count(), 'Saved straight away, not queued.');
        $this->assertTrue($tower->registered_existing);

        $this->actingAs($director)->get(route('towers.show', $tower))
            ->assertDontSee(__('app.existing_towers.details_needed'));
    }

    public function test_only_the_director_can_complete_existing_towers(): void
    {
        $tower = $this->confirmedTower();

        foreach (['coordinator.maroodi@mocit.local', 'section.head@mocit.local', 'dg@mocit.local'] as $email) {
            $this->actingAs($this->user($email))->get(route('towers.edit', $tower))->assertForbidden();
            $this->actingAs($this->user($email))->put(route('towers.update', $tower), $this->completionForm($tower))->assertForbidden();
        }

        $this->assertNull($tower->fresh()->capacity);
    }

    public function test_director_cannot_edit_towers_that_were_not_registered_as_existing(): void
    {
        $other = Tower::query()->where('registered_existing', false)->firstOrFail();
        $director = $this->user('director@mocit.local');

        $this->actingAs($director)->get(route('towers.edit', $other))->assertForbidden();
        $this->actingAs($director)->put(route('towers.update', $other), $this->completionForm($other))->assertForbidden();
    }

    public function test_registered_existing_flag_cannot_be_set_through_the_tower_form(): void
    {
        $other = Tower::query()->where('registered_existing', false)->firstOrFail();

        $this->actingAs($this->user('superadmin@mocit.local'))
            ->put(route('towers.update', $other), $this->completionForm($other, ['registered_existing' => 1]));

        $this->assertFalse($other->fresh()->registered_existing);
    }

    public function test_director_rejects_with_a_reason(): void
    {
        $this->actingAs($this->coordinator())->post(route('towers.existing.store'), $this->form());
        $approval = ApprovalRequest::query()->where('type', ApprovalRequest::TYPE_TOWER_EXISTING)->firstOrFail();
        $director = $this->user('director@mocit.local');
        $towers = Tower::query()->count();

        $this->actingAs($director)->post(route('approvals.reject', $approval))->assertSessionHasErrors('reviewer_comment');
        $this->actingAs($director)->post(route('approvals.reject', $approval), ['reviewer_comment' => 'Tower was taken down'])->assertRedirect();

        $this->assertSame(ApprovalRequest::STATUS_REJECTED, $approval->fresh()->status);
        $this->assertSame($towers, Tower::query()->count());
    }

    public function test_submitter_and_director_can_correct_a_pending_registration(): void
    {
        $this->actingAs($this->coordinator())->post(route('towers.existing.store'), $this->form());
        $approval = ApprovalRequest::query()->where('type', ApprovalRequest::TYPE_TOWER_EXISTING)->firstOrFail();

        $this->actingAs($this->coordinator())->get(route('approvals.edit', $approval))
            ->assertRedirect(route('approvals.existing.edit', $approval));
        $this->actingAs($this->coordinator())->get(route('approvals.existing.edit', $approval))->assertOk();
        $this->actingAs($this->coordinator())
            ->put(route('approvals.existing.update', $approval), $this->form(['height_m' => 45, 'site_name' => 'Hargeisa Hill']))
            ->assertRedirect(route('approvals.show', $approval));

        $this->actingAs($this->user('director@mocit.local'))
            ->put(route('approvals.existing.update', $approval), $this->form(['type' => 'guyed', 'site_name' => 'Hargeisa Hill']))
            ->assertSessionHasNoErrors();

        $attributes = $approval->fresh()->attributes();
        $this->assertSame('guyed', $attributes['type']);
        $this->assertSame('Hargeisa Hill', $approval->fresh()->summary());

        $this->actingAs($this->user('section.head@mocit.local'))->get(route('approvals.existing.edit', $approval))->assertForbidden();
    }

    public function test_registrations_are_hidden_from_other_staff(): void
    {
        $this->actingAs($this->coordinator())->post(route('towers.existing.store'), $this->form());
        $approval = ApprovalRequest::query()->where('type', ApprovalRequest::TYPE_TOWER_EXISTING)->firstOrFail();

        $this->actingAs($this->user('section.head@mocit.local'))->get(route('approvals.show', $approval))->assertForbidden();
        $this->actingAs($this->user('section.head@mocit.local'))->get(route('approvals.index'))->assertOk()->assertDontSee($approval->summary());
        $this->actingAs($this->user('viewer.telesom@mocit.local'))->get(route('approvals.index'))->assertForbidden();
    }
}
