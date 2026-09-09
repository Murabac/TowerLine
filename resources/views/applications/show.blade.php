@php
    $operatorColor = $application->operator?->color ?: '#1b4d3e';
    $hasPin = $application->latitude !== null && $application->longitude !== null;
    $locationLine = collect([
        $application->region?->localizedName(),
        $application->district?->localizedName(),
        $application->subDistrict?->localizedName(),
        $application->city,
    ])->filter()->unique()->implode(' / ');
    $hasProximity = filled($application->nearest_school_name)
        || filled($application->nearest_school_m)
        || filled($application->nearest_hospital_name)
        || filled($application->nearest_hospital_m)
        || filled($application->nearest_house_name)
        || filled($application->nearest_house_m)
        || filled($application->site_map_notes);
    $statusChip = match (true) {
        $application->isReceived() => 'bg-amber-50 text-amber-900 ring-amber-600/15',
        $application->isAssigned() => 'bg-sky-50 text-sky-900 ring-sky-600/15',
        $application->isDirectorReview() => 'bg-violet-50 text-violet-900 ring-violet-600/15',
        $application->isDgReview() => 'bg-indigo-50 text-indigo-900 ring-indigo-600/15',
        $application->isGranted() => 'bg-emerald-50 text-emerald-900 ring-emerald-600/15',
        $application->isRefused() => 'bg-gray-100 text-gray-800 ring-gray-500/15',
        $application->isReturned() => 'bg-rose-50 text-rose-900 ring-rose-600/15',
        default => 'bg-gray-100 text-gray-700 ring-gray-500/10',
    };
@endphp

