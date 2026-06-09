<?php
require_once 'config/database.php';
session_start();

$db = getDB();
$kategori = $db->fetchAll("SELECT id, nama_kategori FROM kategori_museum ORDER BY nama_kategori");
$museums  = $db->fetchAll("SELECT id, nama FROM v_museum_lengkap ORDER BY nama");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peta Museum - Museum Medan GIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { overflow: hidden; }
        .peta-layout {
            display: flex;
            height: calc(100vh - 62px);
        }
        .peta-sidebar {
            width: 320px;
            min-width: 320px;
            background: var(--bg-card);
            border-right: 1px solid var(--border-color);
            overflow-y: auto;
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .peta-main {
            flex: 1;
            position: relative;
        }
        #map {
            height: 100%;
            width: 100%;
        }
        .map-controls {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .map-btn {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: 0.5rem 0.85rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.82rem;
            font-weight: 600;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .map-btn:hover { border-color: var(--accent-red); color: var(--accent-red); }
        .map-btn.active { background: var(--accent-red); border-color: var(--accent-red); color: white; }
        .result-panel {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 0.75rem;
            font-size: 0.82rem;
        }
        .result-item {
            padding: 0.5rem;
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
        }
        .result-item:last-child { border-bottom: none; }
        .result-item:hover { background: rgba(230,57,70,0.05); border-radius: 5px; }
    </style>
</head>
<body>

<!--NAVBAR-->
<nav class="navbar navbar-custom" style="height:62px;">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">
             Museum Medan
        </a>
        <div class="d-flex gap-2 align-items-center">
            <a href="index.php" class="nav-link"><i class="bi bi-house"></i> Home</a>
            <a href="peta.php" class="nav-link active"><i class="bi bi-map"></i> Peta</a>
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="admin/index.php" class="btn-primary-custom" style="padding:0.4rem 0.9rem;font-size:0.82rem;">Admin</a>
            <?php else: ?>
                <a href="login.php" class="btn-outline-custom" style="padding:0.4rem 0.9rem;font-size:0.82rem;">Login</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!--LAYOUT PETA-->
<div class="peta-layout">
    
    <!--SIDEBAR KIRI-->
    <div class="peta-sidebar">
        
        <!--Cari Museum-->
        <div>
            <div class="sidebar-title"><i class="bi bi-search"></i> Cari Museum</div>
            <input type="text" id="searchMuseum" class="form-control-dark" 
                   placeholder="Ketik nama museum...">
            <div id="searchResult" style="margin-top:0.5rem;"></div>
        </div>
        
        <!--Filter Kategori-->
        <div>
            <div class="sidebar-title"><i class="bi bi-funnel"></i> Filter Kategori</div>
            <select id="filterKategori" class="form-control-dark">
                <option value="">Semua Kategori</option>
                <?php foreach($kategori as $kat): ?>
                <option value="<?= $kat['id'] ?>">
                    <?= htmlspecialchars($kat['nama_kategori']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!--Museum Terdekat-->
        <div>
            <div class="sidebar-title"><i class="bi bi-geo-alt"></i> Museum Terdekat</div>
            <div class="d-flex gap-2 mb-2">
                <input type="number" id="nearestLimit" class="form-control-dark" 
                       value="3" min="1" max="10" style="width:80px;">
                <button class="btn-primary-custom" onclick="findNearest()" style="flex:1;padding:0.5rem;">
                    <i class="bi bi-crosshair"></i> Cari
                </button>
            </div>
            <div id="nearestResult"></div>
        </div>
        
        <!--Radius Query-->
        <div>
            <div class="sidebar-title"><i class="bi bi-circle"></i> Radius Query</div>
            <div class="mb-2">
                <label class="text-secondary" style="font-size:0.8rem;">Radius (meter):</label>
                <input type="range" id="radiusSlider" min="500" max="10000" value="2000" 
                       step="500" style="width:100%;accent-color:var(--accent-red);">
                <div class="d-flex justify-content-between" style="font-size:0.78rem;color:var(--text-muted);">
                    <span>500m</span>
                    <span id="radiusValue" style="color:var(--accent-red);font-weight:600;">2000m</span>
                    <span>10km</span>
                </div>
            </div>
            <button class="btn-primary-custom w-100" onclick="searchRadius()" style="padding:0.5rem;">
                <i class="bi bi-search-heart"></i> Cari dalam Radius
            </button>
            <div id="radiusResult" class="mt-2"></div>
        </div>
        
        <!--Hitung Jarak-->
        <div>
            <div class="sidebar-title"><i class="bi bi-rulers"></i> Hitung Jarak</div>
            <select id="museumA" class="form-control-dark mb-2">
                <option value="">-- Museum A --</option>
                <?php foreach($museums as $m): ?>
                <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nama']) ?></option>
                <?php endforeach; ?>
            </select>
            <select id="museumB" class="form-control-dark mb-2">
                <option value="">-- Museum B --</option>
                <?php foreach($museums as $m): ?>
                <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nama']) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn-primary-custom w-100" onclick="hitungJarak()" style="padding:0.5rem;">
                <i class="bi bi-calculator"></i> Hitung Jarak
            </button>
            <div id="jarakResult" class="mt-2"></div>
        </div>
        
        <!--Geocoding-->
        <div>
            <div class="sidebar-title"><i class="bi bi-pin-map"></i> Geocoding Alamat</div>
            <div class="d-flex gap-2">
                <input type="text" id="geocodeInput" class="form-control-dark" 
                       placeholder="Masukkan alamat...">
                <button class="map-btn" onclick="doGeocode()">
                    <i class="bi bi-search"></i>
                </button>
            </div>
            <div id="geocodeResult" class="mt-2"></div>
        </div>
        
        <!--Daftar Museum-->
        <div style="flex:1;">
            <div class="sidebar-title"><i class="bi bi-list-ul"></i> Daftar Museum (<span id="museumCount">0</span>)</div>
            <div id="museumList"></div>
        </div>
        
    </div>
    
    <!--PETA UTAMA-->
    <div class="peta-main">
        <div id="map"></div>
        
        <div class="map-controls">
            <button class="map-btn" onclick="resetMap()">
                <i class="bi bi-arrows-fullscreen"></i> Reset Peta
            </button>
            <button class="map-btn" id="btnLocation" onclick="getUserLocation()">
                <i class="bi bi-cursor-fill"></i> Lokasi Saya
            </button>
            <button class="map-btn" id="btnTileToggle" onclick="toggleTile()">
                <i class="bi bi-layers"></i> Ganti Layer
            </button>
        </div>
        
        <div id="coordInfo" style="
            position:absolute;bottom:10px;left:10px;z-index:1000;
            background:rgba(0,0,0,0.7);padding:0.4rem 0.85rem;
            border-radius:6px;font-size:0.78rem;color:#ccc;
            border:1px solid #333;">
            Arahkan mouse ke peta
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>

const map = L.map('map').setView([3.5952, 98.6722], 13);

const osmTile = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 19
});

const satelliteTile = L.tileLayer(
    'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
    { attribution: '© Esri', maxZoom: 19 }
);

osmTile.addTo(map);
let currentTile = 'osm';

function toggleTile() {
    if (currentTile === 'osm') {
        map.removeLayer(osmTile);
        satelliteTile.addTo(map);
        currentTile = 'satellite';
        document.getElementById('btnTileToggle').innerHTML = '<i class="bi bi-map"></i> OSM Layer';
    } else {
        map.removeLayer(satelliteTile);
        osmTile.addTo(map);
        currentTile = 'osm';
        document.getElementById('btnTileToggle').innerHTML = '<i class="bi bi-layers"></i> Ganti Layer';
    }
}

let museumLayer    = null;  
let userMarker     = null;  
let radiusCircle   = null;  
let highlightLayer = null;  
let allMuseums     = [];    

const museumIcon = L.divIcon({
    html: `<div style="
        background: #e63946;
        width: 14px; height: 14px;
        border-radius: 50%;
        border: 2px solid white;
        box-shadow: 0 0 8px rgba(230,57,70,0.8);
        transition: all 0.2s;
    "></div>`,
    className: '',
    iconSize: [14, 14],
    iconAnchor: [7, 7],
    popupAnchor: [0, -10]
});

const userIcon = L.divIcon({
    html: `<div style="
        background: #3498db;
        width: 16px; height: 16px;
        border-radius: 50%;
        border: 3px solid white;
        box-shadow: 0 0 12px rgba(52,152,219,0.8);
    "></div>`,
    className: '',
    iconSize: [16, 16],
    iconAnchor: [8, 8]
});

const highlightIcon = L.divIcon({
    html: `<div style="
        background: #f39c12;
        width: 18px; height: 18px;
        border-radius: 50%;
        border: 3px solid white;
        box-shadow: 0 0 12px rgba(243,156,18,0.9);
        animation: pulse 1s infinite;
    "></div>`,
    className: '',
    iconSize: [18, 18],
    iconAnchor: [9, 9],
    popupAnchor: [0, -12]
});

function loadMuseums(filterKategori = '') {
    let url = 'api/museum.php?action=geojson';
    
    fetch(url)
        .then(res => res.json())
        .then(geojson => {
            allMuseums = geojson.features;
            
            let filtered = geojson;
            if (filterKategori) {
                filtered = {
                    ...geojson,
                    features: geojson.features.filter(f => 
                        f.properties.id_kategori == filterKategori
                    )
                };
            }
            
            if (museumLayer) map.removeLayer(museumLayer);
            
            museumLayer = L.geoJSON(filtered, {
                pointToLayer: (feature, latlng) => L.marker(latlng, {icon: museumIcon}),
                onEachFeature: (feature, layer) => {
                    const p = feature.properties;
                    
                    layer.bindPopup(`
                        <div style="min-width:200px;">
                            <div class="popup-nama">${p.nama}</div>
                            <div class="popup-info">📍 ${p.alamat}</div>
                            <div class="popup-info">🏷️ ${p.kategori}</div>
                            <div class="popup-info">🕐 ${p.jam_buka || '-'}</div>
                            <div class="popup-info">🎫 ${p.harga_tiket || '-'}</div>
                            <div class="popup-info" style="font-size:0.75rem;color:#666;margin-top:0.25rem;">
                                📌 ${p.latitude.toFixed(6)}, ${p.longitude.toFixed(6)}
                            </div>
                        </div>
                    `);
                    
                    layer.bindTooltip(p.nama, {
                        permanent: false,
                        direction: 'top',
                        className: 'leaflet-tooltip-custom'
                    });
                    
                    layer.featureData = p;
                }
            }).addTo(map);
            
            updateMuseumList(filtered.features);
            document.getElementById('museumCount').textContent = filtered.features.length;
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Gagal memuat data museum');
        });
}

function updateMuseumList(features) {
    const container = document.getElementById('museumList');
    container.innerHTML = '';
    
    features.forEach(f => {
        const p = f.properties;
        const div = document.createElement('div');
        div.className = 'museum-list-item';
        div.innerHTML = `
            <div class="museum-name">${p.nama}</div>
            <div class="museum-cat">${p.kategori}</div>
        `;
        div.onclick = () => {
            map.setView([p.latitude, p.longitude], 16);
            museumLayer.eachLayer(layer => {
                if (layer.featureData && layer.featureData.id === p.id) {
                    layer.openPopup();
                }
            });
        };
        container.appendChild(div);
    });
}

document.getElementById('searchMuseum').addEventListener('input', function() {
    const query = this.value.toLowerCase().trim();
    
    if (!query) {
        document.getElementById('searchResult').innerHTML = '';
        updateMuseumList(allMuseums);
        return;
    }
    
    const filtered = allMuseums.filter(f => 
        f.properties.nama.toLowerCase().includes(query) ||
        f.properties.alamat.toLowerCase().includes(query)
    );
    
    updateMuseumList(filtered);
    document.getElementById('museumCount').textContent = filtered.length;
    
    if (filtered.length === 1) {
        const p = filtered[0].properties;
        map.setView([p.latitude, p.longitude], 16);
    }
});

document.getElementById('filterKategori').addEventListener('change', function() {
    loadMuseums(this.value);
});

let userLat = null, userLon = null;

function getUserLocation() {
    if (!navigator.geolocation) {
        alert('Browser tidak mendukung geolokasi');
        return;
    }
    
    document.getElementById('btnLocation').innerHTML = '<i class="bi bi-hourglass"></i> Mencari...';
    
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            userLat = pos.coords.latitude;
            userLon = pos.coords.longitude;
            
            if (userMarker) map.removeLayer(userMarker);
            
            userMarker = L.marker([userLat, userLon], {icon: userIcon})
                .addTo(map)
                .bindPopup('<strong>📍 Lokasi Anda</strong>')
                .openPopup();
            
            map.setView([userLat, userLon], 14);
            document.getElementById('btnLocation').innerHTML = 
                '<i class="bi bi-cursor-fill"></i> Lokasi Saya ✓';
            document.getElementById('btnLocation').classList.add('active');
        },
        (err) => {
            userLat = 3.5952;
            userLon = 98.6722;
            alert('Geolokasi gagal. Menggunakan lokasi default (Pusat Medan)');
            document.getElementById('btnLocation').innerHTML = 
                '<i class="bi bi-cursor-fill"></i> Lokasi Saya';
        }
    );
}

