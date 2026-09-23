<x-app-layout>
    <div class="p-4 lg:p-8">
        <div class="flex flex-wrap items-end justify-between gap-4 mb-5">
            <div>
                <p class="text-xs font-medium uppercase tracking-wider text-brand-muted">{{ __('app.app_short') }}</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-brand">{{ __('app.complaints.title') }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ $canCreate ? __('app.complaints.subtitle_officer') : __('app.complaints.subtitle_scoped') }}</p>
            </div>
            @if ($canCreate)
                <a href="{{ route('complaints.create') }}" class="inline-flex items-center px-4 py-2 bg-brand text-white text-sm font-semibold rounded-lg hover:bg-brand-dark">{{ __('app.complaints.intake') }}</a>
            @endif
        </div>

        <div class="data-card">
            <form method="GET" class="data-toolbar flex-wrap">
                <select name="status" class="field lg:w-44">
                    <option value="">{{ __('app.complaints.status_label') }}</option>
                    @foreach (\App\Models\Complaint::STATUSES as $value)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ __('app.complaints.status.'.$value) }}</option>
                    @endforeach
                </select>
                <select name="complaint_type" class="field lg:w-44">
                    <option value="">{{ __('app.complaints.type') }}</option>
                    @foreach (\App\Models\Complaint::TYPES as $value)
                        <option value="{{ $value }}" @selected(($filters['complaint_type'] ?? '') === $value)>{{ __('app.complaints.types.'.$value) }}</option>
                    @endforeach
                </select>
                <select name="region_id" class="field lg:w-44">
                    <option value="">{{ __('app.towers.region') }}</option>
                    @foreach ($regions as $region)
                        <option value="{{ $region->id }}" @selected((string) ($filters['region_id'] ?? '') === (string) $region->id)>{{ $region->localizedName() }}</option>
                    @endforeach
                </select>
                <select name="priority" class="field lg:w-36">
                    <option value="">{{ __('app.complaints.priority_label') }}</option>
                    @foreach (\App\Models\Complaint::PRIORITIES as $value)
                        <option value="{{ $value }}" @selected(($filters['priority'] ?? '') === $value)>{{ __('app.complaints.priority.'.$value) }}</option>
                    @endforeach
                </select>
                <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="field lg:w-40">
                <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="field lg:w-40">
                <select name="sort" class="field lg:w-40">
                    <option value="created_at" @selected(($filters['sort'] ?? 'created_at') === 'created_at')>{{ __('app.complaints.received_at') }}</option>
                    <option value="priority" @selected(($filters['sort'] ?? '') === 'priority')>{{ __('app.complaints.priority_label') }}</option>
                    <option value="status" @selected(($filters['sort'] ?? '') === 'status')>{{ __('app.complaints.status_label') }}</option>
                    <option value="reference_number" @selected(($filters['sort'] ?? '') === 'reference_number')>{{ __('app.complaints.reference') }}</option>
                </select>
                <select name="dir" class="field lg:w-28">
                    <option value="desc" @selected(($filters['dir'] ?? 'desc') === 'desc')>{{ __('app.complaints.newest') }}</option>
                    <option value="asc" @selected(($filters['dir'] ?? '') === 'asc')>{{ __('app.complaints.oldest') }}</option>
                </select>
                <button class="inline-flex items-center px-4 py-2 bg-brand text-white text-sm font-semibold rounded-lg hover:bg-brand-dark">{{ __('app.filter') }}</button>
            </form>

            @if ($canTriage)
                <form id="bulk-assign" method="POST" action="{{ route('complaints.bulk-assign') }}" class="px-4 pb-3 flex flex-wrap items-end gap-3 border-b border-gray-100">
                    @csrf
                    <select name="assigned_to" class="field lg:w-64" required>
                        <option value="">{{ __('app.complaints.select_assignee') }}</option>
                        @foreach ($bulkAssignees as $person)
                            <option value="{{ $person->id }}">{{ $person->name }} · {{ $person->roleLabel() }}</option>
                        @endforeach
                    </select>
                    <button class="inline-flex items-center px-4 py-2 bg-white border border-gray-200 text-sm font-semibold rounded-lg hover:bg-gray-50">{{ __('app.complaints.bulk_assign') }}</button>
                    <p class="text-xs text-gray-400">{{ __('app.complaints.bulk_hint') }}</p>
                </form>
            @endif

            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            @if ($canTriage)
                                <th></th>
                            @endif
                            <th>{{ __('app.complaints.reference') }}</th>
                            <th>{{ __('app.complaints.type') }}</th>
                            <th>{{ __('app.towers.region') }}</th>
                            <th>{{ __('app.complaints.priority_label') }}</th>
                            <th>{{ __('app.complaints.status_label') }}</th>
                            <th>{{ __('app.complaints.assignee') }}</th>
                            <th>{{ __('app.complaints.received_at') }}</th>
                            <th class="text-right">{{ __('app.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($complaints as $item)
                            <tr>
                                @if ($canTriage)
                                    <td><input type="checkbox" name="complaint_ids[]" value="{{ $item->id }}" form="bulk-assign" class="rounded border-gray-300 text-brand"></td>
                                @endif
                                <td class="font-mono text-sm font-semibold text-gray-900">{{ $item->reference_number }}</td>
                                <td class="text-gray-700">{{ $item->typeLabel() }}</td>
                                <td class="text-gray-600">{{ $item->region?->localizedName() }}</td>
                                <td>{{ $item->priorityLabel() }}</td>
                                <td>
                                    <span @class([
                                        'inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold',
                                        'bg-amber-100 text-amber-900' => $item->status === 'submitted',
                                        'bg-sky-100 text-sky-900' => $item->status === 'under_review',
                                        'bg-violet-100 text-violet-900' => $item->status === 'assigned',
                                        'bg-indigo-100 text-indigo-900' => $item->status === 'in_progress',
                                        'bg-emerald-100 text-emerald-900' => $item->status === 'resolved',
                                        'bg-gray-200 text-gray-800' => $item->status === 'closed',
                                        'bg-rose-100 text-rose-900' => $item->status === 'rejected',
                                    ])>{{ $item->statusLabel() }}</span>
                                </td>
                                <td class="text-gray-600">{{ $item->assignee?->name ?: '—' }}</td>
                                <td class="text-gray-500 whitespace-nowrap tabular-nums">{{ \App\Models\Complaint::stamp($item->created_at) }}</td>
                                <td class="actions">
                                    <a href="{{ route('complaints.show', $item) }}" class="text-sm font-semibold text-brand hover:underline">{{ __('app.view') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canTriage ? 9 : 8 }}" class="!py-16 text-center text-sm text-gray-500">{{ __('app.complaints.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($complaints->total())
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 py-3 border-t border-gray-100 bg-gray-50/50">
                    <p class="text-sm text-gray-500">{{ $complaints->firstItem() }}–{{ $complaints->lastItem() }} / {{ $complaints->total() }}</p>
                    {{ $complaints->onEachSide(1)->links('pagination.towerline') }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
