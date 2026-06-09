<?php
require_once '../config/database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$db = getDB();

// Statistik untuk dashboard
$totalMuseum    = $db->fetchOne("SELECT COUNT(*) AS total FROM museum WHERE status = 'aktif'")['total'];
$totalNonaktif  = $db->fetchOne("SELECT COUNT(*) AS total FROM museum WHERE status = 'nonaktif'")['total'];
$statsKategori  = $db->fetchAll("SELECT nama_kategori, jumlah_museum FROM mv_statistik_museum ORDER BY jumlah_museum DESC");
$recentMuseum   = $db->fetchAll("SELECT nama, kategori, created_at FROM v_museum_lengkap ORDER BY created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Museum Medan GIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-layout { display: flex; min-height: 100vh; }
        .admin-sidebar {
            width: 240px; min-width: 240px;
            background: var(--bg-sidebar);
            border-right: 1px solid var(--border-color);
            padding: 1.5rem 0;
            display: flex; flex-direction: column;
        }
        .admin-main { flex: 1; padding: 2rem; background: var(--bg-primary); }
        .sidebar-logo {
            padding: 0 1.5rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 1rem;
        }
        .sidebar-logo h6 { font-weight: 700; color: var(--text-primary); margin: 0; }
        .sidebar-logo small { color: var(--text-muted); font-size: 0.75rem; }
        .sidebar-nav a {
            display: flex; align-items: center; gap: 0.75rem;
            padding: 0.7rem 1.5rem;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.88rem; font-weight: 500;
            transition: all 0.2s;
        }
        .sidebar-nav a:hover { color: var(--text-primary); background: rgba(255,255,255,0.03); }
        .sidebar-nav a.active { color: var(--accent-red); background: var(--accent-glow); border-right: 2px solid var(--accent-red); }
        .sidebar-nav i { width: 18px; text-align: center; }
        .sidebar-bottom { margin-top: auto; padding: 1rem 0; border-top: 1px solid var(--border-color); }
    </style>
</head>
<body>
<div class="admin-layout">
    
    <!-- SIDEBAR -->
    <div class="admin-sidebar">
        <div class="sidebar-logo">
            <h6>Museum Medan</h6>
            <small>Admin Panel</small>
        </div>
        
        <nav class="sidebar-nav">
            <a href="index.php" class="active">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="museum.php">
                <i class="bi bi-building"></i> Data Museum
            </a>
            <a href="tambah.php">
                <i class="bi bi-plus-circle"></i> Tambah Museum
            </a>
            <a href="../peta.php" target="_blank">
                <i class="bi bi-map"></i> Lihat Peta
            </a>
        </nav>
        
        <div class="sidebar-bottom">
            <nav class="sidebar-nav">
                <a href="../index.php">
                    <i class="bi bi-house"></i> Beranda
                </a>
                <a href="#" onclick="doLogout()">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </nav>
        </div>
    </div>
    
    <!-- MAIN CONTENT -->
    <div class="admin-main">
        <div class="mb-4">
            <h4 style="font-weight:700;">Dashboard Admin</h4>
            <p class="text-secondary">Selamat datang, Kelola data museum Kota Medan di sini</p>
        </div>
        
        <!-- Statistik -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?= $totalMuseum ?></div>
                    <div class="stat-label">Museum Aktif</div>
                </div>
            </div>
            <?php foreach($statsKategori as $s): ?>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number" style="font-size:2rem;"><?= $s['jumlah_museum'] ?></div>
                    <div class="stat-label">Museum <?= htmlspecialchars($s['nama_kategori']) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
            <div class="col-md-3">
                <div class="stat-card" style="--accent-red:#666;">
                    <div class="stat-number" style="color:#666;"><?= $totalNonaktif ?></div>
                    <div class="stat-label">Museum Nonaktif</div>
                </div>
            </div>
        </div>
        
        <!-- Aksi Cepat -->
        <div class="row g-3 mb-4">
            <div class="col-12">
                <div style="background:var(--bg-card);border:1px solid var(--border-color);border-radius:12px;padding:1.5rem;">
                    <h6 style="font-weight:700;margin-bottom:1rem;">Aksi Cepat</h6>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="tambah.php" class="btn-primary-custom">
                            <i class="bi bi-plus-circle"></i> Tambah Museum
                        </a>
                        <a href="museum.php" class="btn-outline-custom">
                            <i class="bi bi-table"></i> Kelola Museum
                        </a>
                        <a href="../peta.php" target="_blank" class="btn-outline-custom">
                            <i class="bi bi-map"></i> Lihat Peta
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Museum Terbaru -->
        <div style="background:var(--bg-card);border:1px solid var(--border-color);border-radius:12px;padding:1.5rem;">
            <h6 style="font-weight:700;margin-bottom:1rem;">Museum Terbaru Ditambahkan</h6>
            <table class="table-dark-custom" style="width:100%;">
                <thead>
                    <tr>
                        <th>Nama Museum</th>
                        <th>Kategori</th>
                        <th>Tanggal Tambah</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($recentMuseum as $m): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($m['nama']) ?></strong></td>
                        <td>
                            <span class="badge-kategori badge-<?= strtolower($m['kategori']) ?>">
                                <?= htmlspecialchars($m['kategori']) ?>
                            </span>
                        </td>
                        <td style="color:var(--text-muted);font-size:0.82rem;">
                            <?= date('d M Y', strtotime($m['created_at'])) ?>
                        </td>
                        <td>
                            <a href="museum.php" style="color:var(--accent-red);font-size:0.82rem;">Kelola →</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function doLogout() {
    if (confirm('Yakin ingin logout?')) {
        fetch('../api/auth.php?action=logout')
            .then(() => window.location.href = '../login.php');
    }
}
</script>
</body>
</html>