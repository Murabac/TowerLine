<x-app-layout>
    <div class="p-4 lg:p-8 max-w-3xl">
        <a href="{{ route('licenses.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
            {{ __('app.licenses.title') }}
        </a>

        <div class="mt-4 rounded-2xl bg-brand text-white px-5 py-5 sm:px-6">
            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-brand-light">{{ __('app.licenses.title') }}</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight">{{ __('app.licenses.edit') }}</h1>
            <p class="mt-2 text-sm text-white/75 max-w-xl">{{ __('app.licenses.edit_subtitle') }}</p>
            <div class="mt-4 flex flex-wrap items-center gap-2 text-xs">
                <a href="{{ route('towers.show', $license->tower) }}" class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-2.5 py-1 hover:bg-white/15">
                    <span class="h-2 w-2 rounded-full" style="background: {{ $license->tower->operator->color }}"></span>
                    {{ $license->tower->name }}
                </a>
                <span class="rounded-full bg-white/10 px-2.5 py-1">{{ $license->operator->name }}</span>
                <span class="rounded-full bg-white/10 px-2.5 py-1">{{ $license->tower->region->localizedName() }}</span>
                <span class="rounded-full bg-white px-2.5 py-1 font-semibold text-brand">{{ __('app.status.'.$license->display_status) }}</span>
            </div>
        </div>

        <form method="POST" action="{{ route('licenses.update', $license) }}" enctype="multipart/form-data" class="mt-5 space-y-5">
            @csrf
            @method('PUT')

            <section class="data-card p-5 sm:p-6">
                @include('licenses._fields')
            </section>

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <a href="{{ route('licenses.index') }}" class="inline-flex items-center justify-center px-4 py-3 text-sm font-semibold text-gray-600">{{ __('app.cancel') }}</a>
                <button class="inline-flex items-center justify-center px-5 py-3 bg-brand text-white text-sm font-semibold rounded-2xl hover:bg-brand-dark shadow-sm">{{ __('app.save') }}</button>
            </div>
        </form>
    </div>
</x-app-layout>
