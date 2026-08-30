<?php

namespace Tests\Feature;

use App\Models\FrequencyAllocation;
use App\Models\MinistrySetting;
use App\Models\Operator;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrequencyAllocationTest extends TestCase
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

    public function test_admin_can_create_frequency_allocation(): void
    {
        [$operator] = $this->fixtures();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('frequencies.store'), [
                'operator_id' => $operator->id,
                'band_label' => '900 MHz',
                'frequency_range' => '880–915 MHz',
                'issued_at' => now()->toDateString(),
                'expires_at' => now()->addYear()->toDateString(),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('frequency_allocations', [
            'operator_id' => $operator->id,
            'band_label' => '900 MHz',
        ]);
    }

    public function test_admin_can_renew_frequency_allocation(): void
    {
        [$operator] = $this->fixtures();
        $admin = User::factory()->create(['role' => 'admin']);

        $allocation = FrequencyAllocation::query()->create([
            'operator_id' => $operator->id,
            'band_label' => '1800 MHz',
            'frequency_range' => '1710–1785 MHz',
            'issued_at' => now()->subYear(),
            'expires_at' => now()->addDays(5),
        ]);

        $this->actingAs($admin)
            ->post(route('frequencies.renew', $allocation))
            ->assertRedirect();

        $renewed = FrequencyAllocation::query()->where('renewed_from_id', $allocation->id)->first();
        $this->assertNotNull($renewed);
        $this->assertTrue($renewed->expires_at->greaterThan(now()->addMonths(11)));
        $this->assertNotNull($renewed->currentLetter);
        $this->assertNotNull($renewed->currentReceipt);
        $this->assertStringStartsWith('MoCIT/FRQ/', $renewed->currentLetter->reference_number);
        $this->assertStringStartsWith('MoCIT/FRQ-RCP/', $renewed->currentReceipt->reference_number);

        $this->actingAs($admin)
            ->get(route('frequencies.dashboard'))
            ->assertOk()
            ->assertSee('2,000,000', false);
    }

    public function test_frequency_dashboard_and_registry_pages_load(): void
    {
        [$operator] = $this->fixtures();
        $admin = User::factory()->create(['role' => 'admin']);

        FrequencyAllocation::query()->create([
            'operator_id' => $operator->id,
            'band_label' => '900 MHz',
            'frequency_range' => '880–915 MHz',
            'issued_at' => now()->subMonths(11),
            'expires_at' => now()->addDays(10),
        ]);

        FrequencyAllocation::query()->create([
            'operator_id' => $operator->id,
            'band_label' => '2100 MHz',
            'frequency_range' => '1920–1980 MHz',
            'issued_at' => now()->subYears(2),
            'expires_at' => now()->subDays(5),
        ]);

        $this->actingAs($admin)
            ->get(route('frequencies.dashboard'))
            ->assertOk()
            ->assertSee(__('app.frequencies.dashboard_title'), false)
            ->assertSee(__('app.frequencies.revenue.section_title'), false)
            ->assertSee(__('app.frequencies.revenue.due'), false)
            ->assertSee('frequency-finance-pie', false)
            ->assertSee('frequency-finance-line', false);

        $this->actingAs($admin)
            ->get(route('frequencies.registry', ['state' => 'outstanding']))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('frequencies.registry', ['payment' => 'with_receipt']))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('frequencies.registry', ['state' => 'expiring_soon']))
            ->assertOk()
            ->assertSee('900 MHz', false);
    }

    public function test_renewal_receipt_can_be_printed(): void
    {
        [$operator] = $this->fixtures();
        $admin = User::factory()->create(['role' => 'admin']);

        $allocation = FrequencyAllocation::query()->create([
            'operator_id' => $operator->id,
            'band_label' => '1800 MHz',
            'frequency_range' => '1710–1785 MHz',
            'issued_at' => now()->subYear(),
            'expires_at' => now()->addDays(3),
        ]);

        $this->actingAs($admin)->post(route('frequencies.renew', $allocation));

        $renewed = FrequencyAllocation::query()->where('renewed_from_id', $allocation->id)->firstOrFail();

        $this->actingAs($admin)
            ->get(route('frequencies.receipt.print', $renewed))
            ->assertOk()
            ->assertSee($renewed->currentReceipt->reference_number, false)
            ->assertSee('Official Receipt', false)
            ->assertSee('2,000,000 SLSH', false);
    }

    public function test_registry_filters_by_receipt_month_from_line_chart(): void
    {
        [$operator] = $this->fixtures();
        $admin = User::factory()->create(['role' => 'admin']);

        $allocation = FrequencyAllocation::query()->create([
            'operator_id' => $operator->id,
            'band_label' => '1800 MHz',
            'frequency_range' => '1710–1785 MHz',
            'issued_at' => now()->subYear(),
            'expires_at' => now()->addDays(3),
        ]);

        $this->actingAs($admin)->post(route('frequencies.renew', $allocation));

        $renewed = FrequencyAllocation::query()->where('renewed_from_id', $allocation->id)->firstOrFail();
        $month = $renewed->currentReceipt->issued_at->format('Y-m');

        $this->actingAs($admin)
            ->get(route('frequencies.registry', ['receipt_month' => $month, 'payment' => 'with_receipt']))
            ->assertOk()
            ->assertSee($renewed->operator->name, false)
            ->assertSee($renewed->currentReceipt->reference_number, false)
            ->assertSee(__('app.frequencies.filter_active_receipt_month', ['month' => $renewed->currentReceipt->issued_at->format('M Y')]), false);
    }

    public function test_admin_can_generate_frequency_allocation_letter(): void
    {
        [$operator] = $this->fixtures();
        $admin = User::factory()->create(['role' => 'admin']);

        $allocation = FrequencyAllocation::query()->create([
            'operator_id' => $operator->id,
            'band_label' => '2100 MHz',
            'frequency_range' => '1920–1980 MHz',
            'issued_at' => now()->subMonth(),
            'expires_at' => now()->addMonths(11),
        ]);

        $this->actingAs($admin)
            ->post(route('frequencies.letter.store', $allocation))
            ->assertRedirect(route('frequencies.letter.show', $allocation));

        $letter = $allocation->fresh()->currentLetter;
        $this->assertNotNull($letter);
        $this->assertStringStartsWith('MoCIT/FRQ/', $letter->reference_number);

        $this->actingAs($admin)
            ->get(route('frequencies.letter.show', $allocation))
            ->assertOk()
            ->assertSee('MoCIT/FRQ/', false)
            ->assertSee('Frequency Allocation Approval Letter', false)
            ->assertSee('Eng. Ahmed Hassan', false);

        $this->actingAs($admin)
            ->get(route('frequencies.letter.print', $allocation))
            ->assertOk()
            ->assertSee($letter->reference_number, false);
    }

    public function test_admin_can_view_and_edit_frequency_allocation_pages(): void
    {
        [$operator] = $this->fixtures();
        $admin = User::factory()->create(['role' => 'admin']);

        $allocation = FrequencyAllocation::query()->create([
            'operator_id' => $operator->id,
            'band_label' => '900 MHz',
            'frequency_range' => '880–915 MHz',
            'issued_at' => now()->subMonth(),
            'expires_at' => now()->addMonths(11),
        ]);

        $this->actingAs($admin)
            ->get(route('frequencies.show', $allocation))
            ->assertOk()
            ->assertSee('900 MHz', false);

        $this->actingAs($admin)
            ->get(route('frequencies.edit', $allocation))
            ->assertOk()
            ->assertSee(__('app.frequencies.edit'), false);
    }

    /**
     * @return array{0: Operator}
     */
    private function fixtures(): array
    {
        Region::query()->firstOrCreate(
            ['name_en' => 'Maroodi Jeex'],
            ['name_en' => 'Maroodi Jeex', 'name_so' => 'Maroodi Jeex'],
        );

        $operator = Operator::query()->create([
            'name' => 'Telesom',
            'category' => 'telecom',
            'color' => '#0F766E',
        ]);

        return [$operator];
    }
}
