<x-app-layout>
    <div class="p-4 lg:p-8">
        <div class="flex flex-wrap items-end justify-between gap-4 mb-5">
            <div>
                <p class="text-xs font-medium uppercase tracking-wider text-brand-muted">{{ __('app.app_short') }}</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-brand">{{ $isReviewer ? __('app.approvals.title') : __('app.approvals.title_own') }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ $isReviewer ? __('app.approvals.subtitle') : __('app.approvals.subtitle_own') }}</p>
            </div>
            @if ($pendingCount)
                <p class="rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-900">
                    {{ __('app.approvals.pending_count', ['count' => $pendingCount]) }}
                </p>
            @endif
        </div>

        <div class="data-card">
            <form method="GET" class="data-toolbar">
                <select name="status" class="field lg:w-48">
                    @foreach (['pending', 'approved', 'rejected'] as $status)
                        <option value="{{ $status }}" @selected(request('status', 'pending') === $status)>{{ __('app.approvals.status.'.$status) }}</option>
                    @endforeach
                </select>
                <select name="type" class="field lg:w-56">
                    <option value="">{{ __('app.approvals.type') }}</option>
                    @foreach (['tower_create', 'tower_update', 'inspection'] as $type)
                        <option value="{{ $type }}" @selected(request('type') === $type)>{{ __('app.approvals.types.'.$type) }}</option>
                    @endforeach
                </select>
                <div class="flex items-center gap-2">
                    <button class="inline-flex items-center px-4 py-2 bg-brand text-white text-sm font-semibold rounded-lg hover:bg-brand-dark">{{ __('app.filter') }}</button>
                    @if (request()->hasAny(['status', 'type']) && (request('status') !== 'pending' || request()->filled('type')))
                        <a href="{{ route('approvals.index') }}" class="text-sm text-gray-500 hover:text-brand">{{ __('app.reset') }}</a>
                    @endif
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('app.approvals.type') }}</th>
                            <th>{{ __('app.approvals.summary') }}</th>
                            @if ($isReviewer)
                                <th>{{ __('app.approvals.submitted_by') }}</th>
                            @endif
                            <th>{{ __('app.approvals.status_label') }}</th>
                            <th>{{ __('app.approvals.when') }}</th>
                            <th class="text-right">{{ __('app.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($approvals as $item)
                            <tr>
                                <td class="font-medium text-gray-900">{{ $item->typeLabel() }}</td>
                                <td class="text-gray-600">{{ $item->summary() }}</td>
                                @if ($isReviewer)
                                    <td class="text-gray-600">{{ $item->submitter?->name }}</td>
                                @endif
                                <td>
                                    <span @class([
                                        'inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold',
                                        'bg-amber-100 text-amber-900' => $item->status === 'pending',
                                        'bg-emerald-100 text-emerald-800' => $item->status === 'approved',
                                        'bg-red-100 text-red-800' => $item->status === 'rejected',
                                    ])>{{ $item->statusLabel() }}</span>
                                </td>
                                <td class="text-gray-500 whitespace-nowrap">{{ $item->created_at?->timezone(config('app.timezone'))->format('d M Y H:i') }}</td>
                                <td class="actions">
                                    @can('update', $item)
                                        <a href="{{ route('approvals.edit', $item) }}" class="text-sm font-semibold text-brand hover:underline">{{ __('app.approvals.correct') }}</a>
                                    @else
                                        <a href="{{ route('approvals.show', $item) }}" class="text-sm font-semibold text-brand hover:underline">{{ $isReviewer ? __('app.approvals.review') : __('app.approvals.view') }}</a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $isReviewer ? 6 : 5 }}" class="!py-16 text-center text-sm text-gray-500">{{ $isReviewer ? __('app.approvals.empty') : __('app.approvals.empty_own') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($approvals->total())
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 py-3 border-t border-gray-100 bg-gray-50/50">
                    <p class="text-sm text-gray-500">{{ $approvals->firstItem() }}–{{ $approvals->lastItem() }} / {{ $approvals->total() }}</p>
                    {{ $approvals->onEachSide(1)->links('pagination.towerline') }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
