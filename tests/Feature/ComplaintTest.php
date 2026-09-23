<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\District;
use App\Models\Operator;
use App\Models\Region;
use App\Models\Role;
use App\Models\SubDistrict;
use App\Models\Tower;
use App\Models\User;
use App\Notifications\ComplaintAssigned;
use App\Notifications\ComplaintAwaitingClosure;
use App\Support\ComplaintNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ComplaintTest extends TestCase
{
    use RefreshDatabase;

    public function test_complaints_officer_can_log_a_complaint_with_a_readable_reference(): void
    {
        [$region, $tower] = $this->fixtures();
        $officer = User::factory()->create(['role' => Role::KEY_COMPLAINTS_OFFICER]);

        $this->actingAs($officer)
            ->post(route('complaints.store'), [
                'complaint_type' => Complaint::TYPE_SIGNAL_ISSUE,
                'description' => 'Dropped calls after 8pm.',
                'region_id' => $region->id,
                'tower_id' => $tower->id,
                'priority' => Complaint::PRIORITY_HIGH,
                'submitter_name' => 'Amina',
                'submitter_phone' => '+252634111222',
            ])
            ->assertRedirect();

        $complaint = Complaint::query()->firstOrFail();
        $year = now()->format('Y');
        $this->assertSame('CMP-'.$year.'-00001', $complaint->reference_number);
        $this->assertSame(Complaint::STATUS_SUBMITTED, $complaint->status);
        $this->assertSame($tower->id, $complaint->tower_id);
        $this->assertTrue($complaint->updates()->exists());
    }

    public function test_intake_can_attach_a_tower_without_sending_region(): void
    {
        [, $tower] = $this->fixtures();
        $officer = User::factory()->create(['role' => Role::KEY_COMPLAINTS_OFFICER]);

        $this->actingAs($officer)
            ->post(route('complaints.store'), [
                'complaint_type' => Complaint::TYPE_SIGNAL_ISSUE,
                'description' => 'Interference at the mast.',
                'tower_id' => $tower->id,
                'latitude' => $tower->latitude,
                'longitude' => $tower->longitude,
            ])
            ->assertRedirect();

        $complaint = Complaint::query()->firstOrFail();
        $this->assertSame($tower->id, $complaint->tower_id);
        $this->assertSame($tower->region_id, $complaint->region_id);
    }

    public function test_guest_cannot_open_complaint_routes(): void
    {
        $this->get(route('complaints.index'))->assertRedirect(route('login'));
        $this->get(route('complaints.create'))->assertRedirect(route('login'));
    }

    public function test_operator_sees_only_own_tower_complaints_and_cannot_close(): void
    {
        [$region, $telesomTower, $somtelTower, $inspector, $officer] = $this->fixturesWithSecondOperator();
        $telesom = $telesomTower->operator;
        $viewer = User::factory()->create([
            'role' => Role::KEY_OPERATOR_VIEWER,
            'operator_id' => $telesom->id,
        ]);

        $own = $this->complaint($officer, $telesomTower, Complaint::STATUS_ASSIGNED, $viewer->id);
        $other = $this->complaint($officer, $somtelTower, Complaint::STATUS_ASSIGNED, $inspector->id);

        $this->actingAs($viewer)
            ->get(route('complaints.index'))
            ->assertOk()
            ->assertSee($own->reference_number, false)
            ->assertDontSee($other->reference_number, false);

        $this->actingAs($viewer)
            ->put(route('complaints.update', $own), [
                'status' => Complaint::STATUS_CLOSED,
                'priority' => Complaint::PRIORITY_MEDIUM,
            ])
            ->assertForbidden();

        Notification::fake();

        $this->actingAs($viewer)
            ->post(route('complaints.respond', $own), [
                'note' => 'Crew replaced the feeder.',
                'mark_resolved' => '1',
            ])
            ->assertRedirect();

        $this->assertSame(Complaint::STATUS_RESOLVED, $own->fresh()->status);
        Notification::assertSentTo($officer, ComplaintAwaitingClosure::class);
    }

    public function test_inspector_is_notified_when_assigned_and_sees_region_files(): void
    {
        [$region, $tower] = $this->fixtures();
        $officer = User::factory()->create(['role' => Role::KEY_COMPLAINTS_OFFICER]);
        $inspector = User::factory()->create(['role' => 'inspector']);
        $inspector->syncInspectorRegions([$region->id]);
        $complaint = $this->complaint($officer, $tower, Complaint::STATUS_SUBMITTED);

        Notification::fake();

        $this->actingAs($officer)
            ->post(route('complaints.assign', $complaint), [
                'assigned_to' => $inspector->id,
            ])
            ->assertRedirect();

        $this->assertSame($inspector->id, $complaint->fresh()->assigned_to);
        $this->assertSame(Complaint::STATUS_ASSIGNED, $complaint->fresh()->status);
        Notification::assertSentTo($inspector, ComplaintAssigned::class);

        $this->actingAs($inspector)
            ->get(route('complaints.show', $complaint))
            ->assertOk()
            ->assertSee($complaint->reference_number, false);
    }

    public function test_map_complaints_json_is_scoped_and_uses_diamond_payload(): void
    {
        [$region, $tower] = $this->fixtures();
        $officer = User::factory()->create(['role' => Role::KEY_COMPLAINTS_OFFICER]);
        $complaint = $this->complaint($officer, $tower, Complaint::STATUS_SUBMITTED);

        $payload = $this->actingAs($officer)
            ->getJson(route('map.complaints'))
            ->assertOk()
            ->json('complaints.0');

        $this->assertSame($complaint->reference_number, $payload['reference_number']);
        $this->assertEqualsWithDelta((float) $tower->latitude, $payload['lat'], 0.0001);
    }

    public function test_intake_accepts_a_map_pin_without_a_tower(): void
    {
        [$region] = $this->fixtures();
        $officer = User::factory()->create(['role' => Role::KEY_COMPLAINTS_OFFICER]);
        Storage::fake('local');

        $this->actingAs($officer)
            ->post(route('complaints.store'), [
                'complaint_type' => Complaint::TYPE_OTHER,
                'description' => 'Unknown mast near the market.',
                'region_id' => $region->id,
                'latitude' => 9.561,
                'longitude' => 44.065,
                'photo' => UploadedFile::fake()->image('site.jpg'),
            ])
            ->assertRedirect();

        $complaint = Complaint::query()->firstOrFail();
        $this->assertNull($complaint->tower_id);
        $this->assertNotNull($complaint->photo_path);
        $this->assertTrue(Storage::disk('local')->exists($complaint->photo_path));
    }

    public function test_intake_map_loads_all_visible_towers(): void
    {
        [$region, $near] = $this->fixtures();
        $district = District::query()->where('region_id', $region->id)->firstOrFail();
        $operator = Operator::query()->firstOrFail();
        $far = Tower::query()->create($this->towerAttributes(
            $region,
            $district,
            $operator,
            'FAR-TEL-001',
            9.62,
            44.15,
        ));
        $officer = User::factory()->create(['role' => Role::KEY_COMPLAINTS_OFFICER]);

        $this->actingAs($officer)
            ->get(route('complaints.create'))
            ->assertOk()
            ->assertSee(__('app.complaints.map_pin_hint'), false)
            ->assertDontSee('nearby-towers', false);

        $this->actingAs($officer)
            ->getJson(route('map.towers'))
            ->assertOk()
            ->assertJsonCount(2, 'towers')
            ->assertJsonFragment(['id' => $near->id, 'name' => $near->name, 'region_id' => $region->id])
            ->assertJsonFragment(['id' => $far->id, 'name' => $far->name]);
    }

    public function test_reference_numbers_increment(): void
    {
        $this->assertSame('CMP-'.now()->format('Y').'-00001', ComplaintNumberGenerator::generate());
        $this->fixtures();
        $officer = User::factory()->create(['role' => Role::KEY_COMPLAINTS_OFFICER]);
        Complaint::query()->create([
            'reference_number' => ComplaintNumberGenerator::generate(),
            'region_id' => Region::query()->first()->id,
            'complaint_type' => Complaint::TYPE_OTHER,
            'description' => 'First',
            'status' => Complaint::STATUS_SUBMITTED,
            'priority' => Complaint::PRIORITY_LOW,
            'created_by' => $officer->id,
            'latitude' => 9.5,
            'longitude' => 44.0,
        ]);

        $this->assertSame('CMP-'.now()->format('Y').'-00002', ComplaintNumberGenerator::generate());
    }

    /**
     * @return array{0: Region, 1: Tower}
     */
    private function fixtures(): array
    {
        $region = Region::query()->create(['name_en' => 'Maroodi Jeex', 'name_so' => 'Maroodi Jeex']);
        $district = District::query()->create(['region_id' => $region->id, 'name' => 'Hargeisa', 'grade' => 'A']);
        SubDistrict::query()->create(['district_id' => $district->id, 'name' => 'Central']);
        $operator = Operator::query()->create(['name' => 'Telesom', 'category' => 'telecom', 'color' => '#0F766E']);
        $tower = Tower::query()->create($this->towerAttributes($region, $district, $operator, 'HRG-TEL-001'));

        return [$region, $tower];
    }

    /**
     * @return array{0: Region, 1: Tower, 2: Tower, 3: User, 4: User}
     */
    private function fixturesWithSecondOperator(): array
    {
        [$region, $telesomTower] = $this->fixtures();
        $somtel = Operator::query()->create(['name' => 'Somtel', 'category' => 'telecom', 'color' => '#1D4ED8']);
        $district = District::query()->where('region_id', $region->id)->firstOrFail();
        $somtelTower = Tower::query()->create($this->towerAttributes($region, $district, $somtel, 'HRG-SOM-001'));
        $inspector = User::factory()->create(['role' => 'inspector']);
        $inspector->syncInspectorRegions([$region->id]);
        $officer = User::factory()->create(['role' => Role::KEY_COMPLAINTS_OFFICER]);

        return [$region, $telesomTower, $somtelTower, $inspector, $officer];
    }

    private function complaint(User $officer, Tower $tower, string $status, ?int $assignedTo = null): Complaint
    {
        return Complaint::query()->create([
            'reference_number' => ComplaintNumberGenerator::generate(),
            'tower_id' => $tower->id,
            'latitude' => $tower->latitude,
            'longitude' => $tower->longitude,
            'region_id' => $tower->region_id,
            'complaint_type' => Complaint::TYPE_SIGNAL_ISSUE,
            'description' => 'Field report.',
            'status' => $status,
            'priority' => Complaint::PRIORITY_MEDIUM,
            'created_by' => $officer->id,
            'assigned_to' => $assignedTo,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function towerAttributes(
        Region $region,
        District $district,
        Operator $operator,
        string $name,
        float $latitude = 9.56,
        float $longitude = 44.07,
    ): array {
        return [
            'name' => $name,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'region_id' => $region->id,
            'district_id' => $district->id,
            'sub_district_id' => SubDistrict::query()->where('district_id', $district->id)->value('id'),
            'operator_id' => $operator->id,
            'type' => 'guyed',
            'height_m' => 40,
            'capacity' => '4g',
            'signal_radius_m' => 5000,
            'status' => 'active',
            'health_status' => 'good',
        ];
    }
}
