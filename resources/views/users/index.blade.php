<x-app-layout>
    <div class="p-4 lg:p-8">
        <div class="flex flex-wrap items-end justify-between gap-4 mb-5">
            <div>
                <p class="text-xs font-medium uppercase tracking-wider text-brand-muted">{{ __('app.app_short') }}</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-brand">{{ __('app.users.title') }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ __('app.users.subtitle') }}</p>
            </div>
            @can('create', App\Models\User::class)
                <a href="{{ route('users.create') }}" class="inline-flex items-center px-4 py-2.5 bg-brand text-white text-sm font-semibold rounded-xl hover:bg-brand-dark">
                    {{ __('app.users.create') }}
                </a>
            @endcan
        </div>

        <div class="data-card">
            <form method="GET" class="data-toolbar">
                <select name="role" class="field lg:w-56">
                    <option value="">{{ __('app.users.role') }}</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->key }}" @selected(request('role') === $role->key)>{{ $role->displayName() }}</option>
                    @endforeach
                </select>
                <div class="flex items-center gap-2">
                    <button class="inline-flex items-center px-4 py-2 bg-brand text-white text-sm font-semibold rounded-lg hover:bg-brand-dark">{{ __('app.filter') }}</button>
                    @if (request()->filled('role'))
                        <a href="{{ route('users.index') }}" class="text-sm text-gray-500 hover:text-brand">{{ __('app.reset') }}</a>
                    @endif
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('app.users.name') }}</th>
                            <th>{{ __('app.email') }}</th>
                            <th>{{ __('app.users.role') }}</th>
                            <th>{{ __('app.users.scope') }}</th>
                            <th class="text-right">{{ __('app.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $managed)
                            <tr>
                                <td class="font-semibold text-gray-900">{{ $managed->name }}</td>
                                <td class="text-gray-600">{{ $managed->email }}</td>
                                <td>{{ $managed->roleLabel() }}</td>
                                <td class="text-gray-600">
                                    @if ($managed->requiresRegions())
                                        {{ $managed->regions->map->localizedName()->join(', ') ?: '—' }}
                                    @elseif ($managed->isOperatorViewer())
                                        {{ $managed->operator?->name ?: '—' }}
                                    @else
                                        {{ __('app.all') }}
                                    @endif
                                </td>
                                <td class="actions">
                                    <div class="inline-flex items-center gap-0.5">
                                        @can('update', $managed)
                                            <a href="{{ route('users.edit', $managed) }}" class="icon-btn" title="{{ __('app.edit') }}">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L8.193 18.46a4.5 4.5 0 0 1-1.897 1.13L4.5 20.25l.66-1.796a4.5 4.5 0 0 1 1.13-1.897Z"/></svg>
                                            </a>
                                        @endcan
                                        @can('delete', $managed)
                                            <form method="POST" action="{{ route('users.destroy', $managed) }}" onsubmit="return confirm(@json(__('app.users.confirm_delete')))">
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
                                <td colspan="5" class="!py-16 text-center text-sm text-gray-500">{{ __('app.users.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($users->total())
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 py-3 border-t border-gray-100 bg-gray-50/50">
                    <p class="text-sm text-gray-500">{{ $users->firstItem() }}–{{ $users->lastItem() }} / {{ $users->total() }}</p>
                    {{ $users->onEachSide(1)->links('pagination.towerline') }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
