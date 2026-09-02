<x-app-layout>
    <div class="p-4 lg:p-8 max-w-4xl">
        <a href="{{ route('roles.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
            {{ __('app.role_admin.title') }}
        </a>

        <div class="mt-4 rounded-2xl bg-brand text-white px-5 py-5 sm:px-6">
            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-brand-light">{{ __('app.role_admin.title') }}</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight">{{ $managedRole->displayName() }}</h1>
            @if ($managedRole->isSystem())
                <p class="mt-2 text-sm text-white/75">{{ __('app.role_admin.system_locked') }}</p>
            @endif
        </div>

        <form method="POST" action="{{ route('roles.update', $managedRole) }}" class="mt-5 space-y-5">
            @csrf
            @method('PUT')
            @include('roles._form')
            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <a href="{{ route('roles.index') }}" class="inline-flex items-center justify-center px-4 py-3 text-sm font-semibold text-gray-600">{{ __('app.cancel') }}</a>
                <button class="inline-flex items-center justify-center px-5 py-3 bg-brand text-white text-sm font-semibold rounded-2xl hover:bg-brand-dark shadow-sm">{{ __('app.save') }}</button>
            </div>
        </form>
    </div>
</x-app-layout>
