<?php

namespace App\Policies;

use App\Models\FrequencyRenewalReceipt;
use App\Models\User;

class FrequencyRenewalReceiptPolicy
{
    public function view(User $user, FrequencyRenewalReceipt $receipt): bool
    {
        return $user->can('view', $receipt->allocation);
    }
}
