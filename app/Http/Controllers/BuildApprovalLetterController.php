<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBuildApprovalLetterRequest;
use App\Models\BuildApprovalLetter;
use App\Models\Tower;
use App\Support\Audits;
use App\Support\BuildApprovalLetterNumberGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BuildApprovalLetterController extends Controller
{
    public function show(Tower $tower): View
    {
        $this->authorize('view', $tower);

        $tower->load([
            'region',
            'district',
            'subDistrict',
            'operator',
            'currentApprovalLetter.issuer',
        ]);

        $letter = $tower->currentApprovalLetter;

        return view('approval-letters.show', compact('tower', 'letter'));
    }

    public function store(StoreBuildApprovalLetterRequest $request, Tower $tower): RedirectResponse
    {
        $this->authorize('create', [BuildApprovalLetter::class, $tower]);

        $tower->load(['operator']);

        $letter = BuildApprovalLetter::query()->create([
            'tower_id' => $tower->id,
            'operator_id' => $tower->operator_id,
            'reference_number' => BuildApprovalLetterNumberGenerator::generate(),
            'status' => $request->input('status', BuildApprovalLetter::STATUS_APPROVED),
            'issued_at' => $request->input('issued_at', now()->toDateString()),
            'issued_by' => $request->user()->id,
            'template_version' => BuildApprovalLetter::TEMPLATE_VERSION,
        ]);

        Audits::log('created', $letter, [
            'tower_id' => $tower->id,
            'reference_number' => $letter->reference_number,
            'status' => $letter->status,
        ]);

        return redirect()
            ->route('towers.approval-letter.show', $tower)
            ->with('status', __('app.approval_letters.generated'));
    }

    public function print(Tower $tower): View
    {
        $this->authorize('view', $tower);

        $letter = $tower->currentApprovalLetter()
            ->with(['tower.region', 'tower.district', 'tower.subDistrict', 'tower.operator', 'issuer'])
            ->firstOrFail();

        $this->authorize('view', $letter);

        return view('approval-letters.print', compact('tower', 'letter'));
    }
}
