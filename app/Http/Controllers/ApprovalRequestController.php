<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInspectionRequest;
use App\Http\Requests\StoreTowerRequest;
use App\Models\ApprovalRequest;
use App\Models\Inspection;
use App\Models\Tower;
use App\Support\InspectorApprovals;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;

class ApprovalRequestController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ApprovalRequest::class);

        $user = $request->user();
        $status = $request->string('status')->toString() ?: ApprovalRequest::STATUS_PENDING;
        $type = $request->string('type')->toString();

        $query = ApprovalRequest::query()
            ->visibleTo($user)
            ->with(['submitter', 'reviewer', 'tower'])
            ->latest();

        if (in_array($status, [ApprovalRequest::STATUS_PENDING, ApprovalRequest::STATUS_APPROVED, ApprovalRequest::STATUS_REJECTED], true)) {
            $query->where('status', $status);
        }

        if (in_array($type, ApprovalRequest::TYPES, true)) {
            $query->where('type', $type);
        }

        return view('approvals.index', [
            'approvals' => $query->paginate(20)->withQueryString(),
            'pendingCount' => ApprovalRequest::query()->visibleTo($user)->pending()->count(),
            'isReviewer' => $user->canTask('approvals.review'),
        ]);
    }

    public function show(ApprovalRequest $approval): View
    {
        $this->authorize('view', $approval);

        $approval->load(['submitter', 'reviewer', 'tower.region', 'tower.operator', 'tower.district']);

        return view('approvals.show', [
            'approval' => $approval,
            'diffLines' => InspectorApprovals::diffLines($approval),
        ]);
    }

    public function edit(ApprovalRequest $approval): View
    {
        $this->authorize('update', $approval);

        $approval->load(['tower.region', 'tower.operator']);

        if ($approval->type === ApprovalRequest::TYPE_INSPECTION) {
            return view('inspections.create', [
                'tower' => $approval->tower,
                'defaults' => $approval->attributes(),
                'existingPhotos' => $approval->photos(),
                'formAction' => route('approvals.update', $approval),
                'formMethod' => 'PUT',
                'submitLabel' => __('app.approvals.save_corrections'),
                'cancelUrl' => route('approvals.show', $approval),
            ]);
        }

        $draft = $this->draftTower($approval);

        return view('towers.edit', array_merge(
            app(TowerController::class)->formData($draft),
            [
                'tower' => $draft,
                'pendingApproval' => $approval,
                'formAction' => route('approvals.update', $approval),
                'submitLabel' => __('app.approvals.save_corrections'),
                'cancelUrl' => route('approvals.show', $approval),
            ],
        ));
    }

    public function update(Request $request, ApprovalRequest $approval): RedirectResponse
    {
        $this->authorize('update', $approval);

        if ($approval->type === ApprovalRequest::TYPE_INSPECTION) {
            $inspectionRequest = app(StoreInspectionRequest::class);

            InspectorApprovals::amendInspection(
                $approval,
                $inspectionRequest->safe()->except('photos'),
                $this->inspectionUploads($inspectionRequest),
            );
        } else {
            $towerRequest = app(StoreTowerRequest::class);
            $liveTower = $approval->type === ApprovalRequest::TYPE_TOWER_UPDATE ? $approval->tower : null;
            $attributes = app(TowerController::class)->towerAttributes($towerRequest, $liveTower);
            $siteMap = $towerRequest->file('site_map');
            $siteMap = $siteMap instanceof UploadedFile ? $siteMap : null;

            InspectorApprovals::amendTower($approval, $attributes, $siteMap);
        }

        return redirect()->route('approvals.show', $approval)->with('status', __('app.approvals.amended'));
    }

    public function approve(Request $request, ApprovalRequest $approval): RedirectResponse
    {
        $this->authorize('review', $approval);

        $applied = InspectorApprovals::approve(
            $approval,
            $request->user(),
            $request->string('reviewer_comment')->trim()->toString() ?: null,
        );

        $redirect = $applied instanceof Inspection
            ? redirect()->route('towers.show', $applied->tower_id)
            : redirect()->route('towers.show', $applied);

        return $redirect->with('status', __('app.approvals.approved'));
    }

    public function reject(Request $request, ApprovalRequest $approval): RedirectResponse
    {
        $this->authorize('review', $approval);

        $validated = $request->validate([
            'reviewer_comment' => ['required', 'string', 'max:2000'],
        ]);

        InspectorApprovals::reject($approval, $request->user(), $validated['reviewer_comment']);

        return redirect()->route('approvals.index')->with('status', __('app.approvals.rejected'));
    }

    private function draftTower(ApprovalRequest $approval): Tower
    {
        $attributes = $approval->attributes();

        if ($approval->tower) {
            $tower = $approval->tower->replicate();
            $tower->id = $approval->tower->id;
            $tower->exists = true;
            $tower->fill($attributes);

            return $tower;
        }

        return new Tower($attributes);
    }

    /**
     * @return array<string, list<UploadedFile>>
     */
    private function inspectionUploads(StoreInspectionRequest $request): array
    {
        $paths = [];

        foreach (Inspection::photoSlots() as $slot) {
            $files = $request->file('photos.'.$slot);
            if (! $files) {
                continue;
            }

            $files = is_array($files) ? $files : [$files];
            $stored = array_values(array_filter($files, fn ($file) => $file instanceof UploadedFile));

            if ($stored !== []) {
                $paths[$slot] = $stored;
            }
        }

        return $paths;
    }
}
