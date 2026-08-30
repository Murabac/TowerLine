<?php

namespace Tests\Feature;

use App\Models\BuildApprovalLetter;
use App\Models\MinistrySetting;
use App\Models\Operator;
use App\Models\Region;
use App\Models\Tower;
use App\Models\User;
use Database\Seeders\GeographySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuildApprovalLetterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        MinistrySetting::query()->create([
            'approval_director_name' => 'Eng. Ahmed Hassan',
            'approval_director_title_so' => 'Agaasimaha Waaxda Isgaadhsiinta',
            'approval_director_title_en' => 'Director of Communication Department',
        ]);
    }

    public function test_admin_can_generate_approval_letter_for_tower(): void
    {
        [$region, $district, $subDistrict, $tower, $admin] = $this->fixtures();

        $this->actingAs($admin)
            ->post(route('towers.approval-letter.store', $tower), [
                'status' => 'approved',
            ])
            ->assertRedirect(route('towers.approval-letter.show', $tower));

        $letter = $tower->fresh()->currentApprovalLetter;

        $this->assertNotNull($letter);
        $this->assertSame('approved', $letter->status);
        $this->assertStringStartsWith('MoCIT/ISG/', $letter->reference_number);
        $this->assertSame($admin->id, $letter->issued_by);
    }

    public function test_approval_letter_page_shows_tower_registration_data(): void
    {
        [$region, $district, $subDistrict, $tower, $admin] = $this->fixtures();

        BuildApprovalLetter::query()->create([
            'tower_id' => $tower->id,
            'operator_id' => $tower->operator_id,
            'reference_number' => 'MoCIT/ISG/2026/0001',
            'status' => BuildApprovalLetter::STATUS_APPROVED,
            'issued_at' => now()->toDateString(),
            'issued_by' => $admin->id,
            'template_version' => BuildApprovalLetter::TEMPLATE_VERSION,
        ]);

        $this->actingAs($admin)
            ->get(route('towers.approval-letter.show', $tower))
            ->assertOk()
            ->assertSee('MoCIT/ISG/2026/0001', false)
            ->assertSee('Telesom', false)
            ->assertSee('Hargeisa', false)
            ->assertSee('TEL-HAR-001', false)
            ->assertSee('20 × 20 m', false)
            ->assertSee('WAA LA OGGOLAADAY', false)
            ->assertSee('Faahfaahinta Taawarka', false)
            ->assertSee('Eng. Ahmed Hassan', false)
            ->assertSee('Director of Communication Department', false)
            ->assertSee('Signature &amp; stamp', false);
    }

    public function test_print_view_is_available_for_authorized_users(): void
    {
        [$region, $district, $subDistrict, $tower, $admin] = $this->fixtures();

        BuildApprovalLetter::query()->create([
            'tower_id' => $tower->id,
            'operator_id' => $tower->operator_id,
            'reference_number' => 'MoCIT/ISG/2026/0099',
            'status' => BuildApprovalLetter::STATUS_APPROVED,
            'issued_at' => now()->toDateString(),
            'issued_by' => $admin->id,
            'template_version' => BuildApprovalLetter::TEMPLATE_VERSION,
        ]);

        $this->actingAs($admin)
            ->get(route('towers.approval-letter.print', $tower))
            ->assertOk()
            ->assertSee('MoCIT/ISG/2026/0099', false);
    }

    public function test_reference_numbers_increment_per_year(): void
    {
        [$region, $district, $subDistrict, $tower, $admin] = $this->fixtures();

        $first = \App\Support\BuildApprovalLetterNumberGenerator::generate(2026);
        BuildApprovalLetter::query()->create([
            'tower_id' => $tower->id,
            'operator_id' => $tower->operator_id,
            'reference_number' => $first,
            'status' => BuildApprovalLetter::STATUS_APPROVED,
            'issued_at' => now()->toDateString(),
            'issued_by' => $admin->id,
            'template_version' => BuildApprovalLetter::TEMPLATE_VERSION,
        ]);

        $secondTower = Tower::query()->create([
            'name' => 'TEL-HAR-002',
            'latitude' => 9.563,
            'longitude' => 44.078,
            'region_id' => $region->id,
            'district_id' => $district->id,
            'sub_district_id' => $subDistrict->id,
            'city' => 'Hargeisa',
            'operator_id' => $tower->operator_id,
            'type' => 'monopole',
            'height_m' => 40,
            'capacity' => '4g',
            'signal_radius_m' => 8000,
            'status' => 'active',
            'health_status' => 'good',
        ]);

        $second = \App\Support\BuildApprovalLetterNumberGenerator::generate(2026);

        $this->assertSame('MoCIT/ISG/2026/0001', $first);
        $this->assertSame('MoCIT/ISG/2026/0002', $second);
        $this->assertNotSame($first, $second);
        $this->assertSame($secondTower->id, $secondTower->id);
    }

    public function test_operator_viewer_can_view_but_not_generate_approval_letter(): void
    {
        [$region, $district, $subDistrict, $tower, $admin] = $this->fixtures();
        $viewer = User::factory()->create([
            'role' => 'operator_viewer',
            'operator_id' => $tower->operator_id,
        ]);

        BuildApprovalLetter::query()->create([
            'tower_id' => $tower->id,
            'operator_id' => $tower->operator_id,
            'reference_number' => 'MoCIT/ISG/2026/0100',
            'status' => BuildApprovalLetter::STATUS_APPROVED,
            'issued_at' => now()->toDateString(),
            'issued_by' => $admin->id,
            'template_version' => BuildApprovalLetter::TEMPLATE_VERSION,
        ]);

        $this->actingAs($viewer)
            ->get(route('towers.approval-letter.show', $tower))
            ->assertOk();

        $this->actingAs($viewer)
            ->post(route('towers.approval-letter.store', $tower))
            ->assertForbidden();
    }

    /**
     * @return array{0: Region, 1: \App\Models\District, 2: \App\Models\SubDistrict, 3: Tower, 4: User}
     */
    private function fixtures(): array
    {
        Region::query()->firstOrCreate(
            ['name_en' => 'Maroodi Jeex'],
            ['name_en' => 'Maroodi Jeex', 'name_so' => 'Maroodi Jeex'],
        );

        $this->seed(GeographySeeder::class);

        $region = Region::query()->where('name_en', 'Maroodi Jeex')->firstOrFail();
        $district = \App\Models\District::query()->where('region_id', $region->id)->firstOrFail();
        $subDistrict = \App\Models\SubDistrict::query()->where('district_id', $district->id)->firstOrFail();
        $operator = Operator::query()->create([
            'name' => 'Telesom',
            'category' => 'telecom',
            'color' => '#0F766E',
        ]);

        $tower = Tower::query()->create([
            'name' => 'TEL-HAR-001',
            'latitude' => 9.562,
            'longitude' => 44.077,
            'region_id' => $region->id,
            'district_id' => $district->id,
            'sub_district_id' => $subDistrict->id,
            'city' => 'Hargeisa',
            'land_area' => '20 × 20 m',
            'operator_id' => $operator->id,
            'type' => 'monopole',
            'height_m' => 45,
            'capacity' => '4g',
            'signal_radius_m' => 8000,
            'status' => 'active',
            'health_status' => 'good',
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        return [$region, $district, $subDistrict, $tower, $admin];
    }
}
