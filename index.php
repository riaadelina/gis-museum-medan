<?php
require_once 'config/database.php';
session_start();

$db = getDB();

try {
    $stats = $db->fetchAll("SELECT nama_kategori, jumlah_museum FROM mv_statistik_museum ORDER BY jumlah_museum DESC");
    $totalMuseum = $db->fetchOne("SELECT COUNT(*) AS total FROM museum WHERE status = 'aktif'");
    $total = $totalMuseum['total'] ?? 0;
} catch (Exception $e) {
    $stats = [];
    $total = 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Museum Medan GIS - Dashboard</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!--NAVBAR-->
<nav class="navbar navbar-expand-lg navbar-custom sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">
            Museum Medan
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <i class="bi bi-list text-light fs-4"></i>
        </button>
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto ms-3">
                <li class="nav-item">
                    <a class="nav-link active" href="index.php">
                        <i class="bi bi-house"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="peta.php">
                        <i class="bi bi-map"></i> Peta Museum
                    </a>
                </li>
            </ul>
            <div class="d-flex align-items-center gap-2">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <span class="text-secondary small">
                        <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['username']) ?>
                    </span>
                    <a href="admin/index.php" class="btn-primary-custom btn-sm">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn-outline-custom btn-sm" style="padding: 0.45rem 1rem; font-size: 0.85rem;">
                        <i class="bi bi-lock"></i> Login Admin
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!--HERO SECTION-->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <div class="section-badge mb-3">
                    <i class="bi bi-geo-alt-fill me-1"></i> Sistem Informasi Geografis
                </div>
                <h1 class="hero-title">
                    Pemetaan
                    <span class="highlight">Museum</span> di<br>
                    Kota Medan
                </h1>
                <p class="hero-subtitle">
                    Jelajahi berbagai museum di Kota Medan melalui peta interaktif yang menyediakan informasi lokasi dan persebaran museum untuk memudahkan pencarian dan eksplorasi
                </p>
                <div class="d-flex gap-3 flex-wrap">
                    <a href="peta.php" class="btn-primary-custom">
                        <i class="bi bi-map-fill"></i> Lihat Peta
                    </a>
                </div>
                
                <!-- Tech Stack Tags -->
                <div class="d-flex gap-2 flex-wrap mt-4">
                    <span style="background:#1a1a1a;border:1px solid #333;padding:0.3rem 0.8rem;border-radius:20px;font-size:0.78rem;color:#a0a0a0;">
                        <i class="bi bi-database"></i> PostGIS
                    </span>
                    <span style="background:#1a1a1a;border:1px solid #333;padding:0.3rem 0.8rem;border-radius:20px;font-size:0.78rem;color:#a0a0a0;">
                        <i class="bi bi-map"></i> Leaflet.js
                    </span>
                    <span style="background:#1a1a1a;border:1px solid #333;padding:0.3rem 0.8rem;border-radius:20px;font-size:0.78rem;color:#a0a0a0;">
                        <i class="bi bi-filetype-php"></i> PHP Native
                    </span>
                    <span style="background:#1a1a1a;border:1px solid #333;padding:0.3rem 0.8rem;border-radius:20px;font-size:0.78rem;color:#a0a0a0;">
                        <i class="bi bi-geo-alt"></i> Vector Point
                    </span>
                </div>
            </div>
            <div class="col-lg-6 mt-4 mt-lg-0">
                <!-- Mini Peta Preview -->
                <div class="map-container" style="height: 380px;">
                    <div id="mapPreview" style="height:100%;width:100%;"></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!--STATISTIK-->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-4">
            <div class="section-badge">Data Museum</div>
            <h2 class="section-title">Statistik Museum Kota Medan</h2>
            <p class="section-subtitle">Data realtime dari database PostgreSQL/PostGIS</p>
        </div>
        
        <div class="row g-4 justify-content-center">
            <!-- Total Museum -->
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon">🏛️</div>
                    <div class="stat-number"><?= $total ?></div>
                    <div class="stat-label">Total Museum</div>
                </div>
            </div>
            <?php foreach($stats as $stat): ?>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon">
                        <?php
                        $icons = ['Sejarah'=>'📜','Seni'=>'🎨','Edukasi'=>'📚','Alam'=>'🌿','Militer'=>'⚔️'];
                        echo $icons[$stat['nama_kategori']] ?? '🏛️';
                        ?>
                    </div>
                    <div class="stat-number"><?= $stat['jumlah_museum'] ?></div>
                    <div class="stat-label">Museum <?= htmlspecialchars($stat['nama_kategori']) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!--INFORMASI SISTEM-->
<section class="py-5" style="background: var(--bg-secondary);">
    <div class="container">
        <div class="text-center mb-4">
            <div class="section-badge">Teknologi</div>
            <h2 class="section-title">Dibangun dengan Teknologi GIS</h2>
            <p class="section-subtitle">Menggunakan stack teknologi modern untuk analisis spasial yang akurat</p>
        </div>
        
        <div class="row g-3">
            <div class="col-lg-3 col-md-6">
                <div class="info-card">
                    <div class="info-icon">🗄️</div>
                    <div class="info-title">PostgreSQL + PostGIS</div>
                    <div class="info-desc">Database spasial untuk menyimpan dan menganalisis data geografis dengan fungsi ST_Distance, ST_DWithin, dan KNN</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="info-card">
                    <div class="info-icon">📍</div>
                    <div class="info-title">Vector Point</div>
                    <div class="info-desc">Representasi lokasi museum menggunakan tipe geometri Point (koordinat latitude/longitude) dengan SRID 4326</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="info-card">
                    <div class="info-icon">🗺️</div>
                    <div class="info-title">Leaflet.js</div>
                    <div class="info-desc">Library JavaScript open-source untuk menampilkan peta interaktif, marker, popup, dan lingkaran radius</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="info-card">
                    <div class="info-icon">🔒</div>
                    <div class="info-title">PHP Native + PDO</div>
                    <div class="info-desc">Backend API menggunakan PHP native dengan PDO untuk koneksi database yang aman dan parameterized query</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!--FOOTER-->
<footer class="footer-custom">
    <div class="container">
        <p><strong>Museum Medan</strong> — Tugas Akhir Sistem Informasi Geografis</p>
        <p class="mt-1">Dibangun dengan PHP Native · PostgreSQL · PostGIS · Leaflet.js</p>
    </div>
</footer>

<!--Scripts-->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
const mapPreview = L.map('mapPreview', {
    center: [3.5952, 98.6722],  
    zoom: 12,
    zoomControl: true,
    scrollWheelZoom: false      
});

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 18
}).addTo(mapPreview);

fetch('api/museum.php?action=geojson')
    .then(res => res.json())
    .then(geojson => {
        const redIcon = L.divIcon({
            html: '<div style="background:#e63946;width:12px;height:12px;border-radius:50%;border:2px solid white;box-shadow:0 0 6px rgba(230,57,70,0.7);"></div>',
            className: '',
            iconSize: [12, 12],
            iconAnchor: [6, 6]
        });
        
        L.geoJSON(geojson, {
            pointToLayer: (feature, latlng) => L.marker(latlng, {icon: redIcon}),
            onEachFeature: (feature, layer) => {
                const p = feature.properties;
                layer.bindPopup(`
                    <div class="popup-nama">${p.nama}</div>
                    <div class="popup-info">📍 ${p.alamat}</div>
                    <div class="popup-info">🏷️ ${p.kategori}</div>
                `);
            }
        }).addTo(mapPreview);
    })
    .catch(err => console.error('Gagal load museum:', err));
</script>
</body>
</html>