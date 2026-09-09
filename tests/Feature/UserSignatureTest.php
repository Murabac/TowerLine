<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Operator;
use App\Models\Region;
use App\Models\Role;
use App\Models\SiteApplication;
use App\Models\SubDistrict;
use App\Models\User;
use App\Support\UserSignature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserSignatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_save_and_clear_a_profile_signature(): void
    {
        $user = User::factory()->create(['role' => Role::KEY_DEPARTMENT_DIRECTOR]);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee(__('app.signatures.profile_title'), false);

        $this->actingAs($user)
            ->post(route('profile.signature.store'), $this->signatureFields())
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status', __('app.signatures.saved'));

        $user->refresh();
        $this->assertTrue($user->hasSavedSignature());

        $this->actingAs($user)
            ->get(route('profile.signature.show'))
            ->assertOk()
            ->assertHeader('content-type', 'image/png');

        $this->actingAs($user)
            ->delete(route('profile.signature.destroy'))
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status', __('app.signatures.cleared'));

        $this->assertFalse($user->fresh()->hasSavedSignature());
    }

    public function test_director_can_reuse_a_saved_signature_and_the_trail_keeps_a_snapshot(): void
    {
        [$application, $coordinator, $director, $dg] = $this->fileWaitingForDirector();

        $this->actingAs($director)
            ->post(route('profile.signature.store'), $this->signatureFields())
            ->assertRedirect(route('profile.edit'));

        $savedPath = $director->fresh()->signature_path;
        $savedPng = Storage::disk('local')->get($savedPath);

        $this->actingAs($director)
            ->from(route('applications.show', $application))
            ->post(route('applications.concur', $application), [
                'decision' => SiteApplication::DECISION_YES,
                'director_remarks' => 'Site meets ministry spacing.',
                'director_name' => 'Amina Director',
                'use_saved_signature' => '1',
                'save_signature' => '0',
                'signature_data' => '',
            ])
            ->assertRedirect(route('applications.show', $application))
            ->assertSessionHasNoErrors();

        $application->refresh();
        $this->assertNotNull($application->director_signature_path);
        $this->assertNotSame($savedPath, $application->director_signature_path);
        $this->assertSame($savedPng, Storage::disk('local')->get($application->director_signature_path));

        $this->actingAs($director)
            ->post(route('profile.signature.store'), [
                'use_saved_signature' => '0',
                'save_signature' => '1',
                'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=',
            ])
            ->assertRedirect(route('profile.edit'));

        $this->assertSame($savedPng, Storage::disk('local')->get($application->director_signature_path));
        $this->assertNotSame($savedPng, Storage::disk('local')->get($director->fresh()->signature_path));

        $this->actingAs($director)
            ->get(route('applications.show', $application))
            ->assertOk()
            ->assertSee(__('app.signatures.trail'), false)
            ->assertSee($director->name, false);

        $this->actingAs($director)
            ->get(route('applications.signatures.show', [$application, 'director']))
            ->assertOk()
            ->assertHeader('content-type', 'image/png');
    }

    public function test_grant_letter_and_tower_show_the_signed_approvals(): void
    {
        [$application, $coordinator, $director, $dg] = $this->fileWaitingForDirector();

        $this->actingAs($director)->post(route('applications.concur', $application), array_merge($this->signatureFields(), [
            'decision' => SiteApplication::DECISION_YES,
            'director_remarks' => 'Concur.',
            'director_name' => 'Amina Director',
        ]));

        $this->actingAs($dg)->post(route('applications.grant', $application), array_merge($this->signatureFields(), [
            'decision' => SiteApplication::DECISION_GRANT,
            'dg_remarks' => 'Permit granted.',
            'dg_name' => 'Hassan DG',
        ]))->assertRedirect(route('applications.show', $application));

        $application->refresh();
        $this->assertNotNull($application->director_signature_path);
        $this->assertNotNull($application->dg_signature_path);

        $this->actingAs($dg)
            ->get(route('applications.show', $application))
            ->assertOk()
            ->assertSee(__('app.signatures.trail'), false)
            ->assertSee($coordinator->name, false)
            ->assertSee('Amina Director', false)
            ->assertSee('Hassan DG', false);

        $this->actingAs($dg)
            ->get(route('applications.permit.print', ['application' => $application, 'copy' => 'hq']))
            ->assertOk()
            ->assertSee('data:image/png;base64,', false)
            ->assertSee('Amina Director', false)
            ->assertSee('Hassan DG', false);

        $this->actingAs($dg)
            ->get(route('towers.show', $application->tower))
            ->assertOk()
            ->assertSee(__('app.signatures.trail'), false)
            ->assertSee('Amina Director', false);

        $this->actingAs($dg)
            ->get(route('towers.approval-letter.print', $application->tower))
            ->assertOk()
            ->assertSee('data:image/png;base64,', false);
    }

    public function test_a_decision_requires_a_signature(): void
    {
        [$application, $coordinator, $director] = $this->fileWaitingForDirector();

        $this->actingAs($director)
            ->from(route('applications.show', $application))
            ->post(route('applications.concur', $application), [
                'decision' => SiteApplication::DECISION_YES,
                'director_remarks' => 'Concur.',
                'director_name' => 'Amina Director',
            ])
            ->assertRedirect(route('applications.show', $application))
            ->assertSessionHasErrors('signature_data');

        $this->assertSame(SiteApplication::STATUS_DIRECTOR_REVIEW, $application->fresh()->status);
        $this->assertNull($application->fresh()->director_signature_path);
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
            'name' => 'Maroodi Coordinator',
        ]);
        $director = User::factory()->create(['role' => Role::KEY_DEPARTMENT_DIRECTOR, 'name' => 'Amina Director']);
        $dg = User::factory()->create(['role' => Role::KEY_DIRECTOR_GENERAL, 'name' => 'Hassan DG']);

        $application->assignTo($coordinator, $head);
        $png = UserSignature::pngFromDataUrl($this->signatureFields()['signature_data']);
        $application->recordOfficerReview(
            $coordinator,
            SiteApplication::DECISION_APPROVE,
            now()->toDateString(),
            'Fence and GPS match the plan.',
            'Recommend concurrence.',
            UserSignature::snapshot('applications/'.$application->id, 'officer', (string) $png),
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
