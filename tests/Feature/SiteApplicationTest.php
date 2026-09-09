<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Operator;
use App\Models\Region;
use App\Models\SiteApplication;
use App\Models\SubDistrict;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiteApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_open_the_application_form(): void
    {
        [$region, $district, $subDistrict, $operator] = $this->fixtures();

        $this->withSession(['locale' => 'so'])
            ->get(route('apply.create'))
            ->assertOk()
            ->assertSee('Site registration application', false)
            ->assertSee($operator->name, false)
            ->assertSee($region->name_en, false)
            ->assertSee($district->name, false)
            ->assertSee($subDistrict->name, false)
            ->assertSee('Request letter', false)
            ->assertDontSee('Location / GPS map', false)
            ->assertSee('Engineering site plan', false)
            ->assertSee('Description of planned radio apparatus', false)
            ->assertSee('ICNIRP compliance declaration', false)
            ->assertSee('Tower details', false)
            ->assertSee('Tower height (m)', false)
            ->assertSee('Nearest school', false)
            ->assertSee('Power source', false)
            ->assertSee('Save current location', false)
            ->assertDontSee('Latitude', false)
            ->assertDontSee('Longitude', false)
            ->assertDontSee('name="locale"', false)
            ->assertDontSee(route('geography.districts'), false)
            ->assertDontSee(route('towers.location-preview'), false);
    }

    public function test_guidelines_and_login_link_to_the_application_form(): void
    {
        $this->get(route('guidelines'))
            ->assertOk()
            ->assertSee(route('apply.create'), false)
            ->assertSee(__('app.apply.open', [], 'en'), false);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('apply.create'), false);
    }

    public function test_guests_cannot_use_staff_geography_endpoints(): void
    {
        $this->get(route('geography.districts'))->assertRedirect(route('login'));
        $this->get(route('geography.sub-districts'))->assertRedirect(route('login'));
        $this->get(route('geography.cities'))->assertRedirect(route('login'));
        $this->get(route('towers.location-preview'))->assertRedirect(route('login'));
    }

    public function test_submission_fails_without_the_five_documents(): void
    {
        [$region, $district, $subDistrict, $operator] = $this->fixtures();

        $this->from(route('apply.create'))
            ->post(route('apply.store'), $this->payload($region, $district, $subDistrict, $operator, files: false))
            ->assertRedirect(route('apply.create'))
            ->assertSessionHasErrors(['letter', 'layout', 'radio', 'icnirp']);

        $this->assertSame(0, SiteApplication::query()->count());
    }

    public function test_spreadsheet_attachments_are_rejected(): void
    {
        Storage::fake('local');
        [$region, $district, $subDistrict, $operator] = $this->fixtures();

        $payload = $this->payload($region, $district, $subDistrict, $operator);
        $payload['letter'] = UploadedFile::fake()->create(
            'letter.xlsx',
            80,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );

        $this->from(route('apply.create'))
            ->post(route('apply.store'), $payload)
            ->assertRedirect(route('apply.create'))
            ->assertSessionHasErrors(['letter']);

        $this->assertStringContainsString('PDF, Word, JPG or PNG', session('errors')->first('letter'));
        $this->assertSame(0, SiteApplication::query()->count());
    }

    public function test_word_attachments_are_accepted(): void
    {
        Storage::fake('local');
        [$region, $district, $subDistrict, $operator] = $this->fixtures();

        $payload = $this->payload($region, $district, $subDistrict, $operator);
        $payload['letter'] = UploadedFile::fake()->create(
            'letter.docx',
            80,
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        );

        $this->post(route('apply.store'), $payload)
            ->assertRedirect(route('apply.received'));

        $this->assertSame(1, SiteApplication::query()->count());
    }

    public function test_guest_can_submit_a_complete_application_and_see_a_tracking_number(): void
    {
        Storage::fake('local');
        [$region, $district, $subDistrict, $operator] = $this->fixtures();

        $this->post(route('apply.store'), $this->payload($region, $district, $subDistrict, $operator))
            ->assertRedirect(route('apply.received'));

        $application = SiteApplication::query()->firstOrFail();
        $year = now()->format('Y');

        $this->assertSame("MoCIT/APP/{$year}/0001", $application->reference_number);
        $this->assertSame(SiteApplication::STATUS_RECEIVED, $application->status);
        $this->assertSame('New Hargeisa Site', $application->site_name);
        $this->assertSame('monopole', $application->type);
        $this->assertSame('4g', $application->capacity);
        $this->assertSame('20 × 20 m', $application->land_area);
        $this->assertTrue($application->operator->is($operator));

        foreach (SiteApplication::DOCUMENT_FIELDS as $pathColumn) {
            Storage::disk('local')->assertExists($application->{$pathColumn});
        }

        $this->get(route('apply.received'))
            ->assertOk()
            ->assertSee("MoCIT/APP/{$year}/0001", false)
            ->assertSee('New Hargeisa Site', false)
            ->assertSee($operator->name, false)
            ->assertDontSee($application->letter_path, false);
    }

    public function test_second_application_increments_the_tracking_sequence(): void
    {
        Storage::fake('local');
        [$region, $district, $subDistrict, $operator] = $this->fixtures();
        $year = now()->format('Y');

        $this->post(route('apply.store'), $this->payload($region, $district, $subDistrict, $operator, siteName: 'Site A'))
            ->assertRedirect(route('apply.received'));
        $this->post(route('apply.store'), $this->payload($region, $district, $subDistrict, $operator, siteName: 'Site B'))
            ->assertRedirect(route('apply.received'));

        $this->assertSame("MoCIT/APP/{$year}/0001", SiteApplication::query()->where('site_name', 'Site A')->value('reference_number'));
        $this->assertSame("MoCIT/APP/{$year}/0002", SiteApplication::query()->where('site_name', 'Site B')->value('reference_number'));
    }

    public function test_district_from_another_region_is_rejected(): void
    {
        Storage::fake('local');
        [$region, $district, $subDistrict, $operator] = $this->fixtures();
        $otherRegion = Region::query()->create(['name_en' => 'Awdal', 'name_so' => 'Awdal']);
        $otherDistrict = District::query()->create(['region_id' => $otherRegion->id, 'name' => 'Borama']);

        $payload = $this->payload($region, $district, $subDistrict, $operator);
        $payload['district_id'] = $otherDistrict->id;

        $this->from(route('apply.create'))
            ->post(route('apply.store'), $payload)
            ->assertRedirect(route('apply.create'))
            ->assertSessionHasErrors(['district_id']);

        $this->assertSame(0, SiteApplication::query()->count());
    }

    public function test_received_page_redirects_when_there_is_no_submission_in_session(): void
    {
        $this->get(route('apply.received'))->assertRedirect(route('apply.create'));
    }

    public function test_public_endpoint_is_rate_limited(): void
    {
        Storage::fake('local');
        [$region, $district, $subDistrict, $operator] = $this->fixtures();

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.25']);

        for ($i = 1; $i <= 6; $i++) {
            $this->post(route('apply.store'), $this->payload($region, $district, $subDistrict, $operator, siteName: "Site {$i}"))
                ->assertRedirect(route('apply.received'));
        }

        $this->from(route('apply.create'))
            ->post(route('apply.store'), $this->payload($region, $district, $subDistrict, $operator, siteName: 'Site 7'))
            ->assertRedirect(route('apply.create'))
            ->assertSessionHasErrors(['form']);

        $this->assertSame(6, SiteApplication::query()->count());
    }

    /**
     * @return array{0: Region, 1: District, 2: SubDistrict, 3: Operator}
     */
    private function fixtures(): array
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
     * @return array<string, mixed>
     */
    private function payload(Region $region, District $district, SubDistrict $subDistrict, Operator $operator, bool $files = true, string $siteName = 'New Hargeisa Site'): array
    {
        $payload = [
            'contact_name' => 'Amina Hassan',
            'telephone' => '+252 63 4000000',
            'operator_id' => $operator->id,
            'license_class_no' => 'A-12/2026',
            'email' => 'amina@example.com',
            'address' => 'Sha\'ab, Hargeisa',
            'site_name' => $siteName,
            'region_id' => $region->id,
            'district_id' => $district->id,
            'sub_district_id' => $subDistrict->id,
            'latitude' => 9.562,
            'longitude' => 44.077,
            'type' => 'monopole',
            'height_m' => 45,
            'capacity' => '4g',
            'land_area_preset' => '20x20',
            'fence_distance_preset' => '6',
        ];

        if ($files) {
            foreach (array_keys(SiteApplication::DOCUMENT_FIELDS) as $field) {
                $payload[$field] = UploadedFile::fake()->create($field.'.pdf', 80, 'application/pdf');
            }
        }

        return $payload;
    }
}
