<x-app-layout>
    <div class="p-4 lg:p-8 max-w-3xl space-y-5">
        <a href="{{ route('frequencies.show', $allocation) }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
            {{ $allocation->operator->name }} · {{ $allocation->band_label }}
        </a>

        @include('frequencies._header', [
            'current' => 'registry',
            'title' => __('app.frequencies.edit'),
        ])

        <form method="POST" action="{{ route('frequencies.update', $allocation) }}" enctype="multipart/form-data" class="space-y-5">
            @csrf
            @method('PUT')
            <section class="data-card p-5 sm:p-6">
                @include('frequencies._fields', ['allocation' => $allocation])
            </section>
            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <a href="{{ route('frequencies.show', $allocation) }}" class="inline-flex items-center justify-center px-4 py-3 text-sm font-semibold text-gray-600">{{ __('app.cancel') }}</a>
                <button class="inline-flex items-center justify-center px-5 py-3 bg-brand text-white text-sm font-semibold rounded-2xl hover:bg-brand-dark shadow-sm">{{ __('app.save') }}</button>
            </div>
        </form>
    </div>
</x-app-layout>
