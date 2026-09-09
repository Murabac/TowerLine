<x-guidelines-layout>
    <section class="overflow-hidden rounded-3xl bg-gradient-to-br from-[#1B4D3E] via-[#246352] to-[#14382C] px-6 py-10 text-white shadow-[0_12px_40px_rgba(20,56,44,0.18)] sm:px-10">
        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-[#F5C518]">{{ __('app.apply.received_title', [], 'en') }}</p>
        <h1 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">{{ __('app.apply.received_title', [], 'en') }}</h1>
        <p class="mt-4 max-w-2xl text-sm leading-7 text-white/80">{{ __('app.apply.received_lead', [], 'en') }}</p>
    </section>

    <section class="mt-10 overflow-hidden rounded-3xl border border-gray-200/80 bg-white p-8 text-center shadow-[0_1px_2px_rgba(16,24,40,0.04)]">
        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-gray-400">{{ __('app.apply.tracking', [], 'en') }}</p>
        <p class="mt-3 font-mono text-2xl font-semibold tracking-wide text-brand sm:text-3xl">{{ $application->reference_number }}</p>
        <dl class="mx-auto mt-8 grid max-w-lg gap-3 text-left text-sm sm:grid-cols-2">
            <div>
                <dt class="text-gray-400">{{ __('app.apply.site_name', [], 'en') }}</dt>
                <dd class="mt-0.5 font-medium text-gray-900">{{ $application->site_name }}</dd>
            </div>
            <div>
                <dt class="text-gray-400">{{ __('app.apply.operator', [], 'en') }}</dt>
                <dd class="mt-0.5 font-medium text-gray-900">{{ $application->operator->name }}</dd>
            </div>
            <div>
                <dt class="text-gray-400">{{ __('app.apply.submitted', [], 'en') }}</dt>
                <dd class="mt-0.5 font-medium text-gray-900">{{ $application->created_at->timezone(config('app.timezone'))->format('d M Y H:i') }}</dd>
            </div>
            <div>
                <dt class="text-gray-400">{{ __('app.apply.region', [], 'en') }}</dt>
                <dd class="mt-0.5 font-medium text-gray-900">{{ $application->region->name_en }}</dd>
            </div>
        </dl>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ route('apply.create') }}" class="inline-flex rounded-xl bg-brand px-5 py-3 text-sm font-semibold text-white hover:bg-brand-dark">{{ __('app.apply.another', [], 'en') }}</a>
            <a href="{{ route('guidelines') }}" class="inline-flex rounded-xl bg-brand/10 px-5 py-3 text-sm font-medium text-brand hover:bg-brand/15">{{ __('app.guidelines.window_label', [], 'en') }}</a>
        </div>
    </section>
</x-guidelines-layout>
