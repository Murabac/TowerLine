<x-app-layout>
    <div class="p-4 lg:p-6 max-w-6xl">
        <a href="{{ route('towers.index') }}" class="text-sm text-brand hover:underline">{{ __('app.back') }}</a>
        <h1 class="mt-2 text-2xl font-semibold text-brand">{{ __('app.towers.create') }}</h1>

        <form method="POST" action="{{ route('towers.store') }}" enctype="multipart/form-data" class="mt-6 bg-white border border-gray-200 rounded-lg p-4 lg:p-6">
            @csrf
            @include('towers._form')
            <div class="mt-6 flex gap-3">
                <x-primary-button>{{ __('app.save') }}</x-primary-button>
                <a href="{{ route('towers.index') }}" class="inline-flex items-center text-sm text-gray-600 hover:underline">{{ __('app.cancel') }}</a>
            </div>
        </form>
    </div>
    @include('towers._picker-script', ['previewTowerId' => null])
</x-app-layout>