function findNearest() {
    if (!userLat || !userLon) {
        navigator.geolocation.getCurrentPosition(
            pos => {
                userLat = pos.coords.latitude;
                userLon = pos.coords.longitude;
                _doFindNearest();
            },
            () => {
                userLat = 3.5952;
                userLon = 98.6722;
                _doFindNearest();
            }
        );
    } else {
        _doFindNearest();
    }
}

function _doFindNearest() {
    const limit = document.getElementById('nearestLimit').value;
    const container = document.getElementById('nearestResult');
    container.innerHTML = '<div class="spinner-custom"></div>';
    
    fetch(`api/spasial.php?action=nearest&lat=${userLat}&lon=${userLon}&limit=${limit}`)
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                container.innerHTML = `<div class="alert-custom alert-danger">${data.message}</div>`;
                return;
            }
            
            if (highlightLayer) map.removeLayer(highlightLayer);
            highlightLayer = L.layerGroup().addTo(map);
            
            let html = '';
            data.data.museums.forEach((m, i) => {
                L.marker([m.latitude, m.longitude], {icon: highlightIcon})
                    .addTo(highlightLayer)
                    .bindPopup(`
                        <div class="popup-nama">${m.nama}</div>
                        <div class="popup-info">📍 ${m.alamat}</div>
                        <div class="popup-info">📏 <strong>${m.jarak_km} km</strong> dari lokasi Anda</div>
                    `);
                
                html += `
                    <div class="result-item" onclick="map.setView([${m.latitude},${m.longitude}],16)">
                        <strong style="font-size:0.85rem;">${i+1}. ${m.nama}</strong><br>
                        <span style="color:var(--accent-red);font-size:0.8rem;">
                            📏 ${m.jarak_km} km
                        </span>
                        <span style="color:var(--text-muted);font-size:0.78rem;"> · ${m.kategori}</span>
                    </div>
                `;
            });
            
            container.innerHTML = `
                <div class="result-panel">
                    <div style="font-size:0.78rem;color:var(--text-muted);margin-bottom:0.5rem;">
                        ${data.data.total} museum terdekat ditemukan
                    </div>
                    ${html}
                </div>
            `;
        })
        .catch(() => {
            container.innerHTML = '<div class="alert-custom alert-danger">Gagal menghubungi server</div>';
        });
}

