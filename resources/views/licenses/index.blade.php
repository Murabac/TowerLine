<x-app-layout>
    <div class="p-4 lg:p-8">
        <div class="flex flex-wrap items-end justify-between gap-4 mb-5">
            <div>
                <p class="text-xs font-medium uppercase tracking-wider text-brand-muted">{{ __('app.app_short') }}</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-brand">{{ __('app.licenses.title') }}</h1>
            </div>
            @can('create', App\Models\License::class)
                <a href="{{ route('licenses.create') }}" class="inline-flex items-center px-4 py-2.5 bg-brand text-white text-sm font-semibold rounded-xl hover:bg-brand-dark">
                    {{ __('app.licenses.create') }}
                </a>
            @endcan
        </div>

        <div class="data-card">
            <form method="GET" class="data-toolbar">
                <select name="license_type" class="field lg:w-36">
                    <option value="">{{ __('app.licenses.type') }}</option>
                    @foreach (['A', 'B', 'C'] as $type)
                        <option value="{{ $type }}" @selected(request('license_type') === $type)>{{ $type }}</option>
                    @endforeach
                </select>
                <select name="state" class="field lg:w-44">
                    <option value="">{{ __('app.licenses.state') }}</option>
                    @foreach (['active', 'expiring_soon', 'expired'] as $state)
                        <option value="{{ $state }}" @selected(request('state') === $state)>{{ __('app.status.'.$state) }}</option>
                    @endforeach
                </select>
                <div class="flex items-center gap-2">
                    <button class="inline-flex items-center px-4 py-2 bg-brand text-white text-sm font-semibold rounded-lg hover:bg-brand-dark">{{ __('app.filter') }}</button>
                    @if (request()->hasAny(['license_type', 'state']))
                        <a href="{{ route('licenses.index') }}" class="text-sm text-gray-500 hover:text-brand">{{ __('app.reset') }}</a>
                    @endif
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('app.licenses.tower') }}</th>
                            <th>{{ __('app.licenses.operator') }}</th>
                            <th>{{ __('app.licenses.type') }}</th>
                            <th>{{ __('app.licenses.issued') }}</th>
                            <th>{{ __('app.licenses.expires') }}</th>
                            <th>{{ __('app.licenses.state') }}</th>
                            <th>{{ __('app.licenses.documents') }}</th>
                            <th class="text-right">{{ __('app.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($licenses as $license)
                            <tr>
                                <td>
                                    <a href="{{ route('towers.show', $license->tower) }}" class="font-semibold text-gray-900 hover:text-brand">{{ $license->tower->name }}</a>
                                    <p class="text-xs text-gray-400">{{ $license->tower->region->localizedName() }}</p>
                                </td>
                                <td>{{ $license->operator->name }}</td>
                                <td><x-status-badge :value="$license->license_type" /></td>
                                <td class="whitespace-nowrap">{{ $license->issued_at->format('d M Y') }}</td>
                                <td class="whitespace-nowrap">{{ $license->expires_at->format('d M Y') }}</td>
                                <td><x-status-badge :value="$license->display_status" /></td>
                                <td>
                                    @if ($license->documentList())
                                        <div class="flex flex-col gap-1">
                                            @foreach ($license->documentList() as $document)
                                                <a href="{{ $document['url'] }}" target="_blank" rel="noopener" class="truncate text-sm font-medium text-brand hover:underline">{{ $document['name'] }}</a>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="actions">
                                    <div class="inline-flex items-center gap-0.5">
                                        @can('update', $license)
                                            <a href="{{ route('licenses.edit', $license) }}" class="icon-btn" title="{{ __('app.edit') }}">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L8.193 18.46a4.5 4.5 0 0 1-1.897 1.13L4.5 20.25l.66-1.796a4.5 4.5 0 0 1 1.13-1.897Z"/></svg>
                                            </a>
                                        @endcan
                                        @can('delete', $license)
                                            <form method="POST" action="{{ route('licenses.destroy', $license) }}" onsubmit="return confirm(@json(__('app.licenses.confirm_delete')))">
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
                                <td colspan="8" class="!py-16 text-center text-sm text-gray-500">{{ __('app.licenses.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($licenses->total())
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 py-3 border-t border-gray-100 bg-gray-50/50">
                    <p class="text-sm text-gray-500">{{ $licenses->firstItem() }}–{{ $licenses->lastItem() }} / {{ $licenses->total() }}</p>
                    {{ $licenses->onEachSide(1)->links('pagination.towerline') }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
