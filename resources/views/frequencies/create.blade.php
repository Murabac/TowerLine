<x-app-layout>
    <div class="p-4 lg:p-8 max-w-3xl space-y-5">
        @include('frequencies._header', [
            'current' => 'create',
            'title' => __('app.frequencies.create'),
            'subtitle' => __('app.frequencies.nav.create_desc'),
        ])

        <form method="POST" action="{{ route('frequencies.store') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf
            <section class="data-card p-5 sm:p-6">
                @include('frequencies._fields')
            </section>
            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <a href="{{ route('frequencies.registry') }}" class="inline-flex items-center justify-center px-4 py-3 text-sm font-semibold text-gray-600">{{ __('app.cancel') }}</a>
                <button class="inline-flex items-center justify-center px-5 py-3 bg-brand text-white text-sm font-semibold rounded-2xl hover:bg-brand-dark shadow-sm">{{ __('app.save') }}</button>
            </div>
        </form>
    </div>
</x-app-layout>
