@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endpush
@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const latInput = document.getElementById('latitude');
        const lngInput = document.getElementById('longitude');
        const gpsButton = document.getElementById('use-gps');
        const gpsLabel = gpsButton.querySelector('[data-gps-label]');
        const gpsStatus = document.getElementById('gps-status');
        const defaultLabel = gpsLabel.textContent;
        const messages = {
            locating: @json(__('app.towers.locating')),
            accuracy: @json(__('app.towers.gps_accuracy')),
            denied: @json(__('app.towers.gps_denied')),
            unavailable: @json(__('app.towers.gps_unavailable')),
            timeout: @json(__('app.towers.gps_timeout')),
            unsupported: @json(__('app.towers.gps_unsupported')),
        };

        const lat = parseFloat(latInput.value) || 9.562;
        const lng = parseFloat(lngInput.value) || 44.077;
        const map = L.map('picker-map').setView([lat, lng], 8);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);
        let marker = L.marker([lat, lng], { draggable: true }).addTo(map);

        function setLocation(nextLat, nextLng, zoom = 16) {
            const ll = L.latLng(nextLat, nextLng);
            latInput.value = ll.lat.toFixed(7);
            lngInput.value = ll.lng.toFixed(7);
            marker.setLatLng(ll);
            map.setView(ll, zoom);
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
    </script>
@endpush