document.getElementById('radiusSlider').addEventListener('input', function() {
    const val = parseInt(this.value);
    document.getElementById('radiusValue').textContent = 
        val >= 1000 ? (val/1000).toFixed(1) + 'km' : val + 'm';
});

function searchRadius() {
    if (!userLat || !userLon) {
        getUserLocation();
        setTimeout(searchRadius, 2000);
        return;
    }
    
    const radius = document.getElementById('radiusSlider').value;
    const container = document.getElementById('radiusResult');
    container.innerHTML = '<div class="spinner-custom"></div>';
    
    if (radiusCircle) map.removeLayer(radiusCircle);
    if (highlightLayer) map.removeLayer(highlightLayer);
    
    radiusCircle = L.circle([userLat, userLon], {
        radius: parseInt(radius),
        color: '#e63946',
        fillColor: '#e63946',
        fillOpacity: 0.08,
        weight: 2,
        dashArray: '5,5'
    }).addTo(map);
    
    map.fitBounds(radiusCircle.getBounds(), {padding: [20, 20]});
    
    fetch(`api/spasial.php?action=radius&lat=${userLat}&lon=${userLon}&radius=${radius}`)
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                container.innerHTML = `<div class="alert-custom alert-danger">${data.message}</div>`;
                return;
            }
            
            highlightLayer = L.layerGroup().addTo(map);
            
            data.data.museums.forEach(m => {
                L.marker([m.latitude, m.longitude], {icon: highlightIcon})
                    .addTo(highlightLayer)
                    .bindPopup(`
                        <div class="popup-nama">${m.nama}</div>
                        <div class="popup-info">📏 ${m.jarak_km} km · ${m.jarak_meter} m</div>
                    `);
            });
            
            const total = data.data.total;
            const r = data.data.radius_km;
            
            container.innerHTML = `
                <div class="alert-custom ${total > 0 ? 'alert-success' : 'alert-info'}">
                    <i class="bi bi-${total > 0 ? 'check-circle' : 'info-circle'}"></i>
                    <span><strong>${total}</strong> museum dalam radius ${r} km</span>
                </div>
                ${total > 0 ? data.data.museums.map(m => `
                    <div class="result-item" onclick="map.setView([${m.latitude},${m.longitude}],16)">
                        <strong style="font-size:0.82rem;">${m.nama}</strong><br>
                        <span style="color:var(--accent-red);font-size:0.78rem;">📏 ${m.jarak_km} km</span>
                    </div>
                `).join('') : ''}
            `;
        });
}

