<?php
require_once 'config/database.php';
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: admin/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Museum Medan GIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: radial-gradient(circle at 30% 50%, rgba(230,57,70,0.1) 0%, transparent 60%),
                        var(--bg-primary);
        }
        .login-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 2.5rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        }
        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-logo .logo-icon {
            font-size: 3rem;
            display: block;
            margin-bottom: 0.5rem;
        }
        .login-logo h4 {
            font-weight: 700;
            color: var(--text-primary);
        }
        .login-logo p {
            font-size: 0.85rem;
            color: var(--text-muted);
        }
    </style>
</head>
<body>
<div class="login-card">
    <div class="login-logo">
        <span class="logo-icon">
            <i class="bi bi-map"></i>
        </span>
        <h4>Museum Medan</h4>
        <p>Login sebagai Administrator</p>
    </div>
    
    <div id="alertBox"></div>
    
    <div class="mb-3">
        <label class="text-secondary mb-1" style="font-size:0.85rem;">Username</label>
        <input type="text" id="username" class="form-control-dark" placeholder="Masukkan username" autocomplete="username">
    </div>
    <div class="mb-4">
        <label class="text-secondary mb-1" style="font-size:0.85rem;">Password</label>
        <div style="position:relative;">
            <input type="password" id="password" class="form-control-dark" placeholder="Masukkan password" autocomplete="current-password">
            <button onclick="togglePassword()" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;">
                <i class="bi bi-eye" id="eyeIcon"></i>
            </button>
        </div>
    </div>
    
    <button class="btn-primary-custom w-100" onclick="doLogin()" id="loginBtn" style="justify-content:center;">
        <i class="bi bi-box-arrow-in-right"></i> Masuk
    </button>
    
    <div class="text-center mt-3">
        <a href="index.php" style="color:var(--text-muted);font-size:0.85rem;text-decoration:none;">
            <i class="bi bi-arrow-left"></i> Kembali ke Beranda
        </a>
    </div>
</div>

<script>
function togglePassword() {
    const pw = document.getElementById('password');
    const icon = document.getElementById('eyeIcon');
    if (pw.type === 'password') {
        pw.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        pw.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

function doLogin() {
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;
    const btn = document.getElementById('loginBtn');
    
    if (!username || !password) {
        showAlert('danger', 'Username dan password wajib diisi');
        return;
    }
    
    btn.innerHTML = '<i class="bi bi-hourglass"></i> Memproses...';
    btn.disabled = true;
    
    fetch('api/auth.php?action=login', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({username, password})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showAlert('success', 'Login berhasil!');
            setTimeout(() => window.location.href = 'admin/index.php', 1000);
        } else {
            showAlert('danger', data.message);
            btn.innerHTML = '<i class="bi bi-box-arrow-in-right"></i> Masuk';
            btn.disabled = false;
        }
    })
    .catch(() => {
        showAlert('danger', 'Gagal menghubungi server');
        btn.innerHTML = '<i class="bi bi-box-arrow-in-right"></i> Masuk';
        btn.disabled = false;
    });
}

function showAlert(type, msg) {
    const icons = {success: 'check-circle', danger: 'exclamation-circle', info: 'info-circle'};
    document.getElementById('alertBox').innerHTML = `
        <div class="alert-custom alert-${type}" style="margin-bottom:1rem;">
            <i class="bi bi-${icons[type]}"></i> ${msg}
        </div>
    `;
}

document.getElementById('password').addEventListener('keypress', e => {
    if (e.key === 'Enter') doLogin();
});
</script>
</body>
</html>