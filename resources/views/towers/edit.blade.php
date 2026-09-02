<x-app-layout>
    @php
        $isCorrection = isset($pendingApproval);
        $formAction = $formAction ?? route('towers.update', $tower);
        $submitLabel = $submitLabel ?? __('app.save');
        $cancelUrl = $cancelUrl ?? ($tower->exists ? route('towers.show', $tower) : route('towers.index'));
    @endphp
    <div class="p-4 lg:p-6 max-w-6xl">
        <a href="{{ $cancelUrl }}" class="text-sm text-brand hover:underline">{{ __('app.back') }}</a>
        <h1 class="mt-2 text-2xl font-semibold text-brand">
            @if ($isCorrection)
                {{ __('app.approvals.correct_this') }}
                @if ($tower->name)
                    — {{ $tower->name }}
                @endif
            @else
                {{ __('app.edit') }} — {{ $tower->name }}
            @endif
        </h1>
        @if ($isCorrection)
            <p class="mt-2 text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3">{{ __('app.approvals.correct_hint') }}</p>
        @endif

        <form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" class="mt-6 bg-white border border-gray-200 rounded-lg p-4 lg:p-6">
            @csrf
            @method('PUT')
            @include('towers._form', ['tower' => $tower])
            <div class="mt-6 flex gap-3">
                <x-primary-button>{{ $submitLabel }}</x-primary-button>
                <a href="{{ $cancelUrl }}" class="inline-flex items-center text-sm text-gray-600 hover:underline">{{ __('app.cancel') }}</a>
            </div>
        </form>
    </div>
    @include('towers._picker-script', ['previewTowerId' => $tower->exists ? $tower->id : null])
</x-app-layout>
