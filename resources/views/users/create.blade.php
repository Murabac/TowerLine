<x-app-layout>
    <div class="p-4 lg:p-8 max-w-4xl">
        <a href="{{ route('users.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
            {{ __('app.users.title') }}
        </a>
        <h1 class="mt-4 text-2xl font-semibold tracking-tight text-brand">{{ __('app.users.create') }}</h1>

        <form method="POST" action="{{ route('users.store') }}" class="mt-5 space-y-5">
            @csrf
            <section class="data-card p-5 sm:p-6">
                @include('users._form')
            </section>
            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <a href="{{ route('users.index') }}" class="inline-flex items-center justify-center px-4 py-3 text-sm font-semibold text-gray-600">{{ __('app.cancel') }}</a>
                <button class="inline-flex items-center justify-center px-5 py-3 bg-brand text-white text-sm font-semibold rounded-2xl hover:bg-brand-dark shadow-sm">{{ __('app.save') }}</button>
            </div>
        </form>
    </div>
</x-app-layout>
