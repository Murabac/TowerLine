<x-app-layout>
    <div class="p-4 lg:p-8">
        <div class="flex flex-wrap items-end justify-between gap-4 mb-5">
            <div>
                <p class="text-xs font-medium uppercase tracking-wider text-brand-muted">{{ __('app.app_short') }}</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-brand">{{ __('app.applications.title') }}</h1>
                <p class="mt-1 text-sm text-gray-500">
                    @if ($canAssign)
                        {{ __('app.applications.subtitle_assign') }}
                    @elseif ($canGrant)
                        {{ __('app.applications.subtitle_dg') }}
                    @elseif ($seesAll)
                        {{ __('app.applications.subtitle_director') }}
                    @else
                        {{ __('app.applications.subtitle_assigned') }}
                    @endif
                </p>
            </div>
            @if ($canAssign && $pendingCount)
                <p class="rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-900">
                    {{ __('app.applications.pending_count', ['count' => $pendingCount]) }}
                </p>
            @endif
        </div>

        <div class="data-card">
            <form method="GET" class="data-toolbar">
                <select name="status" class="field lg:w-56">
                    @if ($dgInbox)
                        <option value="received" @selected($status === 'received')>{{ __('app.applications.status.received') }}</option>
                        <option value="returned" @selected($status === 'returned')>{{ __('app.applications.status.returned') }}</option>
                        <option value="assigned" @selected($status === 'assigned')>{{ __('app.applications.status.assigned') }}</option>
                        <option value="refused" @selected($status === 'refused')>{{ __('app.applications.status.refused') }}</option>
                        <option value="granted" @selected($status === 'granted')>{{ __('app.applications.status.granted') }}</option>
                        <option value="all" @selected($status === 'all')>{{ __('app.all') }}</option>
                    @else
                        @if ($canAssign)
                            <option value="pending" @selected($status === 'pending')>{{ __('app.applications.status.pending') }}</option>
                        @endif
                        @if ($canAssign || $seesAll)
                            <option value="received" @selected($status === 'received')>{{ __('app.applications.status.received') }}</option>
                            <option value="returned" @selected($status === 'returned')>{{ __('app.applications.status.returned') }}</option>
                            <option value="director_review" @selected($status === 'director_review')>{{ __('app.applications.status.director_review') }}</option>
                            <option value="dg_review" @selected($status === 'dg_review')>{{ __('app.applications.status.dg_review') }}</option>
                            <option value="granted" @selected($status === 'granted')>{{ __('app.applications.status.granted') }}</option>
                            <option value="refused" @selected($status === 'refused')>{{ __('app.applications.status.refused') }}</option>
                        @endif
                        <option value="assigned" @selected($status === 'assigned')>{{ __('app.applications.status.assigned') }}</option>
                        <option value="all" @selected($status === 'all')>{{ __('app.all') }}</option>
                    @endif
                </select>
                <div class="flex items-center gap-2">
                    <button class="inline-flex items-center px-4 py-2 bg-brand text-white text-sm font-semibold rounded-lg hover:bg-brand-dark">{{ __('app.filter') }}</button>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('app.apply.tracking') }}</th>
                            <th>{{ __('app.apply.site_name') }}</th>
                            <th>{{ __('app.apply.operator') }}</th>
                            <th>{{ __('app.apply.region') }}</th>
                            <th>{{ __('app.approvals.status_label') }}</th>
                            <th>{{ __('app.applications.assignee') }}</th>
                            <th>{{ __('app.applications.received_at') }}</th>
                            <th class="text-right">{{ __('app.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($applications as $item)
                            <tr>
                                <td class="font-mono text-sm font-semibold text-gray-900">{{ $item->reference_number }}</td>
                                <td class="text-gray-700">{{ $item->site_name }}</td>
                                <td class="text-gray-600">{{ $item->operator?->name }}</td>
                                <td class="text-gray-600">{{ $item->region?->name_en }}</td>
                                <td>
                                    <span @class([
                                        'inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold',
                                        'bg-amber-100 text-amber-900' => $item->isReceived(),
                                        'bg-sky-100 text-sky-900' => $item->isAssigned(),
                                        'bg-violet-100 text-violet-900' => $item->isDirectorReview(),
                                        'bg-indigo-100 text-indigo-900' => $item->isDgReview(),
                                        'bg-emerald-100 text-emerald-900' => $item->isGranted(),
                                        'bg-gray-200 text-gray-800' => $item->isRefused(),
                                        'bg-rose-100 text-rose-900' => $item->isReturned() || $item->isReturnedToDirector(),
                                    ])>{{ $item->statusLabel() }}</span>
                                </td>
                                <td class="text-gray-600">
                                    {{ $item->assignee?->name ?: '—' }}
                                    @if ($item->assigned_at)
                                        <p class="text-xs text-gray-400 tabular-nums">{{ \App\Models\SiteApplication::stamp($item->assigned_at) }}</p>
                                    @endif
                                </td>
                                <td class="text-gray-500 whitespace-nowrap tabular-nums">{{ \App\Models\SiteApplication::stamp($item->created_at) ?: '—' }}</td>
                                <td class="actions">
                                    <a href="{{ route('applications.show', $item) }}" class="text-sm font-semibold text-brand hover:underline">
                                        @if ($canAssign && ($item->isReceived() || $item->isReturned()))
                                            {{ __('app.applications.assign') }}
                                        @elseif ($canAssign && $item->isAssigned())
                                            {{ __('app.applications.status.assigned') }}
                                        @elseif ($canReview && $item->canBeReviewed())
                                            {{ __('app.applications.review') }}
                                        @elseif ($canConcur && $item->canReceiveDirectorDecision())
                                            {{ __('app.applications.decide') }}
                                        @elseif ($canGrant && $item->canReceiveDgDecision())
                                            {{ __('app.applications.grant') }}
                                        @else
                                            {{ __('app.view') }}
                                        @endif
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="!py-16 text-center text-sm text-gray-500">{{ $canAssign ? __('app.applications.empty') : __('app.applications.empty_assigned') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($applications->total())
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 py-3 border-t border-gray-100 bg-gray-50/50">
                    <p class="text-sm text-gray-500">{{ $applications->firstItem() }}–{{ $applications->lastItem() }} / {{ $applications->total() }}</p>
                    {{ $applications->onEachSide(1)->links('pagination.towerline') }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
