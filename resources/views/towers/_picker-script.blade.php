@props([
    'previewTowerId' => null,
])

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endpush
@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        (function () {
            const latInput = document.getElementById('latitude');
            const lngInput = document.getElementById('longitude');
            const gpsButton = document.getElementById('use-gps');
            const gpsLabel = gpsButton.querySelector('[data-gps-label]');
            const gpsStatus = document.getElementById('gps-status');
            const defaultLabel = gpsLabel.textContent;
            const previewUrl = @json(route('towers.location-preview'));
            const exceptTowerId = @json($previewTowerId);
            const proximityMessages = {
                locating: @json(__('app.towers.proximity_loading')),
                fromMap: @json(__('app.towers.proximity_from_map')),
                nearbyNone: @json(__('app.towers.nearby.none', ['radius' => number_format(\App\Support\TowerProximity::NEARBY_RADIUS_METERS)])),
            };
            const messages = {
                locating: @json(__('app.towers.locating')),
                accuracy: @json(__('app.towers.gps_accuracy')),
                denied: @json(__('app.towers.gps_denied')),
                unavailable: @json(__('app.towers.gps_unavailable')),
                timeout: @json(__('app.towers.gps_timeout')),
                unsupported: @json(__('app.towers.gps_unsupported')),
            };

            const nearbyAutoEl = document.getElementById('proximity-nearby-auto');

            let previewTimer = null;
            let previewRequest = 0;

            const lat = parseFloat(latInput.value) || 9.562;
            const lng = parseFloat(lngInput.value) || 44.077;
            const map = L.map('picker-map').setView([lat, lng], 8);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);
            let marker = L.marker([lat, lng], { draggable: true }).addTo(map);

            function applyAmenity(prefix, match) {
                const nameEl = document.getElementById(`nearest_${prefix}_name`);
                const distEl = document.getElementById(`nearest_${prefix}_m`);
                const hintEl = document.getElementById(`proximity-${prefix}-map-hint`);

                if (! nameEl || ! distEl) {
                    return;
                }

                if (match && match.distance_m !== null && match.distance_m !== undefined) {
                    if (match.name) {
                        nameEl.value = match.name;
                    }

                    distEl.value = match.distance_m;
                    hintEl?.classList.remove('hidden');
                } else {
                    nameEl.value = '';
                    distEl.value = '';
                    hintEl?.classList.add('hidden');
                }
            }

            function renderNearbyTowers(towers) {
                if (! nearbyAutoEl) {
                    return;
                }

                if (! towers || towers.length === 0) {
                    nearbyAutoEl.innerHTML = `<p id="proximity-nearby-empty" class="text-gray-500">${proximityMessages.nearbyNone}</p>`;
                    return;
                }

                nearbyAutoEl.innerHTML = '<ul class="space-y-1"></ul>';
                const list = nearbyAutoEl.querySelector('ul');

                towers.forEach((tower) => {
                    const item = document.createElement('li');
                    item.innerHTML = `<a href="${tower.url}" class="text-brand hover:underline">${tower.name}</a> <span class="text-gray-500">— ${tower.operator} (${Number(tower.distance_m).toLocaleString()} m)</span>`;
                    list.appendChild(item);
                });
            }

            function setProximityLoading() {
                ['school', 'hospital', 'house'].forEach((prefix) => {
                    document.getElementById(`proximity-${prefix}-map-hint`)?.classList.add('hidden');
                });

                if (nearbyAutoEl) {
                    nearbyAutoEl.innerHTML = `<p class="text-gray-500">${proximityMessages.locating}</p>`;
                }
            }

            async function refreshLocationPreview() {
                const latitude = parseFloat(latInput.value);
                const longitude = parseFloat(lngInput.value);

                if (! Number.isFinite(latitude) || ! Number.isFinite(longitude)) {
                    return;
                }

                const requestId = ++previewRequest;
                setProximityLoading();

                const params = new URLSearchParams({
                    latitude: latitude.toString(),
                    longitude: longitude.toString(),
                });

                if (exceptTowerId) {
                    params.set('except_tower_id', exceptTowerId);
                }

                try {
                    const response = await fetch(`${previewUrl}?${params.toString()}`, {
                        headers: { Accept: 'application/json' },
                    });

                    if (! response.ok || requestId !== previewRequest) {
                        return;
                    }

                    const data = await response.json();

                    if (requestId !== previewRequest) {
                        return;
                    }

                    applyAmenity('school', data.school);
                    applyAmenity('hospital', data.hospital);
                    applyAmenity('house', data.house);
                    renderNearbyTowers(data.nearby_towers || []);
                } catch (error) {
                    if (requestId !== previewRequest) {
                        return;
                    }

                    renderNearbyTowers([]);
                }
            }

            function scheduleLocationPreview() {
                clearTimeout(previewTimer);
                previewTimer = setTimeout(refreshLocationPreview, 450);
            }

            function notifyLocationChange() {
                scheduleLocationPreview();
                window.dispatchEvent(new CustomEvent('tower-location-updated'));
            }

            function setLocation(nextLat, nextLng, zoom = 16) {
                const ll = L.latLng(nextLat, nextLng);
                latInput.value = ll.lat.toFixed(7);
                lngInput.value = ll.lng.toFixed(7);
                marker.setLatLng(ll);
                map.setView(ll, zoom);
                notifyLocationChange();
            }

            function showGpsStatus(text, isError = false) {
                gpsStatus.textContent = text;
                gpsStatus.classList.remove('hidden', 'text-gray-500', 'text-red-600', 'text-brand');
                gpsStatus.classList.add(isError ? 'text-red-600' : 'text-brand');
            }

            marker.on('dragend', () => {
                const ll = marker.getLatLng();
                setLocation(ll.lat, ll.lng, map.getZoom());
            });
            map.on('click', (e) => setLocation(e.latlng.lat, e.latlng.lng, Math.max(map.getZoom(), 14)));

            ['change', 'input'].forEach((evt) => {
                latInput.addEventListener(evt, moveFromInputs);
                lngInput.addEventListener(evt, moveFromInputs);
            });

            function moveFromInputs() {
                const nextLat = parseFloat(latInput.value);
                const nextLng = parseFloat(lngInput.value);
                if (Number.isFinite(nextLat) && Number.isFinite(nextLng)) {
                    marker.setLatLng([nextLat, nextLng]);
                    map.panTo([nextLat, nextLng]);
                    notifyLocationChange();
                }
            }

            gpsButton.addEventListener('click', () => {
                if (! navigator.geolocation) {
                    showGpsStatus(messages.unsupported, true);
                    return;
                }

                gpsButton.disabled = true;
                gpsLabel.textContent = messages.locating;
                showGpsStatus(messages.locating);

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        setLocation(position.coords.latitude, position.coords.longitude, 17);
                        const meters = Math.round(position.coords.accuracy || 0);
                        showGpsStatus(messages.accuracy.replace(':meters', meters));
                        gpsButton.disabled = false;
                        gpsLabel.textContent = defaultLabel;
                    },
                    (error) => {
                        const mapErrors = {
                            1: messages.denied,
                            2: messages.unavailable,
                            3: messages.timeout,
                        };
                        showGpsStatus(mapErrors[error.code] || messages.unavailable, true);
                        gpsButton.disabled = false;
                        gpsLabel.textContent = defaultLabel;
                    },
                    { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 }
                );
            });

            scheduleLocationPreview();

            window.addEventListener('geography-map-focus', (event) => {
                const bounds = event.detail && event.detail.bounds;
                if (! bounds || bounds.south == null || bounds.west == null || bounds.north == null || bounds.east == null) {
                    return;
                }
                map.fitBounds(
                    [[bounds.south, bounds.west], [bounds.north, bounds.east]],
                    {
                        padding: [28, 28],
                        maxZoom: event.detail.maxZoom || 12,
                        animate: true,
                    }
                );
                setTimeout(() => map.invalidateSize(), 150);
            });
        })();
    </script>
@endpush
