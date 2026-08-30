<x-app-layout>
    <div class="p-4 lg:p-8 space-y-5">
        @include('frequencies._header', [
            'current' => 'dashboard',
            'subtitle' => __('app.frequencies.dashboard_subtitle'),
        ])

        <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-3">
            <a href="{{ route('frequencies.registry') }}" class="data-card p-4 hover:border-brand">
                <p class="stat-label">{{ __('app.frequencies.stats.total') }}</p>
                <p class="stat-value text-xl">{{ number_format($totalCount) }}</p>
            </a>
            <a href="{{ route('frequencies.registry', ['state' => 'active']) }}" class="data-card p-4 hover:border-brand">
                <p class="stat-label">{{ __('app.frequencies.stats.active') }}</p>
                <p class="stat-value text-xl text-health-good">{{ number_format($activeCount) }}</p>
            </a>
            <a href="{{ route('frequencies.registry', ['state' => 'expiring_soon']) }}" class="data-card p-4 hover:border-health-attention">
                <p class="stat-label">{{ __('app.frequencies.stats.expiring') }}</p>
                <p class="stat-value text-xl text-health-attention">{{ number_format($expiringCount) }}</p>
            </a>
            <a href="{{ route('frequencies.registry', ['state' => 'expired']) }}" class="data-card p-4 hover:border-health-critical">
                <p class="stat-label">{{ __('app.frequencies.stats.expired') }}</p>
                <p class="stat-value text-xl text-health-critical">{{ number_format($expiredCount) }}</p>
            </a>
        </div>

        <section class="data-card overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/60">
                <h2 class="text-sm font-semibold text-brand">{{ __('app.frequencies.revenue.section_title') }}</h2>
                <p class="mt-0.5 text-xs text-gray-500">{{ __('app.frequencies.revenue.section_subtitle', ['fee' => number_format($renewalFee)]) }}</p>
            </div>

            <div class="p-5 space-y-5">
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white divide-y md:divide-y-0 md:divide-x md:grid md:grid-cols-3">
                    <a href="{{ route('frequencies.registry', ['payment' => 'with_receipt']) }}" class="flex items-center justify-between gap-4 px-5 py-4 bg-brand/[0.04] hover:bg-brand/[0.07] transition">
                        <div class="min-w-0">
                            <p class="stat-label">{{ __('app.frequencies.revenue.total_collected') }}</p>
                            <p class="mt-1 text-xs font-medium text-gray-500">{{ __('app.frequencies.revenue.currency') }} · {{ __('app.frequencies.revenue.paid_renewals', ['count' => number_format($totalReceipts)]) }}</p>
                        </div>
                        <p class="shrink-0 text-xl sm:text-2xl font-bold tracking-tight text-brand">{{ number_format($totalRevenue) }}</p>
                    </a>
                    <a href="{{ route('frequencies.registry', ['state' => 'outstanding']) }}" class="flex items-center justify-between gap-4 px-5 py-4 bg-health-critical/[0.04] hover:bg-health-critical/[0.07] transition">
                        <div class="min-w-0">
                            <p class="stat-label">{{ __('app.frequencies.revenue.due') }}</p>
                            <p class="mt-1 text-xs font-medium text-gray-500">{{ __('app.frequencies.revenue.currency') }} · {{ __('app.frequencies.revenue.due_hint', ['count' => number_format($dueCount)]) }}</p>
                        </div>
                        <p class="shrink-0 text-xl sm:text-2xl font-bold tracking-tight text-health-critical">{{ number_format($dueRevenue) }}</p>
                    </a>
                    <a href="{{ route('frequencies.registry', ['payment' => 'this_year']) }}" class="flex items-center justify-between gap-4 px-5 py-4 bg-gray-50/80 hover:bg-gray-100/80 transition">
                        <div class="min-w-0">
                            <p class="stat-label">{{ __('app.frequencies.revenue.this_year') }}</p>
                            <p class="mt-1 text-xs font-medium text-gray-500">{{ __('app.frequencies.revenue.currency') }} · {{ __('app.frequencies.revenue.paid_renewals', ['count' => number_format($yearReceipts)]) }}</p>
                        </div>
                        <p class="shrink-0 text-xl sm:text-2xl font-bold tracking-tight text-gray-900">{{ number_format($yearRevenue) }}</p>
                    </a>
                </div>

                @if ($totalRevenue > 0 || $dueRevenue > 0 || $totalReceipts > 0)
                    <p class="text-xs text-gray-500">{{ __('app.frequencies.revenue.chart_click_hint') }}</p>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="rounded-xl border border-gray-100 bg-white p-4">
                            <div class="mb-3">
                                <h3 class="text-sm font-semibold text-gray-900">{{ __('app.frequencies.revenue.comparison_title') }}</h3>
                                <p class="text-xs text-gray-500">{{ __('app.frequencies.revenue.comparison_collected') }} / {{ __('app.frequencies.revenue.comparison_due') }}</p>
                            </div>
                            <div class="relative mx-auto h-56 max-w-xs cursor-pointer">
                                <canvas id="frequency-finance-pie" aria-label="{{ __('app.frequencies.revenue.comparison_title') }}"></canvas>
                            </div>
                        </div>
                        <div class="rounded-xl border border-gray-100 bg-white p-4">
                            <div class="mb-3">
                                <h3 class="text-sm font-semibold text-gray-900">{{ __('app.frequencies.revenue.chart_title') }}</h3>
                                <p class="text-xs text-gray-500">{{ __('app.frequencies.revenue.chart_subtitle') }}</p>
                            </div>
                            <div class="relative h-56 cursor-pointer">
                                <canvas id="frequency-finance-line" aria-label="{{ __('app.frequencies.revenue.chart_title') }}"></canvas>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="rounded-xl border border-dashed border-gray-200 bg-gray-50/80 px-6 py-10 text-center">
                        <p class="text-sm text-gray-500">{{ __('app.frequencies.revenue.no_financial_data') }}</p>
                    </div>
                @endif
            </div>
        </section>

        <div class="grid lg:grid-cols-2 gap-5">
            <section class="data-card">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-brand">{{ __('app.frequencies.expiring_list') }}</h2>
                    <a href="{{ route('frequencies.registry', ['state' => 'expiring_soon']) }}" class="text-sm font-medium text-brand hover:underline">{{ __('app.frequencies.view_all') }}</a>
                </div>
                <ul class="divide-y divide-gray-100">
                    @forelse ($expiringSoon as $allocation)
                        <li>
                            <a href="{{ route('frequencies.show', $allocation) }}" class="flex items-start justify-between gap-3 px-5 py-3 hover:bg-gray-50">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-900 truncate">{{ $allocation->operator->name }} · {{ $allocation->band_label }}</p>
                                    <p class="text-xs text-gray-400 truncate">{{ $allocation->coverageLabel() }} · {{ $allocation->frequency_range }}</p>
                                </div>
                                <div class="text-right shrink-0">
                                    <x-status-badge :value="$allocation->display_status" />
                                    <p class="mt-1 text-xs text-gray-400">{{ $allocation->expires_at->format('d M Y') }}</p>
                                </div>
                            </a>
                        </li>
                    @empty
                        <li class="px-5 py-10 text-center text-sm text-gray-500">{{ __('app.frequencies.no_expiring') }}</li>
                    @endforelse
                </ul>
            </section>

            <section class="data-card">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-brand">{{ __('app.frequencies.by_operator') }}</h2>
                </div>
                <ul class="divide-y divide-gray-100">
                    @forelse ($byOperator as $row)
                        <li>
                            <a href="{{ route('frequencies.registry', ['operator_id' => $row['operator']->id]) }}" class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-gray-50">
                                <span class="text-sm font-semibold text-gray-900">{{ $row['operator']->name }}</span>
                                <span class="text-sm font-medium text-brand">{{ $row['total'] }}</span>
                            </a>
                        </li>
                    @empty
                        <li class="px-5 py-10 text-center text-sm text-gray-500">{{ __('app.frequencies.empty') }}</li>
                    @endforelse
                </ul>
            </section>
        </div>

        <section class="data-card">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-brand">{{ __('app.frequencies.recent_renewals') }}</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="data-table min-w-0">
                    <thead>
                        <tr>
                            <th>{{ __('app.frequencies.operator') }}</th>
                            <th>{{ __('app.frequencies.band') }}</th>
                            <th>{{ __('app.frequencies.expires') }}</th>
                            <th>{{ __('app.frequency_letters.title_short') }}</th>
                            <th>{{ __('app.frequency_receipts.title_short') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentRenewals as $allocation)
                            <tr>
                                <td>
                                    <a href="{{ route('frequencies.show', $allocation) }}" class="font-semibold text-brand hover:underline">{{ $allocation->operator->name }}</a>
                                </td>
                                <td>{{ $allocation->band_label }}</td>
                                <td class="whitespace-nowrap">{{ $allocation->expires_at->format('d M Y') }}</td>
                                <td>
                                    @if ($allocation->currentLetter)
                                        <a href="{{ route('frequencies.letter.show', $allocation) }}" class="text-sm text-brand hover:underline">{{ $allocation->currentLetter->reference_number }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if ($allocation->currentReceipt)
                                        <a href="{{ route('frequencies.receipt.show', $allocation) }}" class="text-sm text-brand hover:underline">{{ $allocation->currentReceipt->reference_number }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="!py-12 text-center text-sm text-gray-500">{{ __('app.frequencies.no_renewals') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    @if ($totalRevenue > 0 || $dueRevenue > 0 || $totalReceipts > 0)
        @push('scripts')
            <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
            <script>
                const financeCurrency = @json(__('app.frequencies.revenue.currency'));
                const financeCollected = @json($totalRevenue);
                const financeDue = @json($dueRevenue);
                const financeMonthly = @json($monthlyRevenue);
                const financeLabels = {
                    collected: @json(__('app.frequencies.revenue.comparison_collected')),
                    due: @json(__('app.frequencies.revenue.comparison_due')),
                };
                const financeUrls = {
                    collected: @json(route('frequencies.registry', ['payment' => 'with_receipt'])),
                    due: @json(route('frequencies.registry', ['state' => 'outstanding'])),
                    registry: @json(route('frequencies.registry')),
                };

                const chartPointer = (event, elements) => {
                    event.native.target.style.cursor = elements.length > 0 ? 'pointer' : 'default';
                };

                const pieCanvas = document.getElementById('frequency-finance-pie');
                if (pieCanvas && (financeCollected > 0 || financeDue > 0)) {
                    new Chart(pieCanvas, {
                        type: 'doughnut',
                        data: {
                            labels: [financeLabels.collected, financeLabels.due],
                            datasets: [{
                                data: [financeCollected, financeDue],
                                backgroundColor: ['#1b4d3e', '#dc2626'],
                                hoverBackgroundColor: ['#14382c', '#b91c1c'],
                                borderWidth: 2,
                                borderColor: '#ffffff',
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '62%',
                            onHover: chartPointer,
                            onClick: (_event, elements) => {
                                if (! elements.length) {
                                    return;
                                }

                                window.location.href = elements[0].index === 0
                                    ? financeUrls.collected
                                    : financeUrls.due;
                            },
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        boxWidth: 12,
                                        boxHeight: 12,
                                        padding: 16,
                                        font: { size: 12, weight: '600' },
                                    },
                                },
                                tooltip: {
                                    callbacks: {
                                        label: (context) => `${context.label}: ${Number(context.raw).toLocaleString()} ${financeCurrency}`,
                                    },
                                },
                            },
                        },
                    });
                }

                const lineCanvas = document.getElementById('frequency-finance-line');
                if (lineCanvas) {
                    const lineContext = lineCanvas.getContext('2d');
                    const lineGradient = lineContext.createLinearGradient(0, 0, 0, lineCanvas.height || 220);
                    lineGradient.addColorStop(0, 'rgba(27, 77, 62, 0.28)');
                    lineGradient.addColorStop(1, 'rgba(27, 77, 62, 0.02)');

                    new Chart(lineCanvas, {
                        type: 'line',
                        data: {
                            labels: financeMonthly.map((row) => row.label),
                            datasets: [{
                                label: financeCurrency,
                                data: financeMonthly.map((row) => row.revenue),
                                borderColor: '#1b4d3e',
                                backgroundColor: lineGradient,
                                borderWidth: 2.5,
                                fill: true,
                                tension: 0.35,
                                pointBackgroundColor: financeMonthly.map((row) => row.count > 0 ? '#1b4d3e' : '#94a3b8'),
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 2,
                                pointRadius: financeMonthly.map((row) => row.count > 0 ? 6 : 4),
                                pointHoverRadius: 8,
                                pointHitRadius: 18,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                mode: 'index',
                                intersect: false,
                            },
                            onHover: chartPointer,
                            onClick: (event, _elements, chart) => {
                                const points = chart.getElementsAtEventForMode(event, 'index', { intersect: false }, false);
                                if (! points.length) {
                                    return;
                                }

                                const row = financeMonthly[points[0].index];
                                if (row?.url) {
                                    window.location.href = row.url;
                                }
                            },
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        title: (items) => financeMonthly[items[0].dataIndex]?.fullLabel ?? items[0].label,
                                        label: (item) => {
                                            const row = financeMonthly[item.dataIndex];
                                            const amount = `${Number(item.raw).toLocaleString()} ${financeCurrency}`;
                                            if (! row?.count) {
                                                return `${amount} · 0 renewals`;
                                            }

                                            return `${amount} · ${row.count} ${row.count === 1 ? 'renewal' : 'renewals'}`;
                                        },
                                        afterLabel: (item) => {
                                            const row = financeMonthly[item.dataIndex];
                                            return row?.count ? 'Click to view receipts' : 'Click to view month';
                                        },
                                    },
                                },
                            },
                            scales: {
                                x: {
                                    grid: { display: false },
                                    ticks: { font: { size: 11 } },
                                },
                                y: {
                                    beginAtZero: true,
                                    grid: { color: 'rgba(148, 163, 184, 0.2)' },
                                    ticks: {
                                        font: { size: 11 },
                                        callback: (value) => {
                                            if (value >= 1_000_000) {
                                                return `${(value / 1_000_000).toFixed(value % 1_000_000 === 0 ? 0 : 1)}M`;
                                            }

                                            return Number(value).toLocaleString();
                                        },
                                    },
                                },
                            },
                        },
                    });
                }
            </script>
        @endpush
    @endif
</x-app-layout>
