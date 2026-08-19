<x-app-layout>
    <div class="p-4 lg:p-8 max-w-5xl">
        <div class="mb-5 max-w-2xl">
            <p class="text-xs font-medium uppercase tracking-wider text-brand-muted">{{ __('app.app_short') }}</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-brand">{{ __('app.audit.title') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('app.audit.subtitle') }}</p>
        </div>

        <div class="mb-5 flex gap-2 sm:gap-3">
            <a href="{{ route('audit-logs.index', array_filter(['action' => 'created', 'user_id' => request('user_id')])) }}"
               class="flex min-w-0 flex-1 flex-row items-baseline justify-between gap-2 rounded-2xl border px-3 py-3 sm:px-4 {{ request('action') === 'created' ? 'border-emerald-400 bg-emerald-100 ring-2 ring-emerald-500/30' : 'border-emerald-200 bg-emerald-50 hover:border-emerald-300 hover:bg-emerald-100' }}">
                <p class="truncate text-[11px] font-semibold uppercase tracking-[0.08em] text-emerald-700">{{ __('app.audit.actions.created') }}</p>
                <p class="text-xl sm:text-2xl font-semibold tracking-tight text-emerald-800">{{ number_format($counts['created']) }}</p>
            </a>
            <a href="{{ route('audit-logs.index', array_filter(['action' => 'updated', 'user_id' => request('user_id')])) }}"
               class="flex min-w-0 flex-1 flex-row items-baseline justify-between gap-2 rounded-2xl border px-3 py-3 sm:px-4 {{ request('action') === 'updated' ? 'border-sky-400 bg-sky-100 ring-2 ring-sky-500/30' : 'border-sky-200 bg-sky-50 hover:border-sky-300 hover:bg-sky-100' }}">
                <p class="truncate text-[11px] font-semibold uppercase tracking-[0.08em] text-sky-700">{{ __('app.audit.actions.updated') }}</p>
                <p class="text-xl sm:text-2xl font-semibold tracking-tight text-sky-800">{{ number_format($counts['updated']) }}</p>
            </a>
            <a href="{{ route('audit-logs.index', array_filter(['action' => 'deleted', 'user_id' => request('user_id')])) }}"
               class="flex min-w-0 flex-1 flex-row items-baseline justify-between gap-2 rounded-2xl border px-3 py-3 sm:px-4 {{ request('action') === 'deleted' ? 'border-red-400 bg-red-100 ring-2 ring-red-500/30' : 'border-red-200 bg-red-50 hover:border-red-300 hover:bg-red-100' }}">
                <p class="truncate text-[11px] font-semibold uppercase tracking-[0.08em] text-red-700">{{ __('app.audit.actions.deleted') }}</p>
                <p class="text-xl sm:text-2xl font-semibold tracking-tight text-red-800">{{ number_format($counts['deleted']) }}</p>
            </a>
        </div>

        <div class="data-card">
            <form method="GET" class="data-toolbar items-end border-b-0">
                <div class="lg:w-52">
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-gray-400">{{ __('app.audit.who') }}</label>
                    <select name="user_id" class="field">
                        <option value="">{{ __('app.all') }}</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected((string) request('user_id') === (string) $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lg:w-44">
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-gray-400">{{ __('app.audit.what') }}</label>
                    <select name="action" class="field">
                        <option value="">{{ __('app.all') }}</option>
                        @foreach ($actions as $action)
                            <option value="{{ $action }}" @selected(request('action') === $action)>{{ __('app.audit.actions.'.$action) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lg:w-40">
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-gray-400">{{ __('app.audit.from') }}</label>
                    <input type="date" name="from" value="{{ request('from') }}" class="field">
                </div>
                <div class="lg:w-40">
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-gray-400">{{ __('app.audit.to') }}</label>
                    <input type="date" name="to" value="{{ request('to') }}" class="field">
                </div>
                <div class="flex items-center gap-2 pb-0.5">
                    <button class="inline-flex items-center px-4 py-2 bg-brand text-white text-sm font-semibold rounded-lg hover:bg-brand-dark">{{ __('app.filter') }}</button>
                    @if (request()->hasAny(['user_id', 'action', 'from', 'to']))
                        <a href="{{ route('audit-logs.index') }}" class="text-sm text-gray-500 hover:text-brand">{{ __('app.reset') }}</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="mt-4 space-y-3">
            @forelse ($logs as $log)
                @php
                    $card = $log->action === 'deleted'
                        ? ['wrap' => 'border-red-300 bg-red-100', 'badge' => 'bg-red-200 text-red-900 ring-red-700/20', 'details' => 'border-red-200 bg-white']
                        : ($log->action === 'created'
                            ? ['wrap' => 'border-emerald-300 bg-emerald-100', 'badge' => 'bg-emerald-200 text-emerald-900 ring-emerald-700/20', 'details' => 'border-emerald-200 bg-white']
                            : ['wrap' => 'border-sky-300 bg-sky-100', 'badge' => 'bg-sky-200 text-sky-900 ring-sky-700/20', 'details' => 'border-sky-200 bg-white']);
                @endphp
                <article class="rounded-2xl border px-4 py-4 sm:px-5 {{ $card['wrap'] }}">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $card['badge'] }}">
                                {{ $log->actionLabel() }}
                            </span>
                            <h2 class="mt-2 text-base font-semibold text-gray-900">{{ $log->sentence() }}</h2>
                            <p class="mt-1 text-sm text-gray-500">{{ $log->created_at->timezone(config('app.timezone'))->format('d M Y · H:i') }} · {{ $log->created_at->diffForHumans() }}</p>
                        </div>
                        @if ($log->recordUrl())
                            <a href="{{ $log->recordUrl() }}" class="text-sm font-semibold text-brand hover:underline shrink-0">{{ __('app.audit.open') }}</a>
                        @endif
                    </div>

                    @if ($log->detailLines())
                        <div class="mt-3 rounded-xl border px-3 py-3 sm:px-4 {{ $card['details'] }}">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-400">{{ __('app.audit.details') }}</p>
                            <dl class="mt-2 grid sm:grid-cols-2 gap-x-6">
                                @foreach ($log->detailLines() as $line)
                                    <div class="flex justify-between gap-3 border-b border-gray-200/60 py-1.5 last:border-b-0">
                                        <dt class="text-sm text-gray-500 shrink-0">{{ $line['label'] }}</dt>
                                        <dd class="text-sm font-medium text-gray-900 text-right">{{ $line['value'] }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </div>
                    @else
                        <p class="mt-3 text-sm text-gray-400">{{ __('app.audit.no_details') }}</p>
                    @endif
                </article>
            @empty
                <p class="rounded-2xl border border-gray-200 bg-white px-5 py-16 text-center text-sm text-gray-500">{{ __('app.audit.empty') }}</p>
            @endforelse
        </div>

        @if ($logs->total())
            <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 rounded-2xl border border-gray-200 bg-white px-4 py-3">
                <p class="text-sm text-gray-500">{{ $logs->firstItem() }}–{{ $logs->lastItem() }} / {{ $logs->total() }}</p>
                {{ $logs->onEachSide(1)->links('pagination.towerline') }}
            </div>
        @endif
    </div>
</x-app-layout>
