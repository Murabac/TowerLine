<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFrequencyAllocationLetterRequest;
use App\Models\FrequencyAllocation;
use App\Models\FrequencyAllocationLetter;
use App\Support\Audits;
use App\Support\FrequencyAllocationLetterNumberGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FrequencyAllocationLetterController extends Controller
{
    public function show(FrequencyAllocation $frequency): View
    {
        $this->authorize('view', $frequency);

        $frequency->load([
            'operator',
            'region',
            'renewedFrom',
            'currentLetter.issuer',
        ]);

        return view('frequency-letters.show', [
            'allocation' => $frequency,
            'letter' => $frequency->currentLetter,
        ]);
    }

    public function store(StoreFrequencyAllocationLetterRequest $request, FrequencyAllocation $frequency): RedirectResponse
    {
        $this->authorize('create', [FrequencyAllocationLetter::class, $frequency]);

        $frequency->load('operator');

        $letter = FrequencyAllocationLetter::query()->create([
            'frequency_allocation_id' => $frequency->id,
            'operator_id' => $frequency->operator_id,
            'reference_number' => FrequencyAllocationLetterNumberGenerator::generate(),
            'issued_at' => $request->input('issued_at', now()->toDateString()),
            'issued_by' => $request->user()->id,
            'template_version' => FrequencyAllocationLetter::TEMPLATE_VERSION,
        ]);

        Audits::log('created', $letter, [
            'frequency_allocation_id' => $frequency->id,
            'reference_number' => $letter->reference_number,
        ]);

        return redirect()
            ->route('frequencies.letter.show', $frequency)
            ->with('status', __('app.frequency_letters.generated'));
    }

    public function print(FrequencyAllocation $frequency): View
    {
        $this->authorize('view', $frequency);

        $letter = $frequency->currentLetter()
            ->with(['allocation.operator', 'allocation.region', 'issuer'])
            ->firstOrFail();

        $this->authorize('view', $letter);

        return view('frequency-letters.print', [
            'allocation' => $frequency,
            'letter' => $letter,
        ]);
    }
}
