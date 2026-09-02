<x-app-layout>
    <div class="p-4 lg:p-8 space-y-5">
        <div>
            <p class="text-xs font-medium uppercase tracking-wider text-brand-muted">{{ __('app.app_short') }}</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-brand">{{ __('app.dashboard.title') }}</h1>
        </div>

        @if (! empty($pendingApprovalCount))
            <a href="{{ route('approvals.index') }}" class="block rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                {{ __($pendingApprovalIsOwn ? 'app.dashboard.pending_own_submissions_banner' : 'app.dashboard.pending_approvals_banner', ['count' => $pendingApprovalCount]) }}
            </a>
        @endif

        @if ($frequencyExpiringCount)
            <a href="{{ route('frequencies.dashboard') }}" class="block rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                {{ __('app.dashboard.frequency_renewal_banner', ['count' => $frequencyExpiringCount]) }}
            </a>
        @endif

        @if ($expiringCount)
            <a href="{{ route('licenses.index', ['state' => 'expiring_soon']) }}" class="block rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                {{ __('app.dashboard.renewal_banner', ['count' => $expiringCount]) }}
            </a>
        @endif

        <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <a href="{{ route('towers.index') }}" class="data-card p-5 hover:border-brand">
                <p class="stat-label">{{ __('app.dashboard.total_towers') }}</p>
                <p class="stat-value text-2xl">{{ number_format($towerCount) }}</p>
            </a>
            <a href="{{ route('map', ['health_status' => 'critical']) }}" class="data-card p-5 hover:border-health-critical">
                <p class="stat-label">{{ __('app.dashboard.critical') }}</p>
                <p class="stat-value text-2xl text-health-critical">{{ number_format($criticalCount) }}</p>
            </a>
            <a href="{{ route('map', ['overdue' => 1]) }}" class="data-card p-5 hover:border-health-attention">
                <p class="stat-label">{{ __('app.dashboard.needing_inspection') }}</p>
                <p class="stat-value text-2xl text-health-attention">{{ number_format($overdueCount) }}</p>
            </a>
            <a href="{{ route('licenses.index', ['state' => 'expired']) }}" class="data-card p-5 hover:border-health-critical">
                <p class="stat-label">{{ __('app.dashboard.expired_licenses') }}</p>
                <p class="stat-value text-2xl">{{ number_format($expiredCount) }}</p>
            </a>
        </div>

        <div class="grid lg:grid-cols-5 gap-5">
            <div class="lg:col-span-3 data-card overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-brand">{{ __('app.dashboard.preview') }}</h2>
                    <a href="{{ route('map') }}" class="text-sm font-medium text-brand hover:underline">{{ __('app.dashboard.open_map') }}</a>
                </div>
                <div id="dashboard-map" class="h-72 bg-gray-100"></div>
            </div>

            <div class="lg:col-span-2 data-card">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-brand">{{ __('app.dashboard.attention_list') }}</h2>
                    <p class="text-xs text-gray-400 mt-0.5">{{ __('app.dashboard.attention_count', ['count' => $attentionCount]) }}</p>
                </div>
                <ul class="divide-y divide-gray-100">
                    @forelse ($alerts as $tower)
                        <li>
                            <a href="{{ route('towers.show', $tower) }}" class="flex items-start justify-between gap-3 px-5 py-3 hover:bg-gray-50">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-900 truncate">{{ $tower->name }}</p>
                                    <p class="text-xs text-gray-400 truncate">{{ $tower->region->localizedName() }} · {{ $tower->operator->name }}</p>
                                </div>
                                <x-status-badge :value="$tower->health_status === 'unknown' && $tower->isInspectionOverdue() ? 'needs_attention' : $tower->health_status" />
                            </a>
                        </li>
                    @empty
                        <li class="px-5 py-10 text-center text-sm text-gray-500">{{ __('app.dashboard.no_alerts') }}</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    @push('head')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    @endpush
    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
            const preview = @json($previewTowers);
            const map = L.map('dashboard-map').setView([9.56, 44.06], 6);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap',
                maxZoom: 19,
            }).addTo(map);
            const bounds = [];
            preview.forEach((tower) => {
                bounds.push([tower.lat, tower.lng]);
                const icon = L.divIcon({
                    className: '',
                    html: `<span style="display:block;width:12px;height:12px;border-radius:9999px;background:${tower.color};border:2px solid white;box-shadow:0 1px 4px rgba(0,0,0,.25)"></span>`,
                    iconSize: [12, 12],
                    iconAnchor: [6, 6],
                });
                L.marker([tower.lat, tower.lng], { icon }).addTo(map).bindPopup(
                    `<a href="${tower.url}">${tower.name}</a>`
                );
            });
            function fitPreview() {
                map.invalidateSize();
                if (bounds.length) {
                    map.fitBounds(bounds, { padding: [24, 24], maxZoom: 8, animate: false });
                }
            }
            map.whenReady(() => setTimeout(fitPreview, 80));
            window.addEventListener('load', () => setTimeout(fitPreview, 80));
        </script>
    @endpush
</x-app-layout>
