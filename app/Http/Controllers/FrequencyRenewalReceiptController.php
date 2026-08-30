<?php

namespace App\Http\Controllers;

use App\Models\FrequencyAllocation;
use App\Models\FrequencyRenewalReceipt;
use Illuminate\View\View;

class FrequencyRenewalReceiptController extends Controller
{
    public function show(FrequencyAllocation $frequency): View
    {
        $this->authorize('view', $frequency);

        $frequency->load([
            'operator',
            'region',
            'renewedFrom',
            'currentReceipt.issuer',
            'currentReceipt.letter',
        ]);

        return view('frequency-receipts.show', [
            'allocation' => $frequency,
            'receipt' => $frequency->currentReceipt,
        ]);
    }

    public function print(FrequencyAllocation $frequency): View
    {
        $this->authorize('view', $frequency);

        $receipt = $frequency->currentReceipt()
            ->with(['allocation.operator', 'allocation.region', 'allocation.renewedFrom', 'letter', 'issuer'])
            ->firstOrFail();

        $this->authorize('view', $receipt);

        return view('frequency-receipts.print', [
            'allocation' => $frequency,
            'receipt' => $receipt,
        ]);
    }
}
