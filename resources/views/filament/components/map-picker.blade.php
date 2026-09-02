@push('scripts')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endpush

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endpush

<div
    x-data="checadorMap({
        lat: @entangle('data.lat'),
        lng: @entangle('data.lng'),
    })"
    x-init="init()"
    wire:ignore
    class="overflow-hidden rounded-xl border border-gray-300"
>
    <div x-ref="map" style="height: 340px; width: 100%"></div>
    <div class="bg-gray-50 px-3 py-2 text-xs text-gray-600">
        Haz clic sobre el mapa para fijar el marcador y tomar esas coordenadas,
        o arrástralo a la ubicación deseada.
    </div>
</div>

@push('scripts')
    <script>
        window.checadorMap = (config) => ({
            lat: config.lat,
            lng: config.lng,
            map: null,
            marker: null,
            circle: null,

            hasCoords() {
                const lat = String(this.lat ?? '').trim();
                const lng = String(this.lng ?? '').trim();
                return (
                    lat.length > 0 &&
                    lng.length > 0 &&
                    Number.isFinite(Number(lat)) &&
                    Number.isFinite(Number(lng)) &&
                    Number(lat) !== 0 &&
                    Number(lng) !== 0
                );
            },

            setCoords(lat, lng) {
                const self = this;
                self.lat = Number(lat).toFixed(7);
                self.lng = Number(lng).toFixed(7);
                self.applyCoords();
                self.reverseGeocode(lat, lng);
            },

            reverseGeocode(lat, lng) {
                const self = this;
                const url =
                    'https://nominatim.openstreetmap.org/reverse' +
                    '?format=jsonv2&zoom=18&addressdetails=1' +
                    '&lat=' + encodeURIComponent(lat) +
                    '&lon=' + encodeURIComponent(lng);

                fetch(url, { headers: { 'Accept-Language': 'es' } })
                    .then((res) => {
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        return res.json();
                    })
                    .then((data) => {
                        if (!data || !data.address) return;
                        const a = data.address;
                        const direccion = [
                            a.road || a.pedestrian || a.footway,
                            a.suburb || a.neighbourhood,
                            a.city || a.town || a.village || a.municipality,
                            a.state,
                        ]
                            .filter((v) => typeof v === 'string' && v.trim().length > 0)
                            .join(', ');

                        if (direccion.length > 0) {
                            self.$wire.set('data.direccion', direccion);
                        }
                    })
                    .catch(() => {
                        // Silencioso: si el servicio no responde, no se llena la dirección.
                    });
            },

            applyCoords() {
                const self = this;

                if (!self.map || !self.marker) {
                    return;
                }
                if (!self.hasCoords()) {
                    return;
                }

                self.marker.setLatLng([Number(self.lat), Number(self.lng)]);
                self.map.panTo([Number(self.lat), Number(self.lng)]);
                self.drawCircle();
            },

            init() {
                const self = this;

                this.$nextTick(() => {
                    const el = self.$refs.map;

                    if (!window.L) {
                        return;
                    }

                    const defaultCenter = [18.9538351, -99.2304911];
                    const center = self.hasCoords()
                        ? [Number(self.lat), Number(self.lng)]
                        : defaultCenter;
                    const zoom = self.hasCoords() ? 17 : 12;

                    self.map = L.map(el).setView(center, zoom);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap',
                    }).addTo(self.map);

                    self.marker = L.marker(center, { draggable: true }).addTo(self.map);

                    if (self.hasCoords()) {
                        self.drawCircle();
                    }

                    self.map.on('click', (e) => {
                        self.setCoords(e.latlng.lat, e.latlng.lng);
                    });

                    self.marker.on('dragend', (e) => {
                        const pos = e.target.getLatLng();
                        self.setCoords(pos.lat, pos.lng);
                    });

                    self.$watch('lat', () => self.applyCoords());
                    self.$watch('lng', () => self.applyCoords());
                });
            },

            drawCircle() {
                const self = this;
                const radius = Number(self.$wire.get('data.radio_metros')) || 100;

                if (self.circle) {
                    self.circle.remove();
                }

                self.circle = L.circle([Number(self.lat), Number(self.lng)], {
                    radius,
                    color: '#1D4ED8',
                }).addTo(self.map);
            },
        });
    </script>
@endpush