function hitungJarak() {
    const idA = document.getElementById('museumA').value;
    const idB = document.getElementById('museumB').value;
    
    if (!idA || !idB) {
        alert('Pilih dua museum terlebih dahulu');
        return;
    }
    
    if (idA === idB) {
        alert('Pilih dua museum yang berbeda');
        return;
    }
    
    const container = document.getElementById('jarakResult');
    container.innerHTML = '<div class="spinner-custom"></div>';
    
    fetch(`api/spasial.php?action=distance&museum_a=${idA}&museum_b=${idB}`)
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                container.innerHTML = `<div class="alert-custom alert-danger">${data.message}</div>`;
                return;
            }
            
            const h = data.data.hasil;
            
            if (highlightLayer) map.removeLayer(highlightLayer);
            highlightLayer = L.layerGroup().addTo(map);
            
            const latA = parseFloat(h.lat_a), lonA = parseFloat(h.lon_a);
            const latB = parseFloat(h.lat_b), lonB = parseFloat(h.lon_b);
            
            L.polyline([[latA, lonA], [latB, lonB]], {
                color: '#e63946',
                weight: 2,
                dashArray: '5,5'
            }).addTo(highlightLayer);
            
            L.marker([latA, lonA], {icon: highlightIcon})
                .addTo(highlightLayer)
                .bindPopup(`<strong>A: ${h.nama_museum_a}</strong>`);
            L.marker([latB, lonB], {icon: highlightIcon})
                .addTo(highlightLayer)
                .bindPopup(`<strong>B: ${h.nama_museum_b}</strong>`);
            
            map.fitBounds([[latA, lonA], [latB, lonB]], {padding: [40, 40]});
            
            container.innerHTML = `
                <div class="result-panel" style="text-align:center;">
                    <div style="font-size:0.78rem;color:var(--text-muted);margin-bottom:0.5rem;">
                        ${h.nama_museum_a} → ${h.nama_museum_b}
                    </div>
                    <div style="font-size:2rem;font-weight:700;color:var(--accent-red);">
                        ${h.jarak_km} km
                    </div>
                    <div style="font-size:0.78rem;color:var(--text-muted);">
                        ${h.jarak_meter} meter
                    </div>
                </div>
            `;
        });
}

