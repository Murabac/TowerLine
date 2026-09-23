<x-app-layout>
    <div class="p-4 lg:p-8 max-w-5xl">
        <a href="{{ route('complaints.index') }}" class="text-sm text-brand hover:underline">{{ __('app.back') }}</a>
        <div class="mt-2 flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="font-mono text-sm font-semibold text-brand">{{ $complaint->reference_number }}</p>
                <h1 class="mt-1 text-2xl font-semibold text-brand">{{ $complaint->typeLabel() }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ $complaint->region?->localizedName() }} · {{ $complaint->priorityLabel() }}</p>
            </div>
            <span @class([
                'inline-flex rounded-full px-3 py-1 text-sm font-semibold',
                'bg-amber-100 text-amber-900' => $complaint->status === 'submitted',
                'bg-sky-100 text-sky-900' => $complaint->status === 'under_review',
                'bg-violet-100 text-violet-900' => $complaint->status === 'assigned',
                'bg-indigo-100 text-indigo-900' => $complaint->status === 'in_progress',
                'bg-emerald-100 text-emerald-900' => $complaint->status === 'resolved',
                'bg-gray-200 text-gray-800' => $complaint->status === 'closed',
                'bg-rose-100 text-rose-900' => $complaint->status === 'rejected',
            ])>{{ $complaint->statusLabel() }}</span>
        </div>

        <div class="mt-6 grid lg:grid-cols-5 gap-5">
            <div class="lg:col-span-3 space-y-5">
                <section class="data-card p-5">
                    <h2 class="text-sm font-semibold text-brand">{{ __('app.complaints.summary') }}</h2>
                    <p class="mt-3 text-sm leading-6 text-gray-700 whitespace-pre-wrap">{{ $complaint->description }}</p>
                    <dl class="mt-4 grid sm:grid-cols-2 gap-3 text-sm">
                        <div>
                            <dt class="text-xs text-gray-400">{{ __('app.complaints.tower') }}</dt>
                            <dd class="font-medium text-gray-900">
                                @if ($complaint->tower)
                                    <a href="{{ route('towers.show', $complaint->tower) }}" class="text-brand hover:underline">{{ $complaint->tower->name }}</a>
                                    <span class="text-gray-500"> · {{ $complaint->tower->operator?->name }}</span>
                                @else
                                    {{ __('app.complaints.no_tower') }}
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-400">{{ __('app.complaints.submitter') }}</dt>
                            <dd class="text-gray-800">{{ $complaint->submitter_name ?: '—' }} {{ $complaint->submitter_phone }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-400">{{ __('app.complaints.logged_by') }}</dt>
                            <dd class="text-gray-800">{{ $complaint->creator?->name }} · {{ \App\Models\Complaint::stamp($complaint->created_at) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-400">{{ __('app.complaints.assignee') }}</dt>
                            <dd class="text-gray-800">{{ $complaint->assignee?->name ?: '—' }}</dd>
                        </div>
                    </dl>
                    @if ($complaint->photoExists())
                        <a href="{{ $complaint->photoUrl() }}" class="mt-4 inline-block">
                            <img src="{{ $complaint->photoUrl() }}" alt="" class="max-h-56 rounded-lg border border-gray-200">
                        </a>
                    @endif
                    @if ($complaint->resolution_notes)
                        <div class="mt-4 rounded-lg bg-gray-50 p-3 text-sm text-gray-700">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">{{ __('app.complaints.resolution_notes') }}</p>
                            <p class="mt-1 whitespace-pre-wrap">{{ $complaint->resolution_notes }}</p>
                        </div>
                    @endif
                </section>

                @if ($complaint->mapLatitude() && $complaint->mapLongitude())
                    <section class="data-card overflow-hidden">
                        <div id="complaint-detail-map" class="h-64"></div>
                    </section>
                @endif

                <section class="data-card p-5">
                    <h2 class="text-sm font-semibold text-brand">{{ __('app.complaints.timeline') }}</h2>
                    <ol class="mt-4 space-y-3">
                        @forelse ($complaint->updates as $update)
                            <li class="border-l-2 border-brand/20 pl-3">
                                <p class="text-sm text-gray-800">{{ $update->note }}</p>
                                <p class="mt-0.5 text-xs text-gray-400">
                                    {{ $update->user?->name }}
                                    @if ($update->status_change)
                                        · {{ __('app.complaints.status.'.$update->status_change) }}
                                    @endif
                                    · {{ \App\Models\Complaint::stamp($update->created_at) }}
                                </p>
                            </li>
                        @empty
                            <li class="text-sm text-gray-500">{{ __('app.none') }}</li>
                        @endforelse
                    </ol>
                </section>
            </div>

            <div class="lg:col-span-2 space-y-5">
                @if ($canAssign)
                    <form method="POST" action="{{ route('complaints.assign', $complaint) }}" class="data-card p-5 space-y-3">
                        @csrf
                        <h2 class="text-sm font-semibold text-brand">{{ __('app.complaints.assign') }}</h2>
                        <select name="assigned_to" class="field" required>
                            <option value="">{{ __('app.complaints.select_assignee') }}</option>
                            @foreach ($assignees as $person)
                                <option value="{{ $person->id }}" @selected((int) $complaint->assigned_to === (int) $person->id)>{{ $person->name }} · {{ $person->roleLabel() }}</option>
                            @endforeach
                        </select>
                        <textarea name="note" rows="2" class="field" placeholder="{{ __('app.complaints.internal_note') }}"></textarea>
                        <x-primary-button>{{ __('app.complaints.assign') }}</x-primary-button>
                    </form>
                @endif

                @if ($canTriage)
                    <form method="POST" action="{{ route('complaints.update', $complaint) }}" class="data-card p-5 space-y-3">
                        @csrf
                        @method('PUT')
                        <h2 class="text-sm font-semibold text-brand">{{ __('app.complaints.triage') }}</h2>
                        <select name="status" class="field" required>
                            @foreach (\App\Models\Complaint::STATUSES as $status)
                                <option value="{{ $status }}" @selected($complaint->status === $status)>{{ __('app.complaints.status.'.$status) }}</option>
                            @endforeach
                        </select>
                        <select name="priority" class="field" required>
                            @foreach (\App\Models\Complaint::PRIORITIES as $priority)
                                <option value="{{ $priority }}" @selected($complaint->priority === $priority)>{{ __('app.complaints.priority.'.$priority) }}</option>
                            @endforeach
                        </select>
                        <textarea name="resolution_notes" rows="3" class="field" placeholder="{{ __('app.complaints.resolution_notes') }}">{{ old('resolution_notes', $complaint->resolution_notes) }}</textarea>
                        <textarea name="note" rows="2" class="field" placeholder="{{ __('app.complaints.internal_note') }}"></textarea>
                        <x-primary-button>{{ __('app.save') }}</x-primary-button>
                    </form>
                @elseif ($canRespond)
                    <form method="POST" action="{{ route('complaints.respond', $complaint) }}" class="data-card p-5 space-y-3">
                        @csrf
                        <h2 class="text-sm font-semibold text-brand">{{ __('app.complaints.respond') }}</h2>
                        <p class="text-xs text-gray-500">{{ __('app.complaints.respond_hint') }}</p>
                        <textarea name="note" rows="4" class="field" required>{{ old('note') }}</textarea>
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="mark_resolved" value="1" class="rounded border-gray-300 text-brand">
                            {{ __('app.complaints.mark_resolved') }}
                        </label>
                        <x-primary-button>{{ __('app.complaints.add_note') }}</x-primary-button>
                    </form>
                @endif
            </div>
        </div>
    </div>
    @if ($complaint->mapLatitude() && $complaint->mapLongitude())
        @push('head')
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
        @endpush
        @push('scripts')
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
            <script>
                const map = L.map('complaint-detail-map').setView([{{ $complaint->mapLatitude() }}, {{ $complaint->mapLongitude() }}], 13);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap', maxZoom: 19 }).addTo(map);
                L.marker([{{ $complaint->mapLatitude() }}, {{ $complaint->mapLongitude() }}]).addTo(map);
            </script>
        @endpush
    @endif
</x-app-layout>
