<x-app-layout>
    <div class="p-4 lg:p-8 report-page">
        @include('reports._header')

        <div class="data-card">
            @include('reports._filters')

            <div class="overflow-x-auto">
                <table class="data-table report-table">
                    <thead>
                        <tr>
                            @foreach ($result->columns as $column)
                                <th>
                                    @if ($column['key'] === 'no')
                                        {{ $column['label'] }}
                                    @else
                                        <a href="{{ $sortUrl($column['key']) }}" class="inline-flex items-center gap-1 hover:text-brand">
                                            {{ $column['label'] }}
                                            @if (($filters['sort'] ?? '') === $column['key'])
                                                <span class="text-[10px]">{{ ($filters['dir'] ?? 'asc') === 'desc' ? '↓' : '↑' }}</span>
                                            @endif
                                        </a>
                                    @endif
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($result->rows as $row)
                            <tr>
                                @foreach ($result->columns as $column)
                                    <td>{{ $row[$column['key']] ?? '—' }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ max(1, count($result->columns)) }}" class="!py-16 text-center text-sm text-gray-500">{{ __('app.reports.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($result->rows)
                <p class="px-4 py-3 text-xs text-gray-400 border-t border-gray-100">{{ __('app.reports.row_count', ['count' => count($result->rows)]) }}</p>
            @endif
        </div>

        <footer class="report-print-footer">{{ __('app.reports.print_footer') }}</footer>
    </div>
</x-app-layout>
