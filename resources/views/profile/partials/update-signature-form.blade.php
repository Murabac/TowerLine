<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">{{ __('app.signatures.profile_title') }}</h2>
        <p class="mt-1 text-sm text-gray-600">{{ __('app.signatures.profile_hint') }}</p>
    </header>

    @if (session('status') === __('app.signatures.saved') || session('status') === __('app.signatures.cleared'))
        <p class="mt-4 text-sm font-medium text-green-700">{{ session('status') }}</p>
    @endif

    @if ($user->hasSavedSignature())
        <div class="mt-4 rounded-lg border border-gray-200 bg-white px-3 py-2">
            <img src="{{ route('profile.signature.show') }}" alt="" class="h-20 w-auto max-w-full object-contain">
        </div>
        <form method="POST" action="{{ route('profile.signature.destroy') }}" class="mt-3">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-sm font-semibold text-red-700 hover:underline">{{ __('app.signatures.remove_saved') }}</button>
        </form>
    @endif

    <form method="POST" action="{{ route('profile.signature.store') }}" class="mt-6 space-y-4">
        @csrf
        <x-signature-pad />
        <x-primary-button>{{ __('app.signatures.save') }}</x-primary-button>
    </form>
</section>
