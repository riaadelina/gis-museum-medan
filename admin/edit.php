<?php
require_once '../config/database.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php'); exit;
}

$db = getDB();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { header('Location: museum.php'); exit; }

$museum  = $db->fetchOne("SELECT * FROM v_museum_lengkap WHERE id = :id", [':id' => $id]);
if (!$museum) { header('Location: museum.php'); exit; }

$kategori = $db->fetchAll("SELECT id, nama_kategori FROM kategori_museum ORDER BY nama_kategori");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Museum - Admin</title>
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
        .sidebar-nav a:hover { color:var(--text-primary); }
        .sidebar-nav a.active { color:var(--accent-red);background:var(--accent-glow);border-right:2px solid var(--accent-red); }
        .form-card { background:var(--bg-card);border:1px solid var(--border-color);border-radius:12px;padding:1.75rem; }
        label { font-size:0.85rem;color:var(--text-secondary);margin-bottom:0.35rem;display:block; }
        .form-group { margin-bottom:1.25rem; }
        #editMap { height:280px;border-radius:8px;border:1px solid var(--border-color); }
    </style>
</head>
<body>
<div class="admin-layout">
    <div class="admin-sidebar">
        <div class="sidebar-logo">
            <div style="font-size:1.5rem;margin-bottom:0.5rem;">🗺️</div>
            <h6 style="font-weight:700;color:var(--text-primary);margin:0;">Museum Medan GIS</h6>
            <small style="color:var(--text-muted);">Admin Panel</small>
        </div>
        <nav class="sidebar-nav">
            <a href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="museum.php" class="active"><i class="bi bi-building"></i> Data Museum</a>
            <a href="tambah.php"><i class="bi bi-plus-circle"></i> Tambah Museum</a>
            <a href="#" onclick="doLogout()"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </nav>
    </div>
    
    <div class="admin-main">
        <div class="mb-4">
            <a href="museum.php" style="color:var(--text-muted);font-size:0.85rem;text-decoration:none;">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            <h4 style="font-weight:700;margin-top:0.75rem;">Edit Museum</h4>
            <p class="text-secondary">ID Museum: <?= $id ?></p>
        </div>
        
        <div id="alertBox"></div>
        
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="form-card">
                    <h6 style="font-weight:700;margin-bottom:1.5rem;color:var(--accent-red);">Informasi Museum</h6>
                    
                    <div class="form-group">
                        <label>Nama Museum *</label>
                        <input type="text" id="nama" class="form-control-dark" 
                               value="<?= htmlspecialchars($museum['nama']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Kategori *</label>
                        <select id="id_kategori" class="form-control-dark">
                            <?php foreach($kategori as $k): ?>
                            <option value="<?= $k['id'] ?>" 
                                <?= $k['id'] == $museum['id_kategori'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($k['nama_kategori']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Alamat *</label>
                        <textarea id="alamat" class="form-control-dark" rows="2"><?= htmlspecialchars($museum['alamat']) ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea id="deskripsi" class="form-control-dark" rows="3"><?= htmlspecialchars($museum['deskripsi'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="form-group">
                                <label>Telepon</label>
                                <input type="text" id="telepon" class="form-control-dark" 
                                       value="<?= htmlspecialchars($museum['telepon'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label>Harga Tiket</label>
                                <input type="text" id="harga_tiket" class="form-control-dark" 
                                       value="<?= htmlspecialchars($museum['harga_tiket'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Jam Buka</label>
                        <input type="text" id="jam_buka" class="form-control-dark" 
                               value="<?= htmlspecialchars($museum['jam_buka'] ?? '') ?>">
                    </div>
                    
                    <hr style="border-color:var(--border-color);margin:1.5rem 0;">
                    
                    <h6 style="font-weight:700;margin-bottom:1rem;color:var(--accent-red);">Koordinat Lokasi</h6>
                    
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="form-group">
                                <label>Latitude *</label>
                                <input type="number" id="latitude" class="form-control-dark" 
                                       value="<?= $museum['latitude'] ?>" step="0.000001">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label>Longitude *</label>
                                <input type="number" id="longitude" class="form-control-dark" 
                                       value="<?= $museum['longitude'] ?>" step="0.000001">
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2 mt-2">
                        <button class="btn-primary-custom" onclick="submitEdit()" style="flex:1;justify-content:center;">
                            <i class="bi bi-check-circle"></i> Update Museum
                        </button>
                        <a href="museum.php" class="btn-outline-custom" style="justify-content:center;">Batal</a>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="form-card">
                    <h6 style="font-weight:700;margin-bottom:1rem;color:var(--accent-red);">Pindahkan Lokasi di Peta</h6>
                    <p style="font-size:0.82rem;color:var(--text-muted);margin-bottom:0.75rem;">
                        <i class="bi bi-info-circle"></i> Klik pada peta untuk mengubah lokasi museum
                    </p>
                    <div id="editMap"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
// Data museum dari PHP
const currentLat = <?= $museum['latitude'] ?>;
const currentLon = <?= $museum['longitude'] ?>;

// Inisialisasi peta edit
const editMap = L.map('editMap').setView([currentLat, currentLon], 15);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(editMap);

const redIcon = L.divIcon({
    html: '<div style="background:#e63946;width:16px;height:16px;border-radius:50%;border:3px solid white;box-shadow:0 0 10px rgba(230,57,70,0.8);"></div>',
    className: '', iconSize: [16,16], iconAnchor: [8,8]
});

let editMarker = L.marker([currentLat, currentLon], {icon: redIcon, draggable: true})
    .addTo(editMap)
    .bindPopup('Seret marker atau klik peta untuk pindahkan lokasi');

// Drag marker
editMarker.on('dragend', (e) => {
    const pos = e.target.getLatLng();
    document.getElementById('latitude').value  = pos.lat.toFixed(6);
    document.getElementById('longitude').value = pos.lng.toFixed(6);
});

// Klik peta
editMap.on('click', (e) => {
    const lat = e.latlng.lat.toFixed(6);
    const lon = e.latlng.lng.toFixed(6);
    editMarker.setLatLng([lat, lon]);
    document.getElementById('latitude').value  = lat;
    document.getElementById('longitude').value = lon;
});

// Submit edit
function submitEdit() {
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
    
    if (!data.nama || !data.alamat || !data.latitude || !data.longitude) {
        showAlert('danger', 'Field bertanda * wajib diisi');
        return;
    }
    
    fetch(`../api/museum.php?id=<?= $id ?>`, {
        method: 'PUT',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            showAlert('success', 'Museum berhasil diperbarui!');
            setTimeout(() => window.location.href = 'museum.php', 1500);
        } else {
            showAlert('danger', res.message);
        }
    });
}

function showAlert(type, msg) {
    document.getElementById('alertBox').innerHTML = `
        <div class="alert-custom alert-${type}" style="margin-bottom:1rem;">
            <i class="bi bi-${type === 'success' ? 'check' : 'exclamation'}-circle"></i> ${msg}
        </div>
    `;
}

function doLogout() {
    if (confirm('Yakin logout?')) {
        fetch('../api/auth.php?action=logout').then(() => window.location.href = '../login.php');
    }
}
</script>
</body>
</html>