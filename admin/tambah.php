<?php
require_once '../config/database.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php'); exit;
}
$db = getDB();
$kategori = $db->fetchAll("SELECT id, nama_kategori FROM kategori_museum ORDER BY nama_kategori");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Museum - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-layout { display:flex; min-height:100vh; }
        .admin-sidebar {
            width:240px;min-width:240px;background:var(--bg-sidebar);
            border-right:1px solid var(--border-color);padding:1.5rem 0;
        }
        .admin-main { flex:1; padding:2rem; }
        .sidebar-logo { padding:0 1.5rem 1.5rem;border-bottom:1px solid var(--border-color);margin-bottom:1rem; }
        .sidebar-nav a {
            display:flex;align-items:center;gap:0.75rem;
            padding:0.7rem 1.5rem;color:var(--text-secondary);
            text-decoration:none;font-size:0.88rem;font-weight:500;transition:all 0.2s;
        }
        .sidebar-nav a:hover { color:var(--text-primary);background:rgba(255,255,255,0.03); }
        .sidebar-nav a.active { color:var(--accent-red);background:var(--accent-glow);border-right:2px solid var(--accent-red); }
        .form-card {
            background:var(--bg-card);border:1px solid var(--border-color);
            border-radius:12px;padding:1.75rem;
        }
        label { font-size:0.85rem;color:var(--text-secondary);margin-bottom:0.35rem;display:block; }
        .form-group { margin-bottom:1.25rem; }
        #pickMap { height:300px;border-radius:8px;border:1px solid var(--border-color); }
        .map-hint {
            font-size:0.78rem;color:var(--text-muted);
            margin-bottom:0.5rem;padding:0.5rem 0.75rem;
            background:var(--bg-secondary);border-radius:6px;
        }
    </style>
</head>
<body>
<div class="admin-layout">
    <div class="admin-sidebar">
        <div class="sidebar-logo">
            <h6 style="font-weight:700;color:var(--text-primary);margin:0;">Museum Medan</h6>
            <small style="color:var(--text-muted);">Admin Panel</small>
        </div>
        <nav class="sidebar-nav">
            <a href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="museum.php"><i class="bi bi-building"></i> Data Museum</a>
            <a href="tambah.php" class="active"><i class="bi bi-plus-circle"></i> Tambah Museum</a>
            <a href="../peta.php" target="_blank"><i class="bi bi-map"></i> Lihat Peta</a>
            <a href="#" onclick="doLogout()"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </nav>
    </div>
    
    <div class="admin-main">
        <div class="mb-4">
            <a href="museum.php" style="color:var(--text-muted);font-size:0.85rem;text-decoration:none;">
                <i class="bi bi-arrow-left"></i> Kembali ke Data Museum
            </a>
            <h4 style="font-weight:700;margin-top:0.75rem;">Tambah Museum Baru</h4>
        </div>
        
        <div id="alertBox"></div>
        
        <div class="row g-4">
            <!-- Form -->
            <div class="col-lg-6">
                <div class="form-card">
                    <h6 style="font-weight:700;margin-bottom:1.5rem;color:var(--accent-red);">
                        <i class="bi bi-info-circle"></i> Informasi Museum
                    </h6>
                    
                    <div class="form-group">
                        <label>Nama Museum *</label>
                        <input type="text" id="nama" class="form-control-dark" placeholder="Contoh: Museum Negeri Sumatera Utara">
                    </div>
                    
                    <div class="form-group">
                        <label>Kategori *</label>
                        <select id="id_kategori" class="form-control-dark">
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach($kategori as $k): ?>
                            <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kategori']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Alamat *</label>
                        <textarea id="alamat" class="form-control-dark" rows="2" 
                                  placeholder="Jl. HM Joni No.51A, Medan"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea id="deskripsi" class="form-control-dark" rows="3" 
                                  placeholder="Deskripsi singkat tentang museum..."></textarea>
                    </div>
                    
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="form-group">
                                <label>Telepon</label>
                                <input type="text" id="telepon" class="form-control-dark" placeholder="061-XXXXXXX">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label>Harga Tiket</label>
                                <input type="text" id="harga_tiket" class="form-control-dark" placeholder="Rp 10.000">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Jam Buka</label>
                        <input type="text" id="jam_buka" class="form-control-dark" 
                               placeholder="Senin-Jumat 08:00-16:00">
                    </div>
                    
                    <hr style="border-color:var(--border-color);margin:1.5rem 0;">
                    
                    <h6 style="font-weight:700;margin-bottom:1rem;color:var(--accent-red);">
                        <i class="bi bi-geo-alt"></i> Koordinat Lokasi
                    </h6>
                    
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="form-group">
                                <label>Latitude *</label>
                                <input type="number" id="latitude" class="form-control-dark" 
                                       placeholder="3.5952" step="0.000001" min="3.0" max="4.0">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label>Longitude *</label>
                                <input type="number" id="longitude" class="form-control-dark" 
                                       placeholder="98.6722" step="0.000001" min="98.0" max="99.0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2 mt-2">
                        <button class="btn-primary-custom" onclick="submitForm()" style="flex:1;justify-content:center;">
                            <i class="bi bi-check-circle"></i> Simpan Museum
                        </button>
                        <a href="museum.php" class="btn-outline-custom" style="justify-content:center;">
                            Batal
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Peta Pilih Koordinat -->
            <div class="col-lg-6">
                <div class="form-card">
                    <h6 style="font-weight:700;margin-bottom:1rem;color:var(--accent-red);">
                        <i class="bi bi-cursor-fill"></i> Pilih Lokasi di Peta
                    </h6>
                    
                    <!-- Geocoding cepat -->
                    <div class="d-flex gap-2 mb-2">
                        <input type="text" id="pickGeocode" class="form-control-dark" 
                               placeholder="Cari alamat di Medan...">
                        <button class="map-btn" onclick="geocodeForPick()">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                    
                    <div id="pickMap"></div>
                    
                    <div id="coordSelected" style="display:none;margin-top:0.75rem;" 
                         class="alert-custom alert-success">
                        <i class="bi bi-check-circle"></i>
                        <div>
                            Lokasi dipilih:<br>
                            <code id="coordText">-</code>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
