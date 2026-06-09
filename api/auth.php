<?php

require_once '../config/database.php';
session_start();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'login';

if ($method === 'POST' && $action === 'login') {
    handleLogin();
} elseif ($action === 'logout') {
    handleLogout();
} elseif ($action === 'check') {
    checkAuth();
} else {
    jsonResponse(false, 'Action tidak valid', null, 400);
}

function handleLogin() {
    $db = getDB();
    
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) $input = $_POST;
    
    $username = sanitize($input['username'] ?? '');
    $password = $input['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        jsonResponse(false, 'Username dan password wajib diisi', null, 400);
    }
    
    $sql = "SELECT id, username, password, role FROM users WHERE username = :username";
    $user = $db->fetchOne($sql, [':username' => $username]);
    
    if (!$user) {
        jsonResponse(false, 'Username atau password salah', null, 401);
    }
    
    if (!password_verify($password, $user['password'])) {
        jsonResponse(false, 'Username atau password salah', null, 401);
    }
    
    $_SESSION['user_id']  = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role']     = $user['role'];
    
    jsonResponse(true, 'Login berhasil', [
        'user_id'  => $user['id'],
        'username' => $user['username'],
        'role'     => $user['role']
    ]);
}

function handleLogout() {
    session_destroy();
    jsonResponse(true, 'Logout berhasil');
}

function checkAuth() {
    if (isset($_SESSION['user_id'])) {
        jsonResponse(true, 'Authenticated', [
            'user_id'  => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role'     => $_SESSION['role']
        ]);
    } else {
        jsonResponse(false, 'Tidak terautentikasi', null, 401);
    }
}
?>