<x-app-layout>
    <div class="p-4 lg:p-8 max-w-4xl">
        <a href="{{ route('approvals.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
            {{ auth()->user()->canTask('approvals.review') ? __('app.approvals.title') : __('app.approvals.title_own') }}
        </a>

        <div class="mt-4 rounded-2xl bg-brand text-white px-5 py-5 sm:px-6">
            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-brand-light">{{ $approval->typeLabel() }}</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight">{{ $approval->summary() }}</h1>
            <p class="mt-2 text-sm text-white/75">
                {{ $approval->submitter?->name }}
                · {{ $approval->created_at?->timezone(config('app.timezone'))->format('d M Y H:i') }}
                · {{ $approval->statusLabel() }}
            </p>
        </div>

        @if ($approval->tower)
            <p class="mt-4 text-sm text-gray-500">
                {{ __('app.towers.name') }}:
                <a href="{{ route('towers.show', $approval->tower) }}" class="font-semibold text-brand hover:underline">{{ $approval->tower->name }}</a>
            </p>
        @endif

        <section class="mt-5 data-card p-5 sm:p-6 space-y-4">
            <h2 class="text-sm font-semibold text-brand">{{ __('app.approvals.preview') }}</h2>

            @if ($approval->type === 'tower_update' && $diffLines)
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>{{ __('app.approvals.field') }}</th>
                                <th>{{ __('app.approvals.current') }}</th>
                                <th>{{ __('app.approvals.proposed') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($diffLines as $line)
                                <tr>
                                    <td class="font-medium text-gray-900">{{ $line['label'] }}</td>
                                    <td class="text-gray-500">{{ $line['from'] }}</td>
                                    <td class="text-gray-900">{{ $line['to'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @elseif ($approval->type === 'inspection')
                @php $attrs = $approval->attributes(); @endphp
                <dl class="grid sm:grid-cols-2 gap-3 text-sm">
                    <div>
                        <dt class="text-gray-400">{{ __('app.inspections.power') }}</dt>
                        <dd class="mt-0.5 font-medium text-gray-900">{{ $attrs['power_status'] ? __('app.inspections.'.($attrs['power_status'] === 'generator' ? 'generator_power' : $attrs['power_status'])) : __('app.inspections.not_recorded') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-400">{{ __('app.inspections.generator') }}</dt>
                        <dd class="mt-0.5 font-medium text-gray-900">{{ $attrs['generator_condition'] ? __('app.inspections.'.$attrs['generator_condition']) : __('app.inspections.not_recorded') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-400">{{ __('app.inspections.physical') }}</dt>
                        <dd class="mt-0.5 font-medium text-gray-900">{{ $attrs['physical_condition'] ? __('app.inspections.'.$attrs['physical_condition']) : __('app.inspections.not_recorded') }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-gray-400">{{ __('app.inspections.comment') }}</dt>
                        <dd class="mt-0.5 text-gray-800">{{ $attrs['notes'] ?: '—' }}</dd>
                    </div>
                </dl>
                @php
                    $photoUrls = collect($approval->photos())->flatMap(fn ($paths) => $paths)->map(fn ($path) => asset('storage/'.$path));
                @endphp
                @if ($photoUrls->isNotEmpty())
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        @foreach ($photoUrls as $url)
                            <a href="{{ $url }}" target="_blank" class="block overflow-hidden rounded-lg border border-gray-200">
                                <img src="{{ $url }}" alt="" class="h-24 w-full object-cover">
                            </a>
                        @endforeach
                    </div>
                @endif
            @else
                @php $attrs = $approval->attributes(); @endphp
                <dl class="grid sm:grid-cols-2 gap-3 text-sm">
                    @foreach ([
                        'name' => $attrs['name'] ?? null,
                        'city' => $attrs['city'] ?? null,
                        'status' => isset($attrs['status']) ? __('app.status.'.$attrs['status']) : null,
                        'type' => isset($attrs['type']) ? __('app.status.'.$attrs['type']) : null,
                        'height_m' => $attrs['height_m'] ?? null,
                        'capacity' => $attrs['capacity'] ?? null,
                    ] as $key => $value)
                        @if ($value)
                            <div>
                                <dt class="text-gray-400">{{ __('app.audit.fields.'.$key, [], 'en') === 'app.audit.fields.'.$key ? str_replace('_', ' ', $key) : __('app.audit.fields.'.$key) }}</dt>
                                <dd class="mt-0.5 font-medium text-gray-900">{{ $value }}</dd>
                            </div>
                        @endif
                    @endforeach
                </dl>
            @endif

            @if ($approval->siteMapPath())
                <p class="text-sm">
                    <a href="{{ asset('storage/'.$approval->siteMapPath()) }}" target="_blank" class="font-semibold text-brand hover:underline">{{ __('app.approvals.site_map') }}</a>
                </p>
            @endif
        </section>

        @if ($approval->isPending())
            @can('update', $approval)
                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <a href="{{ route('approvals.edit', $approval) }}" class="inline-flex items-center px-5 py-2.5 bg-brand text-white text-sm font-semibold rounded-xl hover:bg-brand-dark">
                        {{ __('app.approvals.correct_this') }}
                    </a>
                    <p class="text-sm text-gray-500">{{ __('app.approvals.correct_hint') }}</p>
                </div>
            @endcan
            @can('review', $approval)
                <section class="mt-5 data-card p-5 sm:p-6 space-y-4">
                    <h2 class="text-sm font-semibold text-brand">{{ __('app.approvals.decision') }}</h2>
                    <form method="POST" action="{{ route('approvals.approve', $approval) }}" class="space-y-3">
                        @csrf
                        <label class="block">
                            <span class="text-sm font-medium text-gray-700">{{ __('app.approvals.comment_optional') }}</span>
                            <textarea name="reviewer_comment" rows="2" class="field mt-1 w-full">{{ old('reviewer_comment') }}</textarea>
                        </label>
                        <button class="inline-flex items-center px-5 py-2.5 bg-brand text-white text-sm font-semibold rounded-xl hover:bg-brand-dark">{{ __('app.approvals.approve') }}</button>
                    </form>
                    <form method="POST" action="{{ route('approvals.reject', $approval) }}" class="space-y-3 border-t border-gray-100 pt-4">
                        @csrf
                        <label class="block">
                            <span class="text-sm font-medium text-gray-700">{{ __('app.approvals.comment_required') }}</span>
                            <textarea name="reviewer_comment" rows="2" class="field mt-1 w-full" required>{{ old('reviewer_comment') }}</textarea>
                            @error('reviewer_comment') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </label>
                        <button class="inline-flex items-center px-5 py-2.5 bg-red-50 text-red-800 text-sm font-semibold rounded-xl hover:bg-red-100">{{ __('app.approvals.reject') }}</button>
                    </form>
                </section>
            @endcan
        @else
            <section class="mt-5 data-card p-5 sm:p-6 text-sm text-gray-600 space-y-1">
                <p>{{ __('app.approvals.reviewed_by') }}: {{ $approval->reviewer?->name ?? '—' }}</p>
                <p>{{ __('app.approvals.reviewed_at') }}: {{ $approval->reviewed_at?->timezone(config('app.timezone'))->format('d M Y H:i') ?? '—' }}</p>
                @if ($approval->reviewer_comment)
                    <p>{{ __('app.approvals.comment') }}: {{ $approval->reviewer_comment }}</p>
                @endif
            </section>
        @endif
    </div>
</x-app-layout>
