<?php

namespace Database\Seeders;

use App\Models\FrequencyAllocation;
use App\Models\FrequencyAllocationLetter;
use App\Models\FrequencyRenewalReceipt;
use App\Models\Operator;
use App\Models\Region;
use App\Models\User;
use App\Support\FrequencyAllocationLetterNumberGenerator;
use App\Support\FrequencyRenewalReceiptNumberGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class FrequencyAllocationSeeder extends Seeder
{
    public function run(): void
    {
        FrequencyRenewalReceipt::query()->delete();
        FrequencyAllocationLetter::query()->delete();
        FrequencyAllocation::query()->update(['renewed_from_id' => null]);
        FrequencyAllocation::query()->delete();

        $admin = User::query()->where('role', 'admin')->first();
        $maroodi = Region::query()->where('name_en', 'Maroodi Jeex')->first();
        $sahil = Region::query()->where('name_en', 'Sahil')->first();

        if (! $admin) {
            return;
        }

        $telesom = Operator::query()->where('name', 'Telesom')->first();
        if ($telesom) {
            $this->seedRenewalChain(
                operator: $telesom,
                admin: $admin,
                band: '900 MHz',
                range: '880–915 MHz / 925–960 MHz',
                regionId: null,
                startYear: 2022,
                endYear: (int) now()->format('Y'),
                currentExpiresAt: now()->addMonths(8),
            );
        }

        $somtel = Operator::query()->where('name', 'Somtel')->first();
        if ($somtel) {
            $this->seedRenewalChain(
                operator: $somtel,
                admin: $admin,
                band: '2100 MHz',
                range: '1920–1980 MHz / 2110–2170 MHz',
                regionId: null,
                startYear: 2024,
                endYear: (int) now()->format('Y'),
                currentExpiresAt: now()->addMonths(4),
            );
        }

        $samples = [
            ['operator' => 'Telesom', 'band' => '1800 MHz', 'range' => '1710–1785 MHz / 1805–1880 MHz', 'region' => $maroodi?->id, 'expires' => now()->addDays(18)],
            ['operator' => 'Somcable', 'band' => '900 MHz', 'range' => '880–915 MHz / 925–960 MHz', 'region' => $sahil?->id, 'expires' => now()->subDays(12)],
            ['operator' => 'Somtel', 'band' => '1800 MHz', 'range' => '1710–1785 MHz / 1805–1880 MHz', 'region' => null, 'expires' => now()->subDays(40)],
            ['operator' => 'Truecable', 'band' => '700 MHz', 'range' => '703–748 MHz / 758–803 MHz', 'region' => $maroodi?->id, 'expires' => now()->subDays(75)],
        ];

        foreach ($samples as $index => $sample) {
            $operator = Operator::query()->where('name', $sample['operator'])->first();
            if (! $operator) {
                continue;
            }

            $issuedAt = $sample['expires']->copy()->subYear();
            $allocation = FrequencyAllocation::query()->create([
                'operator_id' => $operator->id,
                'region_id' => $sample['region'],
                'band_label' => $sample['band'],
                'frequency_range' => $sample['range'],
                'channel_details' => 'ARFCN blocks assigned per national spectrum plan.',
                'issued_at' => $issuedAt,
                'expires_at' => $sample['expires'],
                'notes' => 'Demo allocation for ministry briefing.',
            ]);

            if ($index === 0) {
                $this->createLetter($allocation, $admin, $issuedAt);
            }
        }

        $astaan = Operator::query()->where('name', 'Astaan')->first();
        if ($astaan) {
            $this->seedRenewalChain(
                operator: $astaan,
                admin: $admin,
                band: '600 MHz',
                range: '703–748 MHz',
                regionId: null,
                startYear: 2023,
                endYear: (int) now()->format('Y'),
                currentExpiresAt: now()->addMonths(11),
            );
        }
    }

    private function seedRenewalChain(
        Operator $operator,
        User $admin,
        string $band,
        string $range,
        ?int $regionId,
        int $startYear,
        int $endYear,
        Carbon $currentExpiresAt,
    ): FrequencyAllocation {
        $previous = null;
        $current = null;

        for ($year = $startYear; $year <= $endYear; $year++) {
            $isLatest = $year === $endYear;
            $issuedAt = Carbon::create($year, 3, 15);
            $expiresAt = $isLatest
                ? $currentExpiresAt->copy()
                : Carbon::create($year + 1, 3, 14);

            if ($isLatest) {
                $issuedAt = $expiresAt->copy()->subYear();
            }

            $current = FrequencyAllocation::query()->create([
                'operator_id' => $operator->id,
                'region_id' => $regionId,
                'band_label' => $band,
                'frequency_range' => $range,
                'channel_details' => 'Multi-year demo history for official documents.',
                'issued_at' => $issuedAt->toDateString(),
                'expires_at' => $expiresAt->toDateString(),
                'renewed_from_id' => $previous?->id,
                'notes' => $isLatest
                    ? 'Current allocation with full document history.'
                    : 'Superseded allocation kept for document history.',
            ]);

            if ($previous === null) {
                $this->createLetter($current, $admin, $issuedAt);
            } else {
                $this->createRenewalDocuments($current, $admin, $issuedAt);
            }

            $previous = $current;
        }

        return $current;
    }

    private function createLetter(FrequencyAllocation $allocation, User $admin, Carbon $issuedAt): void
    {
        FrequencyAllocationLetter::query()->create([
            'frequency_allocation_id' => $allocation->id,
            'operator_id' => $allocation->operator_id,
            'reference_number' => FrequencyAllocationLetterNumberGenerator::generate($issuedAt->year),
            'issued_at' => $issuedAt->toDateString(),
            'issued_by' => $admin->id,
            'template_version' => FrequencyAllocationLetter::TEMPLATE_VERSION,
        ]);
    }

    private function createRenewalDocuments(FrequencyAllocation $allocation, User $admin, Carbon $issuedAt): void
    {
        $letter = FrequencyAllocationLetter::query()->create([
            'frequency_allocation_id' => $allocation->id,
            'operator_id' => $allocation->operator_id,
            'reference_number' => FrequencyAllocationLetterNumberGenerator::generate($issuedAt->year),
            'issued_at' => $issuedAt->toDateString(),
            'issued_by' => $admin->id,
            'template_version' => FrequencyAllocationLetter::TEMPLATE_VERSION,
        ]);

        FrequencyRenewalReceipt::query()->create([
            'frequency_allocation_id' => $allocation->id,
            'frequency_allocation_letter_id' => $letter->id,
            'operator_id' => $allocation->operator_id,
            'reference_number' => FrequencyRenewalReceiptNumberGenerator::generate($issuedAt->year),
            'issued_at' => $issuedAt->toDateString(),
            'issued_by' => $admin->id,
            'template_version' => FrequencyRenewalReceipt::TEMPLATE_VERSION,
        ]);
    }
}
