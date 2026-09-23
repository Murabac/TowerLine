<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreComplaintRequest;
use App\Models\Complaint;
use App\Models\Region;
use App\Models\Role;
use App\Models\Tower;
use App\Models\User;
use App\Notifications\ComplaintAssigned;
use App\Notifications\ComplaintAwaitingClosure;
use App\Support\ComplaintNumberGenerator;
use App\Support\SomalilandPhone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ComplaintController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Complaint::class);

        $user = $request->user();
        $sort = in_array($request->string('sort')->toString(), ['created_at', 'priority', 'status', 'reference_number'], true)
            ? $request->string('sort')->toString()
            : 'created_at';
        $direction = $request->string('dir')->toString() === 'asc' ? 'asc' : 'desc';

        $query = Complaint::query()
            ->visibleTo($user)
            ->with(['region', 'tower.operator', 'assignee'])
            ->orderBy($sort, $direction);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('complaint_type')) {
            $query->where('complaint_type', $request->string('complaint_type'));
        }

        if ($request->filled('region_id')) {
            $query->where('region_id', $request->integer('region_id'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->string('priority'));
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->date('to'));
        }

        $regions = Region::query()->orderBy('name_en');
        if ($user->requiresRegions()) {
            $regions->whereIn('id', $user->regionIds() ?: [0]);
        }

        return view('complaints.index', [
            'complaints' => $query->paginate(15)->withQueryString(),
            'canCreate' => $user->can('create', Complaint::class),
            'canTriage' => $user->canTask('complaints.triage'),
            'regions' => $regions->get(),
            'filters' => $request->only(['status', 'complaint_type', 'region_id', 'priority', 'from', 'to', 'sort', 'dir']),
            'bulkAssignees' => $user->canTask('complaints.triage')
                ? User::query()->whereIn('role', [Role::KEY_INSPECTOR, Role::KEY_OPERATOR_VIEWER])->orderBy('name')->get()
                : collect(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Complaint::class);

        return view('complaints.create', [
            'regions' => Region::query()->orderBy('name_en')->get(),
            'towersUrl' => route('map.towers'),
        ]);
    }

    public function store(StoreComplaintRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $tower = isset($data['tower_id']) ? Tower::query()->find($data['tower_id']) : null;

        if ($tower) {
            $data['region_id'] = $tower->region_id;
            $data['latitude'] = $data['latitude'] ?? $tower->latitude;
            $data['longitude'] = $data['longitude'] ?? $tower->longitude;
        }

        $complaint = Complaint::query()->create([
            'reference_number' => ComplaintNumberGenerator::generate(),
            'tower_id' => $tower?->id,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'region_id' => $data['region_id'],
            'complaint_type' => $data['complaint_type'],
            'description' => $data['description'],
            'submitter_name' => $data['submitter_name'] ?? null,
            'submitter_phone' => SomalilandPhone::format($data['submitter_phone'] ?? null),
            'status' => Complaint::STATUS_SUBMITTED,
            'priority' => $data['priority'] ?? Complaint::PRIORITY_MEDIUM,
            'created_by' => $request->user()->id,
        ]);

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('complaints/'.$complaint->id, 'local');
            $complaint->forceFill(['photo_path' => $path])->save();
        }

        $complaint->logUpdate($request->user(), __('app.complaints.logged_intake'), Complaint::STATUS_SUBMITTED);

        return redirect()
            ->route('complaints.show', $complaint)
            ->with('status', __('app.complaints.created', ['ref' => $complaint->reference_number]));
    }

    public function show(Complaint $complaint): View
    {
        $this->authorize('view', $complaint);

        $complaint->load(['region', 'tower.operator', 'creator', 'assignee', 'updates.user']);

        return view('complaints.show', [
            'complaint' => $complaint,
            'canTriage' => request()->user()->can('triage', $complaint),
            'canRespond' => request()->user()->can('respond', $complaint),
            'canAssign' => request()->user()->can('assign', $complaint),
            'assignees' => $complaint->assignableUsers(),
        ]);
    }

    public function update(Request $request, Complaint $complaint): RedirectResponse
    {
        $this->authorize('triage', $complaint);

        $data = $request->validate([
            'status' => ['required', 'string', Rule::in(Complaint::STATUSES)],
            'priority' => ['required', 'string', Rule::in(Complaint::PRIORITIES)],
            'note' => ['nullable', 'string', 'max:2000'],
            'resolution_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $from = $complaint->status;
        $payload = [
            'status' => $data['status'],
            'priority' => $data['priority'],
        ];

        if (array_key_exists('resolution_notes', $data)) {
            $payload['resolution_notes'] = $data['resolution_notes'];
        }

        if (in_array($data['status'], [Complaint::STATUS_RESOLVED, Complaint::STATUS_CLOSED], true) && ! $complaint->resolved_at) {
            $payload['resolved_at'] = now();
        }

        if ($data['status'] === Complaint::STATUS_REJECTED) {
            $payload['resolved_at'] = $complaint->resolved_at ?? now();
        }

        $complaint->update($payload);

        $note = $data['note'] ?: __('app.complaints.status_changed', [
            'from' => __('app.complaints.status.'.$from),
            'to' => __('app.complaints.status.'.$data['status']),
        ]);

        $complaint->logUpdate($request->user(), $note, $from !== $data['status'] ? $data['status'] : null);

        return back()->with('status', __('app.complaints.updated'));
    }

    public function assign(Request $request, Complaint $complaint): RedirectResponse
    {
        $this->authorize('assign', $complaint);

        $data = $request->validate([
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $assignee = User::query()->findOrFail($data['assigned_to']);
        abort_unless($complaint->assignableUsers()->contains('id', $assignee->id), 422);

        $from = $complaint->status;
        $status = in_array($from, [Complaint::STATUS_SUBMITTED, Complaint::STATUS_UNDER_REVIEW, Complaint::STATUS_ASSIGNED], true)
            ? Complaint::STATUS_ASSIGNED
            : $from;

        $complaint->update([
            'assigned_to' => $assignee->id,
            'status' => $status,
        ]);

        $note = ($data['note'] ?? null) ?: __('app.complaints.assigned_to', ['name' => $assignee->name]);
        $complaint->logUpdate($request->user(), $note, $from !== $status ? $status : null);

        if ((int) $assignee->id !== (int) $request->user()->id) {
            $assignee->notify(new ComplaintAssigned($complaint->fresh(['region', 'tower'])));
        }

        return back()->with('status', __('app.complaints.assigned_to', ['name' => $assignee->name]));
    }

    public function bulkAssign(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', Complaint::class);
        abort_unless($request->user()->canTask('complaints.triage'), 403);

        $data = $request->validate([
            'complaint_ids' => ['required', 'array', 'min:1'],
            'complaint_ids.*' => ['integer', 'exists:complaints,id'],
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
        ]);

        $assignee = User::query()->findOrFail($data['assigned_to']);
        $count = 0;

        foreach (Complaint::query()->whereIn('id', $data['complaint_ids'])->get() as $complaint) {
            if (! $request->user()->can('assign', $complaint)) {
                continue;
            }

            if (! $complaint->assignableUsers()->contains('id', $assignee->id)) {
                continue;
            }

            $from = $complaint->status;
            $status = in_array($from, [Complaint::STATUS_SUBMITTED, Complaint::STATUS_UNDER_REVIEW, Complaint::STATUS_ASSIGNED], true)
                ? Complaint::STATUS_ASSIGNED
                : $from;

            $complaint->update([
                'assigned_to' => $assignee->id,
                'status' => $status,
            ]);
            $complaint->logUpdate($request->user(), __('app.complaints.assigned_to', ['name' => $assignee->name]), $from !== $status ? $status : null);
            $count++;
        }

        if ($count && (int) $assignee->id !== (int) $request->user()->id) {
            $assignee->notify(new ComplaintAssigned(Complaint::query()->findOrFail($data['complaint_ids'][0])));
        }

        return back()->with('status', __('app.complaints.bulk_assigned', ['count' => $count]));
    }

    public function respond(Request $request, Complaint $complaint): RedirectResponse
    {
        $this->authorize('respond', $complaint);

        $data = $request->validate([
            'note' => ['required', 'string', 'max:5000'],
            'mark_resolved' => ['nullable', 'boolean'],
        ]);

        $from = $complaint->status;
        $markResolved = $request->boolean('mark_resolved');

        if ($markResolved) {
            abort_unless($complaint->isOpen() && $complaint->status !== Complaint::STATUS_RESOLVED, 422);

            $complaint->update([
                'status' => Complaint::STATUS_RESOLVED,
                'resolution_notes' => $data['note'],
                'resolved_at' => now(),
            ]);
            $complaint->logUpdate($request->user(), $data['note'], Complaint::STATUS_RESOLVED);

            $officers = User::query()
                ->whereIn('role', Complaint::triageRoleKeys())
                ->whereKeyNot($request->user()->id)
                ->get();

            Notification::send($officers, new ComplaintAwaitingClosure($complaint->fresh()));
        } else {
            if ($from === Complaint::STATUS_ASSIGNED) {
                $complaint->update(['status' => Complaint::STATUS_IN_PROGRESS]);
            }
            $complaint->logUpdate(
                $request->user(),
                $data['note'],
                $from === Complaint::STATUS_ASSIGNED ? Complaint::STATUS_IN_PROGRESS : null,
            );
        }

        return back()->with('status', $markResolved ? __('app.complaints.marked_resolved') : __('app.complaints.note_added'));
    }

    public function photo(Complaint $complaint): StreamedResponse|Response
    {
        $this->authorize('view', $complaint);
        abort_unless($complaint->photoExists(), 404);

        return Storage::disk('local')->response($complaint->photo_path);
    }
}