function doGeocode() {
    const address = document.getElementById('geocodeInput').value.trim();
    if (!address) { alert('Masukkan alamat terlebih dahulu'); return; }
    
    const container = document.getElementById('geocodeResult');
    container.innerHTML = '<div class="spinner-custom"></div>';
    
    fetch(`api/spasial.php?action=geocode&address=${encodeURIComponent(address)}`)
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                container.innerHTML = `<div class="alert-custom alert-danger">${data.message}</div>`;
                return;
            }
            
            const loc = data.data.locations[0];
            
            if (userMarker) map.removeLayer(userMarker);
            userMarker = L.marker([loc.latitude, loc.longitude], {icon: userIcon})
                .addTo(map)
                .bindPopup(`<strong>📍 ${address}</strong><br><small>${loc.nama_lokasi}</small>`)
                .openPopup();
            
            map.setView([loc.latitude, loc.longitude], 15);
            
            container.innerHTML = `
                <div class="alert-custom alert-success">
                    <i class="bi bi-check-circle"></i>
                    <div>
                        <strong>Ditemukan!</strong><br>
                        <small>Lat: ${loc.latitude.toFixed(6)}, Lon: ${loc.longitude.toFixed(6)}</small>
                    </div>
                </div>
            `;
        });
}

document.getElementById('geocodeInput').addEventListener('keypress', e => {
    if (e.key === 'Enter') doGeocode();
});

map.on('mousemove', (e) => {
    document.getElementById('coordInfo').textContent = 
        `Lat: ${e.latlng.lat.toFixed(6)} | Lon: ${e.latlng.lng.toFixed(6)}`;
});

function resetMap() {
    map.setView([3.5952, 98.6722], 13);
    if (radiusCircle)   map.removeLayer(radiusCircle);
    if (highlightLayer) map.removeLayer(highlightLayer);
    if (userMarker)     map.removeLayer(userMarker);
    radiusCircle = highlightLayer = userMarker = null;
    userLat = userLon = null;
    document.getElementById('btnLocation').classList.remove('active');
    document.getElementById('nearestResult').innerHTML = '';
    document.getElementById('radiusResult').innerHTML = '';
    document.getElementById('jarakResult').innerHTML = '';
}

loadMuseums();
</script>
</body>
</html>