const pickMap = L.map('pickMap').setView([3.5952, 98.6722], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap'
}).addTo(pickMap);

let clickMarker = null;

pickMap.on('click', (e) => {
    const lat = e.latlng.lat.toFixed(6);
    const lon = e.latlng.lng.toFixed(6);
    
    document.getElementById('latitude').value  = lat;
    document.getElementById('longitude').value = lon;
    
    if (clickMarker) pickMap.removeLayer(clickMarker);
    
    const icon = L.divIcon({
        html: '<div style="background:#e63946;width:16px;height:16px;border-radius:50%;border:3px solid white;box-shadow:0 0 10px rgba(230,57,70,0.8);"></div>',
        className: '', iconSize: [16,16], iconAnchor: [8,8]
    });
    
    clickMarker = L.marker([lat, lon], {icon})
        .addTo(pickMap)
        .bindPopup(`📍 Lat: ${lat}<br>Lon: ${lon}`)
        .openPopup();
    
    document.getElementById('coordSelected').style.display = 'flex';
    document.getElementById('coordText').textContent = `Lat: ${lat}, Lon: ${lon}`;
});

// Sinkronisasi input koordinat → marker di peta
['latitude', 'longitude'].forEach(id => {
    document.getElementById(id).addEventListener('change', updatePickMarker);
});

function updatePickMarker() {
    const lat = parseFloat(document.getElementById('latitude').value);
    const lon = parseFloat(document.getElementById('longitude').value);
    
    if (lat && lon && lat >= 3.0 && lat <= 4.0 && lon >= 98.0 && lon <= 99.0) {
        if (clickMarker) pickMap.removeLayer(clickMarker);
        
        const icon = L.divIcon({
            html: '<div style="background:#e63946;width:16px;height:16px;border-radius:50%;border:3px solid white;"></div>',
            className: '', iconSize: [16,16], iconAnchor: [8,8]
        });
        
        clickMarker = L.marker([lat, lon], {icon}).addTo(pickMap);
        pickMap.setView([lat, lon], 15);
    }
}

function geocodeForPick() {
    const address = document.getElementById('pickGeocode').value.trim();
    if (!address) return;
    
    fetch(`../api/spasial.php?action=geocode&address=${encodeURIComponent(address)}`)
        .then(r => r.json())
        .then(data => {
            if (data.success && data.data.locations.length > 0) {
                const loc = data.data.locations[0];
                pickMap.setView([loc.latitude, loc.longitude], 16);
            } else {
                alert('Alamat tidak ditemukan');
            }
        });
}

document.getElementById('pickGeocode').addEventListener('keypress', e => {
    if (e.key === 'Enter') geocodeForPick();
});

function submitForm() {
    const data = {
        nama:        document.getElementById('nama').value.trim(),
        id_kategori: document.getElementById('id_kategori').value,
        alamat:      document.getElementById('alamat').value.trim(),
        deskripsi:   document.getElementById('deskripsi').value.trim(),
        telepon:     document.getElementById('telepon').value.trim(),
        harga_tiket: document.getElementById('harga_tiket').value.trim(),
        jam_buka:    document.getElementById('jam_buka').value.trim(),
        latitude:    document.getElementById('latitude').value,
        longitude:   document.getElementById('longitude').value,
    };
    
    if (!data.nama || !data.id_kategori || !data.alamat || !data.latitude || !data.longitude) {
        showAlert('danger', 'Field bertanda * wajib diisi termasuk koordinat');
        return;
    }
    
    fetch('../api/museum.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            showAlert('success', `Museum "${data.nama}" berhasil ditambahkan! ID: ${res.data.id}`);
            setTimeout(() => window.location.href = 'museum.php', 1500);
        } else {
            showAlert('danger', res.message);
        }
    })
    .catch(() => showAlert('danger', 'Gagal menghubungi server'));
}

function showAlert(type, msg) {
    const icons = {success: 'check-circle', danger: 'exclamation-circle'};
    document.getElementById('alertBox').innerHTML = `
        <div class="alert-custom alert-${type}" style="margin-bottom:1rem;">
            <i class="bi bi-${icons[type]}"></i> ${msg}
        </div>
    `;
    window.scrollTo({top: 0, behavior: 'smooth'});
}

function doLogout() {
    if (confirm('Yakin logout?')) {
        fetch('../api/auth.php?action=logout').then(() => window.location.href = '../login.php');
    }
}
</script>
</body>
</html>