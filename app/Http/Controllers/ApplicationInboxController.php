<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignSiteApplicationRequest;
use App\Http\Requests\ConcurSiteApplicationRequest;
use App\Http\Requests\GrantSiteApplicationRequest;
use App\Http\Requests\ReviewSiteApplicationRequest;
use App\Models\SiteApplication;
use App\Models\User;
use App\Support\Audits;
use App\Support\SiteApplicationPermit;
use App\Support\UserSignature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationInboxController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', SiteApplication::class);

        $user = $request->user();
        $status = $request->string('status')->toString();
        $allowed = ['pending', 'received', 'assigned', 'returned', 'director_review', 'dg_review', 'granted', 'refused', 'all'];
        $canAssign = $user->canTask('applications.assign');
        $canReview = $user->canTask('applications.review');
        $canConcur = $user->canTask('applications.concur');
        $canGrant = $user->canTask('applications.grant');
        $seesAll = $user->managesSiteApplications();

        $query = SiteApplication::query()
            ->visibleTo($user)
            ->with(['operator', 'region', 'district', 'assignee'])
            ->latest();

        if (! in_array($status, $allowed, true)) {
            $status = $canAssign
                ? 'pending'
                : ($canGrant
                    ? SiteApplication::STATUS_DG_REVIEW
                    : ($canConcur || $seesAll
                        ? SiteApplication::STATUS_DIRECTOR_REVIEW
                        : SiteApplication::STATUS_ASSIGNED));
        }

        if ($status === 'pending') {
            $query->whereIn('status', [SiteApplication::STATUS_RECEIVED, SiteApplication::STATUS_RETURNED]);
        } elseif ($status !== 'all') {
            $query->where('status', $status);
        }

        return view('applications.index', [
            'applications' => $query->paginate(20)->withQueryString(),
            'status' => $status,
            'pendingCount' => SiteApplication::query()
                ->visibleTo($user)
                ->whereIn('status', [SiteApplication::STATUS_RECEIVED, SiteApplication::STATUS_RETURNED])
                ->count(),
            'canAssign' => $canAssign,
            'canReview' => $canReview,
            'canConcur' => $canConcur,
            'canGrant' => $canGrant,
            'seesAll' => $seesAll,
        ]);
    }

    public function show(Request $request, SiteApplication $application): View
    {
        $this->authorize('view', $application);

        $application->load([
            'operator',
            'region',
            'district',
            'subDistrict',
            'assignee',
            'assigner',
            'visitor',
            'officerReviewer',
            'directorReviewer',
            'dgReviewer',
            'tower.currentApprovalLetter',
        ]);

        return view('applications.show', [
            'application' => $application,
            'assignees' => $request->user()->can('assign', $application)
                ? SiteApplication::assigneesForRegion((int) $application->region_id)
                : collect(),
        ]);
    }

    public function assign(AssignSiteApplicationRequest $request, SiteApplication $application): RedirectResponse
    {
        $assignee = User::query()->findOrFail($request->integer('assigned_to'));

        $application->assignTo($assignee, $request->user());
        Audits::log('assigned', $application, [
            'reference_number' => $application->reference_number,
            'assigned_to' => $assignee->id,
            'assigned_to_name' => $assignee->name,
        ]);

        return redirect()
            ->route('applications.show', $application)
            ->with('status', __('app.applications.assigned', ['name' => $assignee->name]));
    }

    public function review(ReviewSiteApplicationRequest $request, SiteApplication $application): RedirectResponse
    {
        $decision = $request->string('decision')->toString();

        $application->recordOfficerReview(
            $request->user(),
            $decision,
            $request->string('site_visit_on')->toString(),
            $request->string('site_visit_notes')->toString(),
            $request->string('officer_remarks')->toString(),
            UserSignature::capture($request->user(), $request, 'applications/'.$application->id, 'officer'),
        );

        Audits::log($decision === SiteApplication::DECISION_APPROVE ? 'officer_approved' : 'officer_returned', $application, [
            'reference_number' => $application->reference_number,
            'decision' => $decision,
            'site_visit_on' => $application->site_visit_on?->toDateString(),
        ]);

        return redirect()
            ->route('applications.show', $application)
            ->with('status', $decision === SiteApplication::DECISION_APPROVE
                ? __('app.applications.approved_to_director')
                : __('app.applications.returned_to_head'));
    }

    public function concur(ConcurSiteApplicationRequest $request, SiteApplication $application): RedirectResponse
    {
        $decision = $request->string('decision')->toString();

        $application->recordDirectorDecision(
            $request->user(),
            $decision,
            $request->string('director_remarks')->toString(),
            $request->string('director_name')->toString(),
            UserSignature::capture($request->user(), $request, 'applications/'.$application->id, 'director'),
        );

        Audits::log($decision === SiteApplication::DECISION_YES ? 'director_concurred' : 'director_refused', $application, [
            'reference_number' => $application->reference_number,
            'decision' => $decision,
            'director_name' => $application->director_name,
        ]);

        return redirect()
            ->route('applications.show', $application)
            ->with('status', $decision === SiteApplication::DECISION_YES
                ? __('app.applications.sent_to_dg')
                : __('app.applications.refused_by_director'));
    }

    public function grant(GrantSiteApplicationRequest $request, SiteApplication $application): RedirectResponse
    {
        $decision = $request->string('decision')->toString();

        $tower = DB::transaction(function () use ($request, $application, $decision) {
            $application->recordDgDecision(
                $request->user(),
                $decision,
                $request->string('dg_remarks')->toString(),
                $request->string('dg_name')->toString(),
                UserSignature::capture($request->user(), $request, 'applications/'.$application->id, 'dg'),
            );

            if ($decision !== SiteApplication::DECISION_GRANT) {
                return null;
            }

            return SiteApplicationPermit::issue($application, $request->user());
        });

        if ($tower) {
            Audits::log('granted', $application, [
                'reference_number' => $application->reference_number,
                'tower_id' => $tower->id,
                'tower_name' => $tower->name,
            ]);

            return redirect()
                ->route('applications.show', $application)
                ->with('status', __('app.applications.granted', ['tower' => $tower->name]));
        }

        Audits::log('dg_returned', $application, [
            'reference_number' => $application->reference_number,
        ]);

        return redirect()
            ->route('applications.show', $application)
            ->with('status', __('app.applications.returned_to_director'));
    }

    public function permitPrint(Request $request, SiteApplication $application): View
    {
        $this->authorize('view', $application);
        abort_unless($application->isGranted() && $application->tower, 404);

        $copy = $request->string('copy')->toString() === 'customer' ? 'customer' : 'hq';
        $tower = $application->tower()->with([
            'region',
            'district',
            'subDistrict',
            'operator',
            'currentApprovalLetter.issuer',
        ])->firstOrFail();
        $letter = $tower->currentApprovalLetter;
        abort_unless($letter, 404);
        $this->authorize('view', $letter);

        $application->loadMissing(['officerReviewer', 'directorReviewer', 'dgReviewer']);

        return view('approval-letters.print', [
            'tower' => $tower,
            'letter' => $letter,
            'permitApplication' => $application,
            'copy' => $copy,
            'signatureImages' => [
                'officer' => UserSignature::dataUri($application->officer_signature_path),
                'director' => UserSignature::dataUri($application->director_signature_path),
                'dg' => UserSignature::dataUri($application->dg_signature_path),
            ],
        ]);
    }

    public function signature(SiteApplication $application, string $party): StreamedResponse
    {
        $this->authorize('view', $application);
        abort_unless(in_array($party, ['officer', 'director', 'dg'], true), 404);

        $path = match ($party) {
            'officer' => $application->officer_signature_path,
            'director' => $application->director_signature_path,
            default => $application->dg_signature_path,
        };
        abort_unless(UserSignature::exists($path), 404);

        return Storage::disk('local')->response($path, $party.'-signature.png', [
            'Content-Type' => 'image/png',
        ]);
    }

    public function document(SiteApplication $application, string $document): StreamedResponse
    {
        $this->authorize('view', $application);

        abort_unless(array_key_exists($document, SiteApplication::DOCUMENT_FIELDS), 404);

        $path = $application->documentPath($document);
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        $extension = pathinfo($path, PATHINFO_EXTENSION) ?: 'bin';
        $filename = str_replace('/', '-', $application->reference_number).'-'.$document.'.'.$extension;

        return Storage::disk('local')->download($path, $filename);
    }
}
