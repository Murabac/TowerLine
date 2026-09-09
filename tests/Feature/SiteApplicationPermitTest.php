<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Operator;
use App\Models\Region;
use App\Models\Role;
use App\Models\SiteApplication;
use App\Models\SubDistrict;
use App\Models\Tower;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteApplicationPermitTest extends TestCase
{
    use RefreshDatabase;

    public function test_director_yes_sends_the_file_to_the_director_general(): void
    {
        [$application, $coordinator, $director, $dg] = $this->fileWaitingForDirector();

        $this->actingAs($director)
            ->get(route('applications.show', $application))
            ->assertOk()
            ->assertSee(__('app.applications.concur_title'), false);

        $this->actingAs($director)
            ->from(route('applications.show', $application))
            ->post(route('applications.concur', $application), array_merge($this->signatureFields(), [
                'decision' => SiteApplication::DECISION_YES,
                'director_remarks' => 'Site meets ministry spacing.',
                'director_name' => 'Amina Director',
            ]))
            ->assertRedirect(route('applications.show', $application))
            ->assertSessionHas('status');

        $application->refresh();
        $this->assertSame(SiteApplication::STATUS_DG_REVIEW, $application->status);
        $this->assertSame('Amina Director', $application->director_name);
        $this->assertSame($director->id, $application->director_reviewed_by);
        $this->assertFalse($application->canReceiveDirectorDecision());
        $this->assertTrue($application->canReceiveDgDecision());

        $this->actingAs($dg)
            ->get(route('applications.index'))
            ->assertOk()
            ->assertSee($application->site_name, false);

        $this->actingAs($dg)
            ->get(route('applications.show', $application))
            ->assertOk()
            ->assertSee(__('app.applications.grant_title'), false)
            ->assertSee('Amina Director', false);

        $this->actingAs($coordinator)
            ->get(route('applications.show', $application))
            ->assertOk()
            ->assertDontSee(__('app.applications.concur_title'), false)
            ->assertDontSee(__('app.applications.grant_title'), false);
    }

    public function test_director_no_refuses_the_file_without_a_tower(): void
    {
        [$application, $coordinator, $director] = $this->fileWaitingForDirector();
        $head = User::factory()->create(['role' => Role::KEY_SECTION_HEAD]);

        $this->actingAs($director)
            ->post(route('applications.concur', $application), array_merge($this->signatureFields(), [
                'decision' => SiteApplication::DECISION_NO,
                'director_remarks' => 'Too close to the school.',
                'director_name' => 'Amina Director',
            ]))
            ->assertRedirect(route('applications.show', $application));

        $application->refresh();
        $this->assertSame(SiteApplication::STATUS_REFUSED, $application->status);
        $this->assertNull($application->tower_id);
        $this->assertFalse($application->canBeAssigned());
        $this->assertSame(0, Tower::query()->count());

        $this->actingAs($head)
            ->post(route('applications.assign', $application), [
                'assigned_to' => $coordinator->id,
            ])
            ->assertForbidden();

        $this->actingAs($head)
            ->get(route('applications.index'))
            ->assertOk()
            ->assertDontSee($application->site_name, false);
    }

    public function test_dg_grant_creates_a_tower_letter_and_map_pin(): void
    {
        [$application, $coordinator, $director, $dg] = $this->fileWaitingForDirector();

        $this->actingAs($director)->post(route('applications.concur', $application), array_merge($this->signatureFields(), [
            'decision' => SiteApplication::DECISION_YES,
            'director_remarks' => 'Concur.',
            'director_name' => 'Amina Director',
        ]));

        $this->actingAs($dg)
            ->post(route('applications.grant', $application), array_merge($this->signatureFields(), [
                'decision' => SiteApplication::DECISION_GRANT,
                'dg_remarks' => 'Permit granted.',
                'dg_name' => 'Hassan DG',
            ]))
            ->assertRedirect(route('applications.show', $application))
            ->assertSessionHas('status');

        $application->refresh();
        $this->assertSame(SiteApplication::STATUS_GRANTED, $application->status);
        $this->assertNotNull($application->tower_id);

        $tower = $application->tower()->with('currentApprovalLetter')->first();
        $this->assertNotNull($tower);
        $this->assertSame('under_construction', $tower->status);
        $this->assertSame($application->operator_id, $tower->operator_id);
        $this->assertNotNull($tower->currentApprovalLetter);
        $this->assertSame($dg->id, $tower->currentApprovalLetter->issued_by);
        $this->assertStringStartsWith('MoCIT/ISG/', $tower->currentApprovalLetter->reference_number);

        $this->actingAs($dg)
            ->getJson(route('map.towers'))
            ->assertOk()
            ->assertJsonFragment(['name' => $tower->name, 'id' => $tower->id]);

        $this->actingAs($dg)
            ->get(route('applications.permit.print', ['application' => $application, 'copy' => 'hq']))
            ->assertOk()
            ->assertSee($application->reference_number, false)
            ->assertSee('Amina Director', false)
            ->assertSee('Hassan DG', false)
            ->assertSee('data:image/png;base64,', false)
            ->assertDontSee(__('app.applications.permit_customer_banner'), false);

        $this->actingAs($dg)
            ->get(route('applications.permit.print', ['application' => $application, 'copy' => 'customer']))
            ->assertOk()
            ->assertSee(__('app.applications.permit_customer_banner'), false)
            ->assertSee($application->reference_number, false);
    }

    public function test_dg_return_sends_the_file_back_to_the_director(): void
    {
        [$application, $coordinator, $director, $dg] = $this->fileWaitingForDirector();

        $this->actingAs($director)->post(route('applications.concur', $application), array_merge($this->signatureFields(), [
            'decision' => SiteApplication::DECISION_YES,
            'director_remarks' => 'Concur.',
            'director_name' => 'Amina Director',
        ]));

        $this->actingAs($dg)
            ->post(route('applications.grant', $application), array_merge($this->signatureFields(), [
                'decision' => SiteApplication::DECISION_RETURN,
                'dg_remarks' => 'Need a clearer visit note.',
                'dg_name' => 'Hassan DG',
            ]))
            ->assertRedirect(route('applications.show', $application));

        $application->refresh();
        $this->assertSame(SiteApplication::STATUS_DIRECTOR_REVIEW, $application->status);
        $this->assertNull($application->tower_id);
        $this->assertTrue($application->canReceiveDirectorDecision());

        $this->actingAs($director)
            ->get(route('applications.show', $application))
            ->assertOk()
            ->assertSee(__('app.applications.concur_title'), false)
            ->assertSee('Need a clearer visit note.', false);

        $this->actingAs($director)
            ->post(route('applications.concur', $application), array_merge($this->signatureFields(), [
                'decision' => SiteApplication::DECISION_YES,
                'director_remarks' => 'Visit note clarified.',
                'director_name' => 'Amina Director',
            ]))
            ->assertRedirect(route('applications.show', $application));

        $this->assertSame(SiteApplication::STATUS_DG_REVIEW, $application->fresh()->status);
    }

    public function test_coordinator_cannot_concur_and_director_cannot_grant(): void
    {
        [$application, $coordinator, $director, $dg] = $this->fileWaitingForDirector();

        $payload = array_merge($this->signatureFields(), [
            'decision' => SiteApplication::DECISION_YES,
            'director_remarks' => 'OK.',
            'director_name' => 'Someone',
        ]);

        $this->actingAs($coordinator)
            ->post(route('applications.concur', $application), $payload)
            ->assertForbidden();

        $this->actingAs($director)->post(route('applications.concur', $application), $payload);

        $this->actingAs($director)
            ->post(route('applications.grant', $application), array_merge($this->signatureFields(), [
                'decision' => SiteApplication::DECISION_GRANT,
                'dg_remarks' => 'Grant.',
                'dg_name' => 'Hassan DG',
            ]))
            ->assertForbidden();

        $this->assertSame(SiteApplication::STATUS_DG_REVIEW, $application->fresh()->status);
        $this->actingAs($dg)->get(route('applications.show', $application))->assertOk();
    }

    public function test_director_and_dg_decisions_require_remarks_and_name(): void
    {
        [$application, $coordinator, $director, $dg] = $this->fileWaitingForDirector();

        $this->actingAs($director)
            ->from(route('applications.show', $application))
            ->post(route('applications.concur', $application), [
                'decision' => SiteApplication::DECISION_YES,
            ])
            ->assertRedirect(route('applications.show', $application))
            ->assertSessionHasErrors(['director_remarks', 'director_name']);

        $this->assertSame(SiteApplication::STATUS_DIRECTOR_REVIEW, $application->fresh()->status);

        $this->actingAs($director)->post(route('applications.concur', $application), array_merge($this->signatureFields(), [
            'decision' => SiteApplication::DECISION_YES,
            'director_remarks' => 'Concur.',
            'director_name' => 'Amina Director',
        ]));

        $this->actingAs($dg)
            ->from(route('applications.show', $application))
            ->post(route('applications.grant', $application), [
                'decision' => SiteApplication::DECISION_GRANT,
            ])
            ->assertRedirect(route('applications.show', $application))
            ->assertSessionHasErrors(['dg_remarks', 'dg_name']);

        $this->assertSame(SiteApplication::STATUS_DG_REVIEW, $application->fresh()->status);
    }

    /**
     * @return array{0: SiteApplication, 1: User, 2: User, 3: User}
     */
    private function fileWaitingForDirector(): array
    {
        [$region, $district, $subDistrict, $operator] = $this->geography();
        $application = $this->application($region, $district, $subDistrict, $operator);
        $head = User::factory()->create(['role' => Role::KEY_SECTION_HEAD]);
        $coordinator = User::factory()->create([
            'role' => Role::KEY_REGIONAL_COORDINATOR,
            'region_id' => $region->id,
        ]);
        $director = User::factory()->create(['role' => Role::KEY_DEPARTMENT_DIRECTOR, 'name' => 'Amina Director']);
        $dg = User::factory()->create(['role' => Role::KEY_DIRECTOR_GENERAL, 'name' => 'Hassan DG']);

        $application->assignTo($coordinator, $head);
        $application->recordOfficerReview(
            $coordinator,
            SiteApplication::DECISION_APPROVE,
            now()->toDateString(),
            'Fence and GPS match the plan.',
            'Recommend concurrence.',
        );

        return [$application->fresh(), $coordinator, $director, $dg];
    }

    /**
     * @return array{0: Region, 1: District, 2: SubDistrict, 3: Operator}
     */
    private function geography(): array
    {
        $region = Region::query()->create(['name_en' => 'Maroodi Jeex', 'name_so' => 'Maroodi Jeex']);
        $district = District::query()->create(['region_id' => $region->id, 'name' => 'Hargeisa']);
        $subDistrict = SubDistrict::query()->create(['district_id' => $district->id, 'name' => 'Ahmed Dhagah']);
        $operator = Operator::query()->create([
            'name' => 'Telesom',
            'category' => 'telecom',
            'color' => '#0F766E',
        ]);

        return [$region, $district, $subDistrict, $operator];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function application(Region $region, District $district, SubDistrict $subDistrict, Operator $operator, array $overrides = []): SiteApplication
    {
        return SiteApplication::query()->create(array_merge([
            'reference_number' => 'MoCIT/APP/2026/'.str_pad((string) random_int(100, 999), 4, '0', STR_PAD_LEFT),
            'contact_name' => 'Amina Hassan',
            'telephone' => '+252 63 4000000',
            'operator_id' => $operator->id,
            'license_class_no' => 'A-12/2026',
            'email' => 'amina@example.com',
            'address' => 'Hargeisa',
            'site_name' => 'New Hargeisa Site',
            'region_id' => $region->id,
            'district_id' => $district->id,
            'sub_district_id' => $subDistrict->id,
            'latitude' => 9.562,
            'longitude' => 44.077,
            'type' => 'monopole',
            'height_m' => 40,
            'capacity' => '4g',
            'letter_path' => 'site-applications/inbox/letter.pdf',
            'layout_path' => 'site-applications/inbox/layout.pdf',
            'radio_path' => 'site-applications/inbox/radio.pdf',
            'icnirp_path' => 'site-applications/inbox/icnirp.pdf',
            'status' => SiteApplication::STATUS_RECEIVED,
        ], $overrides));
    }
}
