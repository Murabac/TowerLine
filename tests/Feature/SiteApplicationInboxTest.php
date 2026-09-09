<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Operator;
use App\Models\Region;
use App\Models\Role;
use App\Models\SiteApplication;
use App\Models\SubDistrict;
use App\Models\User;
use App\Support\SiteApplicationNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiteApplicationInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_section_head_sees_received_applications_and_ops_and_inspectors_cannot(): void
    {
        [$region, $district, $subDistrict, $operator] = $this->geography();
        $application = $this->application($region, $district, $subDistrict, $operator, [
            'site_name' => 'Xero Awr Site',
        ]);

        $head = User::factory()->create(['role' => Role::KEY_SECTION_HEAD]);
        $ops = User::factory()->create(['role' => Role::KEY_OPERATIONS_MANAGER]);
        $inspector = User::factory()->create([
            'role' => Role::KEY_INSPECTOR,
            'region_id' => $region->id,
        ]);

        $this->actingAs($head)
            ->get(route('applications.index'))
            ->assertOk()
            ->assertSee('Xero Awr Site', false)
            ->assertSee($application->reference_number, false);

        $this->actingAs($ops)->get(route('applications.index'))->assertForbidden();
        $this->actingAs($inspector)->get(route('applications.index'))->assertForbidden();
        $this->actingAs($ops)->get(route('applications.show', $application))->assertForbidden();
    }

    public function test_section_head_assigns_a_maroodi_file_to_that_regions_coordinator(): void
    {
        [$maroodi, $district, $subDistrict, $operator] = $this->geography();
        $sahil = Region::query()->create(['name_en' => 'Sahil', 'name_so' => 'Saaxil']);
        $application = $this->application($maroodi, $district, $subDistrict, $operator, [
            'site_name' => 'Maroodi new site',
        ]);

        $head = User::factory()->create(['role' => Role::KEY_SECTION_HEAD]);
        $maroodiCoordinator = User::factory()->create([
            'name' => 'Coordinator Maroodi Jeex',
            'role' => Role::KEY_REGIONAL_COORDINATOR,
            'region_id' => $maroodi->id,
        ]);
        $sahilCoordinator = User::factory()->create([
            'name' => 'Coordinator Sahil',
            'role' => Role::KEY_REGIONAL_COORDINATOR,
            'region_id' => $sahil->id,
        ]);
        $officer = User::factory()->create([
            'name' => 'HQ Assigned Officer',
            'role' => Role::KEY_ASSIGNED_OFFICER,
        ]);

        $this->actingAs($head)
            ->get(route('applications.show', $application))
            ->assertOk()
            ->assertSee('Coordinator Maroodi Jeex', false)
            ->assertSee('HQ Assigned Officer', false)
            ->assertDontSee('Coordinator Sahil', false);

        $this->actingAs($head)
            ->from(route('applications.show', $application))
            ->post(route('applications.assign', $application), [
                'assigned_to' => $sahilCoordinator->id,
            ])
            ->assertRedirect(route('applications.show', $application))
            ->assertSessionHasErrors('assigned_to');

        $this->assertSame(SiteApplication::STATUS_RECEIVED, $application->fresh()->status);

        $this->actingAs($head)
            ->post(route('applications.assign', $application), [
                'assigned_to' => $maroodiCoordinator->id,
            ])
            ->assertRedirect(route('applications.show', $application))
            ->assertSessionHas('status');

        $application->refresh();
        $this->assertSame(SiteApplication::STATUS_ASSIGNED, $application->status);
        $this->assertSame($maroodiCoordinator->id, $application->assigned_to);
        $this->assertSame($head->id, $application->assigned_by);
        $this->assertNotNull($application->assigned_at);

        $this->actingAs($head)
            ->post(route('applications.assign', $application), [
                'assigned_to' => $officer->id,
            ])
            ->assertRedirect(route('applications.show', $application));

        $this->assertSame($officer->id, $application->fresh()->assigned_to);
    }

    public function test_coordinator_sees_only_files_assigned_to_them(): void
    {
        [$region, $district, $subDistrict, $operator] = $this->geography();
        $assigned = $this->application($region, $district, $subDistrict, $operator, [
            'site_name' => 'Assigned site',
            'reference_number' => SiteApplicationNumberGenerator::generate(),
        ]);
        $other = $this->application($region, $district, $subDistrict, $operator, [
            'site_name' => 'Unassigned site',
            'reference_number' => SiteApplicationNumberGenerator::generate(),
        ]);

        $head = User::factory()->create(['role' => Role::KEY_SECTION_HEAD]);
        $coordinator = User::factory()->create([
            'role' => Role::KEY_REGIONAL_COORDINATOR,
            'region_id' => $region->id,
        ]);

        $assigned->assignTo($coordinator, $head);

        $this->actingAs($coordinator)
            ->get(route('applications.index'))
            ->assertOk()
            ->assertSee('Assigned site', false)
            ->assertDontSee('Unassigned site', false);

        $this->actingAs($coordinator)
            ->get(route('applications.show', $assigned))
            ->assertOk()
            ->assertDontSee(__('app.applications.assign_to'), false);

        $this->actingAs($coordinator)
            ->get(route('applications.show', $other))
            ->assertForbidden();
    }

    public function test_admin_can_assign_and_director_can_view_but_not_assign(): void
    {
        [$region, $district, $subDistrict, $operator] = $this->geography();
        $application = $this->application($region, $district, $subDistrict, $operator);
        $admin = User::factory()->create(['role' => Role::KEY_ADMIN]);
        $director = User::factory()->create(['role' => Role::KEY_DEPARTMENT_DIRECTOR]);
        $coordinator = User::factory()->create([
            'role' => Role::KEY_REGIONAL_COORDINATOR,
            'region_id' => $region->id,
        ]);

        $this->actingAs($director)
            ->get(route('applications.show', $application))
            ->assertOk()
            ->assertDontSee(__('app.applications.assign_to'), false);

        $this->actingAs($director)
            ->post(route('applications.assign', $application), [
                'assigned_to' => $coordinator->id,
            ])
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('applications.assign', $application), [
                'assigned_to' => $coordinator->id,
            ])
            ->assertRedirect(route('applications.show', $application));

        $this->assertSame($coordinator->id, $application->fresh()->assigned_to);
    }

    public function test_section_head_login_opens_the_application_inbox(): void
    {
        $head = User::factory()->create(['role' => Role::KEY_SECTION_HEAD]);

        $this->post('/login', [
            'email' => $head->email,
            'password' => 'password',
        ])->assertRedirect(route('applications.index', absolute: false));
    }

    public function test_document_download_is_authorized(): void
    {
        Storage::fake('local');

        [$region, $district, $subDistrict, $operator] = $this->geography();
        $application = $this->application($region, $district, $subDistrict, $operator, [
            'letter_path' => 'site-applications/inbox/letter.pdf',
        ]);
        Storage::disk('local')->put($application->letter_path, '%PDF-1.4 demo');

        $head = User::factory()->create(['role' => Role::KEY_SECTION_HEAD]);
        $coordinator = User::factory()->create([
            'role' => Role::KEY_REGIONAL_COORDINATOR,
            'region_id' => $region->id,
        ]);
        $ops = User::factory()->create(['role' => Role::KEY_OPERATIONS_MANAGER]);

        $this->actingAs($head)
            ->get(route('applications.documents.show', [$application, 'letter']))
            ->assertOk();

        $this->actingAs($coordinator)
            ->get(route('applications.documents.show', [$application, 'letter']))
            ->assertForbidden();

        $this->actingAs($ops)
            ->get(route('applications.documents.show', [$application, 'letter']))
            ->assertForbidden();

        $application->assignTo($coordinator, $head);

        $this->actingAs($coordinator)
            ->get(route('applications.documents.show', [$application, 'letter']))
            ->assertOk();
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
            'reference_number' => SiteApplicationNumberGenerator::generate(),
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
            'letter_path' => 'site-applications/inbox/letter.pdf',
            'layout_path' => 'site-applications/inbox/layout.pdf',
            'radio_path' => 'site-applications/inbox/radio.pdf',
            'icnirp_path' => 'site-applications/inbox/icnirp.pdf',
            'status' => SiteApplication::STATUS_RECEIVED,
        ], $overrides));
    }
}
