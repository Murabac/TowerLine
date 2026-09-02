<x-app-layout>
    <div class="p-4 lg:p-8 report-page">
        @include('reports._header')

        <div class="data-card mb-5 no-print">
            @include('reports._filters')
        </div>

        <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-3 mb-6">
            @foreach ($result->kpis as $kpi)
                <div class="data-card p-4">
                    <p class="stat-label">{{ $kpi['label'] }}</p>
                    <p class="stat-value text-2xl">{{ $kpi['value'] }}</p>
                </div>
            @endforeach
        </div>

        @foreach ($result->sections as $section)
            <section class="data-card mb-5">
                <div class="px-4 py-3 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-brand">{{ $section['title'] }}</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="data-table report-table">
                        <thead>
                            <tr>
                                @foreach ($section['columns'] as $column)
                                    <th>{{ $column['label'] }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($section['rows'] as $row)
                                <tr>
                                    @foreach ($section['columns'] as $column)
                                        <td>{{ $row[$column['key']] ?? '—' }}</td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ max(1, count($section['columns'])) }}" class="!py-10 text-center text-sm text-gray-500">{{ __('app.reports.empty') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endforeach

        <footer class="report-print-footer">{{ __('app.reports.print_footer') }}</footer>
    </div>
</x-app-layout>
