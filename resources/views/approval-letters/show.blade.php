<x-app-layout>
    <div class="p-4 lg:p-8 max-w-4xl">
        <a href="{{ route('towers.show', $tower) }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
            {{ $tower->name }}
        </a>

        <div class="mt-4 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-brand">{{ __('app.approval_letters.title') }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ __('app.approval_letters.subtitle') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($letter)
                    <a href="{{ route('towers.approval-letter.print', $tower) }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand text-white text-sm font-semibold rounded-xl hover:bg-brand-dark">
                        {{ __('app.approval_letters.print') }}
                    </a>
                @endif
                @can('create', [App\Models\BuildApprovalLetter::class, $tower])
                    <form method="POST" action="{{ route('towers.approval-letter.store', $tower) }}" class="inline-flex flex-wrap items-center gap-2">
                        @csrf
                        <select name="status" class="rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
                            <option value="approved">{{ __('app.approval_letters.status_approved') }}</option>
                            <option value="denied">{{ __('app.approval_letters.status_denied') }}</option>
                        </select>
                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 text-sm font-semibold rounded-xl hover:border-brand hover:text-brand">
                            {{ $letter ? __('app.approval_letters.regenerate') : __('app.approval_letters.generate') }}
                        </button>
                    </form>
                @endcan
            </div>
        </div>

        @if (session('status'))
            <p class="mt-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</p>
        @endif

        @if ($letter)
            <link rel="stylesheet" href="{{ asset('css/approval-letter.css') }}">
            <div class="mt-6 data-card p-0 overflow-hidden bg-white">
                <div class="p-4 sm:p-6 overflow-x-auto">
                    @include('approval-letters._document', ['letter' => $letter])
                </div>
            </div>
            <p class="mt-3 text-xs text-gray-500">
                {{ __('app.approval_letters.meta', [
                    'ref' => $letter->reference_number,
                    'issuer' => $letter->issuer?->name ?: __('app.approval_letters.system'),
                    'date' => $letter->created_at->format('d M Y H:i'),
                ]) }}
            </p>
        @else
            <div class="mt-6 data-card p-8 text-center">
                <p class="text-sm text-gray-600">{{ __('app.approval_letters.none_yet') }}</p>
                @can('create', [App\Models\BuildApprovalLetter::class, $tower])
                    <form method="POST" action="{{ route('towers.approval-letter.store', $tower) }}" class="mt-4">
                        @csrf
                        <input type="hidden" name="status" value="approved">
                        <button type="submit" class="inline-flex items-center gap-2 px-5 py-3 bg-brand text-white text-sm font-semibold rounded-2xl hover:bg-brand-dark">
                            {{ __('app.approval_letters.generate') }}
                        </button>
                    </form>
                @endcan
            </div>
        @endif
    </div>
</x-app-layout>
