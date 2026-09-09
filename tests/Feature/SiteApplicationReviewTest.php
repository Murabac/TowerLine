<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Operator;
use App\Models\Region;
use App\Models\Role;
use App\Models\SiteApplication;
use App\Models\SubDistrict;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteApplicationReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_coordinator_reviews_the_file_without_editing_applicant_data(): void
    {
        [$region, $district, $subDistrict, $operator] = $this->geography();
        $application = $this->application($region, $district, $subDistrict, $operator, [
            'site_name' => 'Xero Awr Site',
            'height_m' => 45,
        ]);
        $head = User::factory()->create(['role' => Role::KEY_SECTION_HEAD]);
        $coordinator = User::factory()->create([
            'role' => Role::KEY_REGIONAL_COORDINATOR,
            'region_id' => $region->id,
        ]);
        $application->assignTo($coordinator, $head);

        $this->actingAs($coordinator)
            ->get(route('applications.show', $application))
            ->assertOk()
            ->assertSee('Xero Awr Site', false)
            ->assertSee('45', false)
            ->assertSee(__('app.applications.review_title'), false)
            ->assertSee(__('app.applications.documents_readonly'), false)
            ->assertSee('id="application-map"', false)
            ->assertSee('9.562000', false)
            ->assertDontSee(__('app.applications.assign_to'), false)
            ->assertDontSee('name="contact_name"', false);

        $this->actingAs($head)
            ->get(route('applications.show', $application))
            ->assertOk()
            ->assertDontSee(__('app.applications.review_title'), false);
    }

    public function test_coordinator_approve_sends_the_file_to_the_department_director(): void
    {
        [$region, $district, $subDistrict, $operator] = $this->geography();
        $application = $this->application($region, $district, $subDistrict, $operator);
        $head = User::factory()->create(['role' => Role::KEY_SECTION_HEAD]);
        $coordinator = User::factory()->create([
            'role' => Role::KEY_REGIONAL_COORDINATOR,
            'region_id' => $region->id,
        ]);
        $director = User::factory()->create(['role' => Role::KEY_DEPARTMENT_DIRECTOR]);
        $application->assignTo($coordinator, $head);

        $this->actingAs($coordinator)
            ->from(route('applications.show', $application))
            ->post(route('applications.review', $application), array_merge($this->signatureFields(), [
                'decision' => SiteApplication::DECISION_APPROVE,
                'site_visit_on' => now()->toDateString(),
                'site_visit_notes' => 'Fence and GPS match the plan.',
                'officer_remarks' => 'Recommend concurrence.',
            ]))
            ->assertRedirect(route('applications.show', $application))
            ->assertSessionHas('status');

        $application->refresh();
        $this->assertSame(SiteApplication::STATUS_DIRECTOR_REVIEW, $application->status);
        $this->assertSame('Fence and GPS match the plan.', $application->site_visit_notes);
        $this->assertSame('Recommend concurrence.', $application->officer_remarks);
        $this->assertSame($coordinator->id, $application->officer_reviewed_by);
        $this->assertNotNull($application->officer_signature_path);
        $this->assertFalse($application->canBeAssigned());
        $this->assertFalse($application->canBeReviewed());

        $this->actingAs($director)
            ->get(route('applications.index'))
            ->assertOk()
            ->assertSee($application->site_name, false);

        $this->actingAs($director)
            ->get(route('applications.show', $application))
            ->assertOk()
            ->assertSee('Recommend concurrence.', false)
            ->assertDontSee(__('app.applications.review_title'), false);

        $this->actingAs($head)
            ->post(route('applications.assign', $application), [
                'assigned_to' => $coordinator->id,
            ])
            ->assertForbidden();

        $this->actingAs($coordinator)
            ->get(route('applications.show', $application))
            ->assertOk()
            ->assertDontSee(__('app.applications.review_title'), false);
    }

    public function test_coordinator_reject_returns_the_file_to_the_section_head(): void
    {
        [$region, $district, $subDistrict, $operator] = $this->geography();
        $application = $this->application($region, $district, $subDistrict, $operator);
        $head = User::factory()->create(['role' => Role::KEY_SECTION_HEAD]);
        $coordinator = User::factory()->create([
            'role' => Role::KEY_REGIONAL_COORDINATOR,
            'region_id' => $region->id,
        ]);
        $application->assignTo($coordinator, $head);

        $this->actingAs($coordinator)
            ->post(route('applications.review', $application), array_merge($this->signatureFields(), [
                'decision' => SiteApplication::DECISION_REJECT,
                'site_visit_on' => now()->toDateString(),
                'site_visit_notes' => 'Pin is 200 m from the described plot.',
                'officer_remarks' => 'Return for a corrected GPS map.',
            ]))
            ->assertRedirect(route('applications.show', $application));

        $application->refresh();
        $this->assertSame(SiteApplication::STATUS_RETURNED, $application->status);
        $this->assertTrue($application->canBeAssigned());

        $this->actingAs($head)
            ->get(route('applications.index'))
            ->assertOk()
            ->assertSee($application->site_name, false);

        $this->actingAs($head)
            ->post(route('applications.assign', $application), [
                'assigned_to' => $coordinator->id,
            ])
            ->assertRedirect(route('applications.show', $application));

        $this->assertSame(SiteApplication::STATUS_ASSIGNED, $application->fresh()->status);
    }

    public function test_review_requires_a_site_visit_and_remarks(): void
    {
        [$region, $district, $subDistrict, $operator] = $this->geography();
        $application = $this->application($region, $district, $subDistrict, $operator);
        $head = User::factory()->create(['role' => Role::KEY_SECTION_HEAD]);
        $coordinator = User::factory()->create([
            'role' => Role::KEY_REGIONAL_COORDINATOR,
            'region_id' => $region->id,
        ]);
        $application->assignTo($coordinator, $head);

        $this->actingAs($coordinator)
            ->from(route('applications.show', $application))
            ->post(route('applications.review', $application), [
                'decision' => SiteApplication::DECISION_APPROVE,
            ])
            ->assertRedirect(route('applications.show', $application))
            ->assertSessionHasErrors(['site_visit_on', 'site_visit_notes', 'officer_remarks']);

        $this->assertSame(SiteApplication::STATUS_ASSIGNED, $application->fresh()->status);
    }

    public function test_section_head_and_director_cannot_record_the_officer_decision(): void
    {
        [$region, $district, $subDistrict, $operator] = $this->geography();
        $application = $this->application($region, $district, $subDistrict, $operator);
        $head = User::factory()->create(['role' => Role::KEY_SECTION_HEAD]);
        $director = User::factory()->create(['role' => Role::KEY_DEPARTMENT_DIRECTOR]);
        $coordinator = User::factory()->create([
            'role' => Role::KEY_REGIONAL_COORDINATOR,
            'region_id' => $region->id,
        ]);
        $application->assignTo($coordinator, $head);

        $payload = array_merge($this->signatureFields(), [
            'decision' => SiteApplication::DECISION_APPROVE,
            'site_visit_on' => now()->toDateString(),
            'site_visit_notes' => 'Visited.',
            'officer_remarks' => 'OK.',
        ]);

        $this->actingAs($head)->post(route('applications.review', $application), $payload)->assertForbidden();
        $this->actingAs($director)->post(route('applications.review', $application), $payload)->assertForbidden();
        $this->assertSame(SiteApplication::STATUS_ASSIGNED, $application->fresh()->status);
    }

    public function test_coordinator_cannot_open_a_file_outside_their_region(): void
    {
        [$maroodi, $district, $subDistrict, $operator] = $this->geography();
        $sahil = Region::query()->create(['name_en' => 'Sahil', 'name_so' => 'Saaxil']);
        $application = $this->application($maroodi, $district, $subDistrict, $operator, [
            'site_name' => 'Maroodi only site',
        ]);
        $head = User::factory()->create(['role' => Role::KEY_SECTION_HEAD]);
        $sahilCoordinator = User::factory()->create([
            'role' => Role::KEY_REGIONAL_COORDINATOR,
            'region_id' => $sahil->id,
        ]);

        $application->assignTo($sahilCoordinator, $head);

        $this->actingAs($sahilCoordinator)
            ->get(route('applications.index'))
            ->assertOk()
            ->assertDontSee('Maroodi only site', false);

        $this->actingAs($sahilCoordinator)
            ->get(route('applications.show', $application))
            ->assertForbidden();

        $this->actingAs($sahilCoordinator)
            ->post(route('applications.review', $application), array_merge($this->signatureFields(), [
                'decision' => SiteApplication::DECISION_APPROVE,
                'site_visit_on' => now()->toDateString(),
                'site_visit_notes' => 'Visited.',
                'officer_remarks' => 'OK.',
            ]))
            ->assertForbidden();
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
