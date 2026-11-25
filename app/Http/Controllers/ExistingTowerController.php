<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExistingTowerRequest;
use App\Models\ApprovalRequest;
use App\Models\District;
use App\Models\Operator;
use App\Models\Region;
use App\Support\ExistingTowerRegistration;
use App\Support\InspectorApprovals;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Quick registration of towers that already stand. Submissions wait for the
 * Director of Telecom (towers.confirm_existing) in the approval queue.
 */
class ExistingTowerController extends Controller
{
    public function create(Request $request): View
    {
        abort_unless($request->user()->canTask('towers.register_existing'), 403);

        return view('towers.existing', $this->formData($request));
    }

    public function store(StoreExistingTowerRequest $request): RedirectResponse
    {
        $approval = ExistingTowerRegistration::submit($request->user(), $request->validated());

        return redirect()
            ->route('approvals.show', $approval)
            ->with('status', __('app.existing_towers.submitted'));
    }

    /**
     * Correction form, used by the submitter or the Director while the registration is pending.
     */
    public function edit(Request $request, ApprovalRequest $approval): View
    {
        $this->authorize('update', $approval);
        abort_unless($approval->isExistingTower(), 404);

        return view('towers.existing', $this->formData($request, $approval));
    }

    public function update(StoreExistingTowerRequest $request, ApprovalRequest $approval): RedirectResponse
    {
        abort_unless($approval->isExistingTower(), 404);

        $reviewer = $request->user()->can('review', $approval);

        ExistingTowerRegistration::amend($approval, $request->validated());

        // The Director can correct and confirm in one step, so the tower goes live straight away.
        if ($reviewer && $request->boolean('confirm')) {
            $tower = InspectorApprovals::approve($approval->fresh(), $request->user());

            return redirect()->route('towers.show', $tower)->with('status', __('app.existing_towers.confirmed'));
        }

        return redirect()->route('approvals.show', $approval)->with('status', __(
            $reviewer ? 'app.existing_towers.amended_by_reviewer' : 'app.approvals.amended'
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Request $request, ?ApprovalRequest $approval = null): array
    {
        $user = $request->user();
        $regions = Region::query()->orderBy('name_en')->get();

        if ($user->requiresRegions()) {
            $regions = $regions->whereIn('id', $user->regionIds() ?: [0])->values();
        }

        return [
            'approval' => $approval,
            'values' => $approval?->attributes() ?? [],
            'operators' => Operator::query()->orderBy('name')->get(['id', 'name']),
            'regions' => $regions,
            'districts' => District::query()
                ->whereIn('region_id', $regions->pluck('id'))
                ->with('subDistricts')
                ->orderBy('name')
                ->get(['id', 'region_id', 'name']),
            'duplicates' => session('existing_tower_duplicates', []),
        ];
    }
}
