@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endpush
@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const latInput = document.getElementById('latitude');
        const lngInput = document.getElementById('longitude');
        const lat = parseFloat(latInput.value) || 9.562;
        const lng = parseFloat(lngInput.value) || 44.077;
        const map = L.map('picker-map').setView([lat, lng], 8);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);
        let marker = L.marker([lat, lng], { draggable: true }).addTo(map);

        function syncInputs(ll) {
            latInput.value = ll.lat.toFixed(7);
            lngInput.value = ll.lng.toFixed(7);
        }

        marker.on('dragend', () => syncInputs(marker.getLatLng()));
        map.on('click', (e) => {
            marker.setLatLng(e.latlng);
            syncInputs(e.latlng);
        });
        ['change', 'input'].forEach((evt) => {
            latInput.addEventListener(evt, moveFromInputs);
            lngInput.addEventListener(evt, moveFromInputs);
        });
        function moveFromInputs() {
            const next = [parseFloat(latInput.value), parseFloat(lngInput.value)];
            if (Number.isFinite(next[0]) && Number.isFinite(next[1])) {
                marker.setLatLng(next);
                map.panTo(next);
            }
        }
    </script>
@endpush
