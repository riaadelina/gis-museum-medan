<?php
require_once '../config/database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php'); exit;
}

$db = getDB();
$museums = $db->fetchAll("
    SELECT id, nama, alamat, kategori, latitude, longitude, status, jam_buka, harga_tiket 
    FROM v_museum_lengkap 
    ORDER BY nama
");
$kategori = $db->fetchAll("SELECT id, nama_kategori FROM kategori_museum ORDER BY nama_kategori");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Museum - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-layout { display:flex; min-height:100vh; }
        .admin-sidebar {
            width:240px;min-width:240px;
            background:var(--bg-sidebar);
            border-right:1px solid var(--border-color);
            padding:1.5rem 0;
        }
        .admin-main { flex:1; padding:2rem; }
        .sidebar-logo { padding:0 1.5rem 1.5rem; border-bottom:1px solid var(--border-color); margin-bottom:1rem; }
        .sidebar-nav a {
            display:flex;align-items:center;gap:0.75rem;
            padding:0.7rem 1.5rem;color:var(--text-secondary);
            text-decoration:none;font-size:0.88rem;font-weight:500;transition:all 0.2s;
        }
        .sidebar-nav a:hover { color:var(--text-primary);background:rgba(255,255,255,0.03); }
        .sidebar-nav a.active { color:var(--accent-red);background:var(--accent-glow);border-right:2px solid var(--accent-red); }
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
            <a href="museum.php" class="active"><i class="bi bi-building"></i> Data Museum</a>
            <a href="tambah.php"><i class="bi bi-plus-circle"></i> Tambah Museum</a>
            <a href="../peta.php" target="_blank"><i class="bi bi-map"></i> Lihat Peta</a>
            <a href="#" onclick="doLogout()"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </nav>
    </div>
    
    <div class="admin-main">
        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <h4 style="font-weight:700;">Data Museum</h4>
                <p class="text-secondary">Total: <?= count($museums) ?> museum</p>
            </div>
            <a href="tambah.php" class="btn-primary-custom">
                <i class="bi bi-plus-circle"></i> Tambah Museum
            </a>
        </div>
        
        <!-- Alert -->
        <div id="alertBox"></div>
        
        <!-- Filter Search -->
        <div class="mb-3">
            <input type="text" id="searchTable" class="form-control-dark" 
                   placeholder="Cari nama museum..." style="max-width:300px;">
        </div>
        
        <!-- Tabel -->
        <div style="background:var(--bg-card);border:1px solid var(--border-color);border-radius:12px;overflow:hidden;">
            <div style="overflow-x:auto;">
                <table class="table-dark-custom" style="width:100%;" id="museumTable">
                    <thead>
                        <tr>
                            <th style="width:40px;">#</th>
                            <th>Nama Museum</th>
                            <th>Kategori</th>
                            <th>Alamat</th>
                            <th>Koordinat</th>
                            <th>Status</th>
                            <th style="width:150px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($museums as $i => $m): ?>
                        <tr id="row-<?= $m['id'] ?>">
                            <td style="color:var(--text-muted);"><?= $i+1 ?></td>
                            <td>
                                <strong><?= htmlspecialchars($m['nama']) ?></strong>
                                <?php if($m['jam_buka']): ?>
                                <br><small style="color:var(--text-muted);font-size:0.75rem;"><?= htmlspecialchars($m['jam_buka']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge-kategori badge-<?= strtolower($m['kategori']) ?>">
                                    <?= htmlspecialchars($m['kategori']) ?>
                                </span>
                            </td>
                            <td style="font-size:0.82rem;max-width:200px;">
                                <?= htmlspecialchars($m['alamat']) ?>
                            </td>
                            <td style="font-size:0.78rem;color:var(--text-muted);">
                                <?= number_format($m['latitude'], 5) ?>,<br>
                                <?= number_format($m['longitude'], 5) ?>
                            </td>
                            <td>
                                <span style="color:<?= $m['status'] === 'aktif' ? 'var(--success)' : 'var(--text-muted)' ?>;font-size:0.82rem;">
                                    <?= $m['status'] === 'aktif' ? '● Aktif' : '○ Nonaktif' ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="edit.php?id=<?= $m['id'] ?>" 
                                       style="background:rgba(52,152,219,0.15);color:#3498db;border:1px solid #3498db;padding:0.3rem 0.6rem;border-radius:5px;font-size:0.78rem;text-decoration:none;">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button onclick="deleteMuseum(<?= $m['id'] ?>, '<?= htmlspecialchars($m['nama'], ENT_QUOTES) ?>')"
                                            style="background:rgba(231,76,60,0.15);color:#e74c3c;border:1px solid #e74c3c;padding:0.3rem 0.6rem;border-radius:5px;font-size:0.78rem;cursor:pointer;">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Preview Peta -->
<div id="mapModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.8);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:var(--bg-card);border:1px solid var(--border-color);border-radius:12px;width:600px;max-width:95vw;">
        <div style="padding:1rem 1.25rem;border-bottom:1px solid var(--border-color);display:flex;justify-content:space-between;align-items:center;">
            <strong id="mapModalTitle">Lokasi Museum</strong>
            <button onclick="closeMapModal()" style="background:none;border:none;color:var(--text-primary);cursor:pointer;font-size:1.2rem;">&times;</button>
        </div>
        <div id="modalMap" style="height:350px;"></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
// Search filter tabel
document.getElementById('searchTable').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#museumTable tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});

// Hapus museum
function deleteMuseum(id, nama) {
    if (!confirm(`Yakin ingin menonaktifkan museum "${nama}"?`)) return;
    
    fetch(`../api/museum.php?id=${id}`, {method: 'DELETE'})
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showAlert('success', `Museum "${nama}" berhasil dinonaktifkan`);
                document.getElementById('row-' + id).style.opacity = '0.3';
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert('danger', data.message);
            }
        });
}

// Preview peta
let modalMap = null;
function previewOnMap(lat, lon, nama) {
    document.getElementById('mapModal').style.display = 'flex';
    document.getElementById('mapModalTitle').textContent = '📍 ' + nama;
    
    setTimeout(() => {
        if (modalMap) {
            modalMap.remove();
        }
        modalMap = L.map('modalMap').setView([lat, lon], 16);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(modalMap);
        
        const icon = L.divIcon({
            html: '<div style="background:#e63946;width:16px;height:16px;border-radius:50%;border:3px solid white;box-shadow:0 0 10px rgba(230,57,70,0.8);"></div>',
            className: '', iconSize: [16,16], iconAnchor: [8,8]
        });
        
        L.marker([lat, lon], {icon}).addTo(modalMap)
            .bindPopup(`<strong>${nama}</strong><br>Lat: ${lat}<br>Lon: ${lon}`)
            .openPopup();
    }, 100);
}

function closeMapModal() {
    document.getElementById('mapModal').style.display = 'none';
}

function showAlert(type, msg) {
    const icons = {success: 'check-circle', danger: 'exclamation-circle'};
    document.getElementById('alertBox').innerHTML = `
        <div class="alert-custom alert-${type}" style="margin-bottom:1rem;">
            <i class="bi bi-${icons[type]}"></i> ${msg}
        </div>
    `;
}

function doLogout() {
    if (confirm('Yakin ingin logout?')) {
        fetch('../api/auth.php?action=logout').then(() => window.location.href = '../login.php');
    }
}
</script>
</body>
</html>