<x-app-layout>
    <div class="p-4 lg:p-8 space-y-5">
        <a href="{{ route('applications.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
            {{ __('app.applications.title') }}
        </a>

        <div class="data-card">
            <div class="flex flex-wrap items-start justify-between gap-4 px-5 py-5 border-b border-gray-100">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2 mb-2">
                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $statusChip }}">{{ $application->statusLabel() }}</span>
                        @if ($application->operator)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-50 px-2.5 py-1 text-xs font-medium text-gray-700 ring-1 ring-inset ring-gray-200">
                                <span class="h-2 w-2 rounded-full shrink-0" style="background: {{ $operatorColor }}"></span>
                                {{ $application->operator->name }}
                            </span>
                        @endif
                    </div>
                    <h1 class="text-2xl font-semibold tracking-tight text-brand">{{ $application->site_name }}</h1>
                    <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-gray-500">
                        <span class="font-mono text-xs font-semibold text-gray-700">{{ $application->reference_number }}</span>
                        @if ($locationLine)
                            <span class="text-gray-300">·</span>
                            <span>{{ $locationLine }}</span>
                        @endif
                        @if ($application->assignee)
                            <span class="text-gray-300">·</span>
                            <span>{{ __('app.applications.assignee') }}: {{ $application->assignee->name }}</span>
                        @endif
                    </div>
                </div>
                @if ($application->isGranted() && $application->tower)
                    <a href="{{ route('towers.show', $application->tower) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand text-white text-sm font-semibold rounded-xl hover:bg-brand-dark shrink-0">
                        {{ __('app.applications.open_tower') }}
                    </a>
                @endif
            </div>

            <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-3 p-5">
                <div class="stat-tile">
                    <p class="stat-label">{{ __('app.towers.type') }}</p>
                    <p class="stat-value">{{ $application->type ? __('app.status.'.$application->type) : '—' }}</p>
                </div>
                <div class="stat-tile">
                    <p class="stat-label">{{ __('app.towers.height') }}</p>
                    <p class="stat-value tabular-nums">
                        @if ($application->height_m)
                            {{ rtrim(rtrim(number_format((float) $application->height_m, 1), '0'), '.') }} <span class="text-sm font-medium text-gray-400">m</span>
                        @else
                            —
                        @endif
                    </p>
                </div>
                <div class="stat-tile">
                    <p class="stat-label">{{ __('app.towers.capacity') }}</p>
                    <p class="stat-value text-sm leading-snug">{{ $application->capacity ? __('app.towers.capacities.'.$application->capacity) : '—' }}</p>
                </div>
                <div class="stat-tile">
                    <p class="stat-label">{{ __('app.towers.power_source') }}</p>
                    <p class="stat-value text-sm leading-snug">{{ \App\Support\TowerPowerSource::labelList($application->power_sources) ?: '—' }}</p>
                </div>
            </div>
        </div>

        <div class="data-card overflow-hidden">
            <x-section-bar :title="__('app.towers.location')">
                @if ($hasPin)
                    <x-slot:sub>
                        <p class="mt-0.5 text-xs font-mono text-white/75">{{ number_format((float) $application->latitude, 6) }}, {{ number_format((float) $application->longitude, 6) }}</p>
                    </x-slot:sub>
                @endif
                @if ($hasPin && $application->signal_radius_m)
                    <x-slot:end>
                        <p class="text-xs text-white/75 shrink-0">{{ __('app.map.coverage') }} · {{ number_format((float) $application->signal_radius_m / 1000, 1) }} km</p>
                    </x-slot:end>
                @endif
            </x-section-bar>
            @if ($hasPin)
                <div id="application-map" class="h-[22rem]"></div>
            @else
                <div class="flex h-40 items-center justify-center bg-gray-50 text-sm text-gray-500">{{ __('app.applications.no_coordinates') }}</div>
            @endif
        </div>

        <div class="grid gap-5 lg:grid-cols-5">
            <div class="lg:col-span-3 space-y-5">
                <div class="data-card overflow-hidden">
                    <x-section-bar :title="__('app.applications.applicant')" />
                    <dl class="p-5 sm:p-6 grid gap-4 sm:grid-cols-2 text-sm">
                        <div>
                            <dt class="stat-label">{{ __('app.apply.contact_name') }}</dt>
                            <dd class="mt-1 font-medium text-gray-900">{{ $application->contact_name }}</dd>
                        </div>
                        <div>
                            <dt class="stat-label">{{ __('app.apply.telephone') }}</dt>
                            <dd class="mt-1 font-medium text-gray-900">{{ $application->telephone }}</dd>
                        </div>
                        <div>
                            <dt class="stat-label">{{ __('app.apply.email') }}</dt>
                            <dd class="mt-1 font-medium text-gray-900 break-all">{{ $application->email }}</dd>
                        </div>
                        <div>
                            <dt class="stat-label">{{ __('app.apply.address') }}</dt>
                            <dd class="mt-1 font-medium text-gray-900">{{ $application->address ?: '—' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="stat-label">{{ __('app.apply.license_class_no') }}</dt>
                            <dd class="mt-1 font-medium text-gray-900">{{ $application->license_class_no }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="data-card overflow-hidden">
                    <x-section-bar :title="__('app.applications.site')" />
                    <dl class="p-5 sm:p-6 grid gap-4 sm:grid-cols-2 text-sm">
                        <div>
                            <dt class="stat-label">{{ __('app.apply.region') }}</dt>
                            <dd class="mt-1 font-medium text-gray-900">{{ $application->region?->localizedName() ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="stat-label">{{ __('app.apply.district') }}</dt>
                            <dd class="mt-1 font-medium text-gray-900">{{ $application->district?->localizedName() ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="stat-label">{{ __('app.apply.sub_district') }}</dt>
                            <dd class="mt-1 font-medium text-gray-900">{{ $application->subDistrict?->localizedName() ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="stat-label">{{ __('app.apply.city') }}</dt>
                            <dd class="mt-1 font-medium text-gray-900">{{ $application->city ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="stat-label">{{ __('app.towers.form_fields.land_area') }}</dt>
                            <dd class="mt-1 font-medium text-gray-900">{{ $application->land_area ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="stat-label">{{ __('app.towers.form_fields.fence_distance_m') }}</dt>
                            <dd class="mt-1 font-medium text-gray-900">{{ $application->fence_distance_m ?: '—' }}</dd>
                        </div>
                        @if ($application->signal_radius_m)
                            <div class="sm:col-span-2">
                                <dt class="stat-label">{{ __('app.towers.signal_radius') }}</dt>
                                <dd class="mt-1 font-medium text-gray-900 tabular-nums">{{ number_format((float) $application->signal_radius_m) }} m</dd>
                            </div>
                        @endif
                    </dl>
                </div>

                @if ($hasProximity)
                    <div class="data-card overflow-hidden">
                        <x-section-bar :title="__('app.apply.proximity')" />
                        <dl class="p-5 sm:p-6 grid gap-4 sm:grid-cols-2 text-sm">
                            <div>
                                <dt class="stat-label">{{ __('app.towers.form_fields.nearest_school') }}</dt>
                                <dd class="mt-1 font-medium text-gray-900">{{ $application->nearest_school_name ?: '—' }}@if ($application->nearest_school_m) <span class="font-normal text-gray-500">· {{ $application->nearest_school_m }} m</span>@endif</dd>
                            </div>
                            <div>
                                <dt class="stat-label">{{ __('app.towers.form_fields.nearest_hospital') }}</dt>
                                <dd class="mt-1 font-medium text-gray-900">{{ $application->nearest_hospital_name ?: '—' }}@if ($application->nearest_hospital_m) <span class="font-normal text-gray-500">· {{ $application->nearest_hospital_m }} m</span>@endif</dd>
                            </div>
                            <div>
                                <dt class="stat-label">{{ __('app.towers.form_fields.nearest_house') }}</dt>
                                <dd class="mt-1 font-medium text-gray-900">{{ $application->nearest_house_name ?: '—' }}@if ($application->nearest_house_m) <span class="font-normal text-gray-500">· {{ $application->nearest_house_m }} m</span>@endif</dd>
                            </div>
                            @if ($application->site_map_notes)
                                <div class="sm:col-span-2">
                                    <dt class="stat-label">{{ __('app.towers.form_fields.site_map_notes') }}</dt>
                                    <dd class="mt-1 text-gray-800 whitespace-pre-wrap">{{ $application->site_map_notes }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                @endif

                <div class="data-card overflow-hidden">
                    <x-section-bar :title="__('app.apply.documents')" :hint="__('app.applications.documents_readonly')" />
                    <ul class="divide-y divide-gray-100">
                        @foreach (array_keys(\App\Models\SiteApplication::DOCUMENT_FIELDS) as $field)
                            <li>
                                @if ($application->documentPath($field))
                                    <a href="{{ route('applications.documents.show', [$application, $field]) }}" class="flex items-center justify-between gap-3 px-4 py-3 hover:bg-gray-50">
                                        <span class="text-sm font-medium text-gray-900">{{ __('app.apply.'.$field) }}</span>
                                        <span class="text-xs font-semibold text-brand">{{ __('app.view') }}</span>
                                    </a>
                                @else
                                    <div class="flex items-center justify-between gap-3 px-4 py-3">
                                        <span class="text-sm text-gray-400">{{ __('app.apply.'.$field) }}</span>
                                        <span class="text-xs text-gray-300">—</span>
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>

                @if ($application->hasSiteVisit() || $application->officer_remarks)
                    <div class="data-card overflow-hidden">
                        <x-section-bar :title="__('app.applications.visit_record')" />
                        <dl class="p-5 sm:p-6 grid gap-4 sm:grid-cols-2 text-sm">
                            <div>
                                <dt class="stat-label">{{ __('app.applications.visit_on') }}</dt>
                                <dd class="mt-1 font-medium text-gray-900">{{ $application->site_visit_on?->timezone(config('app.timezone'))->format('d M Y') ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="stat-label">{{ __('app.applications.visited_by') }}</dt>
                                <dd class="mt-1 font-medium text-gray-900">{{ $application->visitor?->name ?: '—' }}</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="stat-label">{{ __('app.applications.visit_notes') }}</dt>
                                <dd class="mt-1 text-gray-800 whitespace-pre-wrap">{{ $application->site_visit_notes ?: '—' }}</dd>
                            </div>
                            @if ($application->officer_remarks)
                                <div class="sm:col-span-2">
                                    <dt class="stat-label">{{ __('app.applications.officer_remarks') }}</dt>
                                    <dd class="mt-1 text-gray-800 whitespace-pre-wrap">{{ $application->officer_remarks }}</dd>
                                    @if ($application->officerReviewer)
                                        <p class="mt-1 text-xs text-gray-500">{{ $application->officerReviewer->name }}@if ($application->officer_reviewed_at) · {{ $application->officer_reviewed_at->timezone(config('app.timezone'))->format('d M Y H:i') }}@endif</p>
                                    @endif
                                </div>
                            @endif
                        </dl>
                    </div>
                @endif

                @if ($application->director_reviewed_at)
                    <div class="data-card overflow-hidden">
                        <x-section-bar :title="__('app.applications.director_record')" />
                        <dl class="p-5 sm:p-6 grid gap-4 sm:grid-cols-2 text-sm">
                            <div>
                                <dt class="stat-label">{{ __('app.applications.decision') }}</dt>
                                <dd class="mt-1 font-medium text-gray-900">{{ __('app.applications.decision_'.$application->director_decision) }}</dd>
                            </div>
                            <div>
                                <dt class="stat-label">{{ __('app.applications.director_name') }}</dt>
                                <dd class="mt-1 font-medium text-gray-900">{{ $application->director_name }}</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="stat-label">{{ __('app.applications.director_remarks') }}</dt>
                                <dd class="mt-1 text-gray-800 whitespace-pre-wrap">{{ $application->director_remarks }}</dd>
                                @if ($application->directorReviewer)
                                    <p class="mt-1 text-xs text-gray-500">{{ $application->directorReviewer->name }}@if ($application->director_reviewed_at) · {{ $application->director_reviewed_at->timezone(config('app.timezone'))->format('d M Y H:i') }}@endif</p>
                                @endif
                            </div>
                        </dl>
                    </div>
                @endif

                @if ($application->dg_reviewed_at)
                    <div class="data-card overflow-hidden">
                        <x-section-bar :title="__('app.applications.dg_record')" />
                        <dl class="p-5 sm:p-6 grid gap-4 sm:grid-cols-2 text-sm">
                            <div>
                                <dt class="stat-label">{{ __('app.applications.decision') }}</dt>
                                <dd class="mt-1 font-medium text-gray-900">{{ __('app.applications.decision_'.$application->dg_decision) }}</dd>
                            </div>
                            <div>
                                <dt class="stat-label">{{ __('app.applications.dg_name') }}</dt>
                                <dd class="mt-1 font-medium text-gray-900">{{ $application->dg_name }}</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="stat-label">{{ __('app.applications.dg_remarks') }}</dt>
                                <dd class="mt-1 text-gray-800 whitespace-pre-wrap">{{ $application->dg_remarks }}</dd>
                                @if ($application->dgReviewer)
                                    <p class="mt-1 text-xs text-gray-500">{{ $application->dgReviewer->name }}@if ($application->dg_reviewed_at) · {{ $application->dg_reviewed_at->timezone(config('app.timezone'))->format('d M Y H:i') }}@endif</p>
                                @endif
                            </div>
                        </dl>
                    </div>
                @endif

                @if (count($application->approvalSignatories()))
                    <div class="data-card overflow-hidden">
                        <x-section-bar :title="__('app.signatures.trail')" :hint="__('app.signatures.trail_hint')" />
                        <div class="p-5 sm:p-6">
                            @include('applications._signatories', [
                                'application' => $application,
                                'signatories' => $application->approvalSignatories(),
                                'showHeading' => false,
                            ])
                        </div>
                    </div>
                @endif
            </div>

            <aside class="lg:col-span-2 space-y-5">
                @can('review', $application)
                    <section class="data-card overflow-hidden">
                        <x-section-bar :title="__('app.applications.review_title')" :hint="__('app.applications.review_hint')" />
                        <form method="POST" action="{{ route('applications.review', $application) }}" class="p-5 sm:p-6 space-y-3">
                            @csrf
                            <label class="block">
                                <span class="text-sm font-medium text-gray-700">{{ __('app.applications.visit_on') }}</span>
                                <input type="date" name="site_visit_on" value="{{ old('site_visit_on', $application->site_visit_on?->toDateString() ?? now()->toDateString()) }}" max="{{ now()->toDateString() }}" class="field mt-1 w-full" required>
                                @error('site_visit_on') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </label>
                            <label class="block">
                                <span class="text-sm font-medium text-gray-700">{{ __('app.applications.visit_notes') }}</span>
                                <textarea name="site_visit_notes" rows="3" class="field mt-1 w-full" required>{{ old('site_visit_notes', $application->site_visit_notes) }}</textarea>
                                @error('site_visit_notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </label>
                            <label class="block">
                                <span class="text-sm font-medium text-gray-700">{{ __('app.applications.officer_remarks') }}</span>
                                <textarea name="officer_remarks" rows="3" class="field mt-1 w-full" required>{{ old('officer_remarks', $application->officer_remarks) }}</textarea>
                                @error('officer_remarks') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </label>
                            <x-signature-pad :saved-url="Auth::user()->hasSavedSignature() ? route('profile.signature.show') : null" />
                            @error('decision') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                            <div class="flex flex-col gap-2">
                                <button type="submit" name="decision" value="approve" class="inline-flex w-full items-center justify-center rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-dark">
                                    {{ __('app.applications.approve') }}
                                </button>
                                <button type="submit" name="decision" value="reject" class="inline-flex w-full items-center justify-center rounded-xl bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-800 hover:bg-rose-100">
                                    {{ __('app.applications.reject') }}
                                </button>
                            </div>
                        </form>
                    </section>
                @endcan

                @can('concur', $application)
                    <section class="data-card overflow-hidden">
                        <x-section-bar :title="__('app.applications.concur_title')" :hint="__('app.applications.concur_hint', [], 'en')" />
                        <form method="POST" action="{{ route('applications.concur', $application) }}" class="p-5 sm:p-6 space-y-3">
                            @csrf
                            <label class="block">
                                <span class="text-sm font-medium text-gray-700">{{ __('app.applications.director_remarks') }}</span>
                                <textarea name="director_remarks" rows="3" class="field mt-1 w-full" required>{{ old('director_remarks', $application->director_remarks) }}</textarea>
                                @error('director_remarks') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </label>
                            <label class="block">
                                <span class="text-sm font-medium text-gray-700">{{ __('app.applications.director_name') }}</span>
                                <input type="text" name="director_name" value="{{ old('director_name', $application->director_name ?: Auth::user()->name) }}" class="field mt-1 w-full" required maxlength="120">
                                @error('director_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </label>
                            <x-signature-pad :saved-url="Auth::user()->hasSavedSignature() ? route('profile.signature.show') : null" />
                            @error('decision') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                            <div class="flex flex-col gap-2">
                                <button type="submit" name="decision" value="yes" class="inline-flex w-full items-center justify-center rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-dark">
                                    {{ __('app.applications.yes') }}
                                </button>
                                <button type="submit" name="decision" value="no" class="inline-flex w-full items-center justify-center rounded-xl bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-800 hover:bg-rose-100">
                                    {{ __('app.applications.no') }}
                                </button>
                            </div>
                        </form>
                    </section>
                @endcan

                @can('grant', $application)
                    <section class="data-card overflow-hidden">
                        <x-section-bar :title="__('app.applications.grant_title')" :hint="__('app.applications.grant_hint')" />
                        <form method="POST" action="{{ route('applications.grant', $application) }}" class="p-5 sm:p-6 space-y-3">
                            @csrf
                            <label class="block">
                                <span class="text-sm font-medium text-gray-700">{{ __('app.applications.dg_remarks') }}</span>
                                <textarea name="dg_remarks" rows="3" class="field mt-1 w-full" required>{{ old('dg_remarks') }}</textarea>
                                @error('dg_remarks') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </label>
                            <label class="block">
                                <span class="text-sm font-medium text-gray-700">{{ __('app.applications.dg_name') }}</span>
                                <input type="text" name="dg_name" value="{{ old('dg_name', Auth::user()->name) }}" class="field mt-1 w-full" required maxlength="120">
                                @error('dg_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </label>
                            <x-signature-pad :saved-url="Auth::user()->hasSavedSignature() ? route('profile.signature.show') : null" />
                            @error('decision') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                            <div class="flex flex-col gap-2">
                                <button type="submit" name="decision" value="grant" class="inline-flex w-full items-center justify-center rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-dark">
                                    {{ __('app.applications.grant') }}
                                </button>
                                <button type="submit" name="decision" value="return" class="inline-flex w-full items-center justify-center rounded-xl bg-amber-50 px-4 py-2.5 text-sm font-semibold text-amber-900 hover:bg-amber-100">
                                    {{ __('app.applications.return_to_director') }}
                                </button>
                            </div>
                        </form>
                    </section>
                @endcan

                @if ($application->isGranted() && $application->tower)
                    <section class="data-card overflow-hidden">
                        <x-section-bar :title="__('app.applications.permit_title')" />
                        <div class="p-5 sm:p-6">
                        <p class="text-sm font-medium text-gray-900">{{ $application->tower->name }}</p>
                        <div class="mt-4 flex flex-col gap-2">
                            <a href="{{ route('towers.show', $application->tower) }}" class="inline-flex w-full items-center justify-center rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-dark">
                                {{ __('app.applications.open_tower') }}
                            </a>
                            <a href="{{ route('applications.permit.print', [$application, 'copy' => 'hq']) }}" target="_blank" class="inline-flex w-full items-center justify-center rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-800 hover:bg-gray-50">
                                {{ __('app.applications.print_hq') }}
                            </a>
                            <a href="{{ route('applications.permit.print', [$application, 'copy' => 'customer']) }}" target="_blank" class="inline-flex w-full items-center justify-center rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-800 hover:bg-gray-50">
                                {{ __('app.applications.print_customer') }}
                            </a>
                        </div>
                        </div>
                    </section>
                @endif

                @can('assign', $application)
                    <section class="data-card overflow-hidden">
                        <x-section-bar :title="__('app.applications.assign_to')" :hint="__('app.applications.assign_hint')" />
                        <form method="POST" action="{{ route('applications.assign', $application) }}" class="p-5 sm:p-6 space-y-3">
                            @csrf
                            <select name="assigned_to" class="field w-full" required>
                                <option value="">{{ __('app.applications.select_assignee') }}</option>
                                @foreach ($assignees as $assignee)
                                    <option value="{{ $assignee->id }}" @selected((int) old('assigned_to', $application->assigned_to) === (int) $assignee->id)>
                                        {{ $assignee->name }} — {{ $assignee->roleLabel() }}
                                    </option>
                                @endforeach
                            </select>
                            @error('assigned_to') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                            @if ($assignees->isEmpty())
                                <p class="text-sm text-amber-800">{{ __('app.applications.no_assignees') }}</p>
                            @endif
                            <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-dark" @disabled($assignees->isEmpty())>
                                {{ __('app.applications.assign') }}
                            </button>
                        </form>
                    </section>
                @endcan
            </aside>
        </div>
    </div>

    @if ($hasPin)
        @push('head')
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        @endpush
        @push('scripts')
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
            <script>
                const lat = {{ (float) $application->latitude }};
                const lng = {{ (float) $application->longitude }};
                const color = @json($operatorColor);
                const radius = {{ (int) ($application->signal_radius_m ?: 0) }};
                const map = L.map('application-map').setView([lat, lng], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap',
                    maxZoom: 19,
                }).addTo(map);

                let coverage = null;
                if (radius > 0) {
                    coverage = L.circle([lat, lng], {
                        radius,
                        color,
                        fillColor: color,
                        fillOpacity: 0.12,
                        weight: 1.5,
                    }).addTo(map);
                }

                const icon = L.divIcon({
                    className: '',
                    html: `<span style="display:block;width:18px;height:18px;border-radius:9999px;background:${color};border:3px solid white;box-shadow:0 1px 6px rgba(0,0,0,.25)"></span>`,
                    iconSize: [18, 18],
                    iconAnchor: [9, 9],
                });
                L.marker([lat, lng], { icon }).addTo(map).bindPopup(@json($application->tower?->name ?: $application->site_name));

                function fitSite() {
                    const size = map.getSize();
                    if (size.x < 50 || size.y < 50) {
                        return false;
                    }
                    map.invalidateSize();
                    if (coverage) {
                        map.fitBounds(coverage.getBounds(), {
                            padding: [28, 28],
                            maxZoom: 15,
                            animate: false,
                        });
                    }
                    return true;
                }

                map.whenReady(() => {
                    if (! fitSite()) {
                        setTimeout(fitSite, 150);
                    }
                });
                window.addEventListener('load', () => setTimeout(fitSite, 50));
            </script>
        @endpush
    @endif
</x-app-layout>
