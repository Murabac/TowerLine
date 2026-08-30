<?php

namespace App\Support;

use App\Models\FrequencyAllocation;
use App\Models\FrequencyAllocationLetter;
use App\Models\FrequencyRenewalReceipt;
use App\Models\User;

class FrequencyRenewalDocuments
{
    /**
     * @return array{letter: FrequencyAllocationLetter, receipt: FrequencyRenewalReceipt}
     */
    public static function issue(FrequencyAllocation $allocation, User $user, ?string $issuedAt = null): array
    {
        $issuedAt ??= now()->toDateString();

        $letter = FrequencyAllocationLetter::query()->create([
            'frequency_allocation_id' => $allocation->id,
            'operator_id' => $allocation->operator_id,
            'reference_number' => FrequencyAllocationLetterNumberGenerator::generate(),
            'issued_at' => $issuedAt,
            'issued_by' => $user->id,
            'template_version' => FrequencyAllocationLetter::TEMPLATE_VERSION,
        ]);

        $receipt = FrequencyRenewalReceipt::query()->create([
            'frequency_allocation_id' => $allocation->id,
            'frequency_allocation_letter_id' => $letter->id,
            'operator_id' => $allocation->operator_id,
            'reference_number' => FrequencyRenewalReceiptNumberGenerator::generate(),
            'issued_at' => $issuedAt,
            'issued_by' => $user->id,
            'template_version' => FrequencyRenewalReceipt::TEMPLATE_VERSION,
        ]);

        return compact('letter', 'receipt');
    }
}
