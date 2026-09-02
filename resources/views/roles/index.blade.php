<x-app-layout>
    <div class="p-4 lg:p-8">
        <div class="flex flex-wrap items-end justify-between gap-4 mb-5">
            <div>
                <p class="text-xs font-medium uppercase tracking-wider text-brand-muted">{{ __('app.app_short') }}</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-brand">{{ __('app.role_admin.title') }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ __('app.role_admin.subtitle') }}</p>
            </div>
            @can('create', App\Models\Role::class)
                <a href="{{ route('roles.create') }}" class="inline-flex items-center px-4 py-2.5 bg-brand text-white text-sm font-semibold rounded-xl hover:bg-brand-dark">
                    {{ __('app.role_admin.create') }}
                </a>
            @endcan
        </div>

        <div class="data-card">
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('app.role_admin.name') }}</th>
                            <th>{{ __('app.role_admin.scope') }}</th>
                            <th>{{ __('app.role_admin.tasks') }}</th>
                            <th>{{ __('app.role_admin.users') }}</th>
                            <th class="text-right">{{ __('app.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($roles as $role)
                            <tr>
                                <td>
                                    <p class="font-semibold text-gray-900">{{ $role->displayName() }}</p>
                                    @if ($role->isSystem())
                                        <p class="text-xs text-gray-400">{{ __('app.role_admin.system') }}</p>
                                    @endif
                                </td>
                                <td class="text-gray-600">
                                    {{ $role->requires_regions ? __('app.role_admin.regional') : __('app.role_admin.ministry_wide') }}
                                </td>
                                <td class="text-gray-600">{{ $role->taskCount() }}</td>
                                <td class="text-gray-600">{{ $role->users_count }}</td>
                                <td class="actions">
                                    <div class="inline-flex items-center gap-0.5">
                                        @can('update', $role)
                                            <a href="{{ route('roles.edit', $role) }}" class="icon-btn" title="{{ __('app.edit') }}">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L8.193 18.46a4.5 4.5 0 0 1-1.897 1.13L4.5 20.25l.66-1.796a4.5 4.5 0 0 1 1.13-1.897Z"/></svg>
                                            </a>
                                        @endcan
                                        @can('delete', $role)
                                            <form method="POST" action="{{ route('roles.destroy', $role) }}" onsubmit="return confirm(@json(__('app.role_admin.confirm_delete')))">
                                                @csrf
                                                @method('DELETE')
                                                <button class="icon-btn text-red-600 hover:bg-red-50" title="{{ __('app.delete') }}">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5h6v2m-7 4v7m4-7v7m4-7v7M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2l1-12"/></svg>
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="!py-16 text-center text-sm text-gray-500">{{ __('app.role_admin.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
