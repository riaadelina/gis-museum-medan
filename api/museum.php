<?php

require_once '../config/database.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            handleGet($action);
            break;
        case 'POST':
            handlePost($action);
            break;
        case 'PUT':
            handlePut($action);
            break;
        case 'DELETE':
            handleDelete($action);
            break;
        default:
            jsonResponse(false, 'Method tidak diizinkan', null, 405);
    }
} catch (Exception $e) {
    jsonResponse(false, 'Server error: ' . $e->getMessage(), null, 500);
}

function handleGet($action) {
    $db = getDB();
    
    switch ($action) {
        case 'geojson':
            getMuseumGeoJSON($db);
            break;
        
        case 'detail':
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            if (!$id) jsonResponse(false, 'ID tidak valid', null, 400);
            getMuseumById($db, $id);
            break;
        
        case 'list':
        default:
            getAllMuseum($db);
            break;
    }
}

function getMuseumGeoJSON($db) {
    $sql = "
        SELECT 
            id,
            nama,
            alamat,
            deskripsi,
            telepon,
            jam_buka,
            harga_tiket,
            kategori,
            id_kategori,
            latitude,
            longitude,
            foto,
            status,
            geojson
        FROM v_museum_lengkap
        ORDER BY nama
    ";
    
    $museums = $db->fetchAll($sql);
    
    $features = [];
    foreach ($museums as $museum) {
        $features[] = [
            'type' => 'Feature',
            'geometry' => json_decode($museum['geojson']),
            'properties' => [
                'id'          => $museum['id'],
                'nama'        => $museum['nama'],
                'alamat'      => $museum['alamat'],
                'deskripsi'   => $museum['deskripsi'],
                'telepon'     => $museum['telepon'],
                'jam_buka'    => $museum['jam_buka'],
                'harga_tiket' => $museum['harga_tiket'],
                'kategori'    => $museum['kategori'],
                'id_kategori' => $museum['id_kategori'],
                'latitude'    => (float)$museum['latitude'],
                'longitude'   => (float)$museum['longitude'],
                'foto'        => $museum['foto'],
            ]
        ];
    }
    
    $geojson = [
        'type'     => 'FeatureCollection',
        'features' => $features
    ];
    
    header('Content-Type: application/json');
    echo json_encode($geojson, JSON_UNESCAPED_UNICODE);
    exit;
}

function getAllMuseum($db) {
    $sql = "
        SELECT 
            id, nama, alamat, kategori, id_kategori,
            latitude, longitude, status, jam_buka,
            harga_tiket, telepon, foto, created_at
        FROM v_museum_lengkap
        ORDER BY nama
    ";
    
    $museums = $db->fetchAll($sql);
    
    $stats = $db->fetchAll("
        SELECT nama_kategori, jumlah_museum 
        FROM mv_statistik_museum
        ORDER BY jumlah_museum DESC
    ");
    
    jsonResponse(true, 'Berhasil', [
        'museums' => $museums,
        'total'   => count($museums),
        'stats'   => $stats
    ]);
}

function getMuseumById($db, $id) {
    $sql = "
        SELECT 
            id, nama, alamat, deskripsi, kategori, id_kategori,
            telepon, jam_buka, harga_tiket, latitude, longitude,
            foto, status, created_at, updated_at
        FROM v_museum_lengkap
        WHERE id = :id
    ";
    
    $museum = $db->fetchOne($sql, [':id' => $id]);
    
    if (!$museum) {
        jsonResponse(false, 'Museum tidak ditemukan', null, 404);
    }
    
    jsonResponse(true, 'Berhasil', $museum);
}

function handlePost($action) {
    requireAdmin();
    
    $db = getDB();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $input = $_POST;
    }
    
    $required = ['nama', 'alamat', 'id_kategori', 'latitude', 'longitude'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            jsonResponse(false, "Field '$field' wajib diisi", null, 400);
        }
    }
    
    $nama        = sanitize($input['nama']);
    $alamat      = sanitize($input['alamat']);
    $deskripsi   = sanitize($input['deskripsi'] ?? '');
    $telepon     = sanitize($input['telepon'] ?? '');
    $jam_buka    = sanitize($input['jam_buka'] ?? '');
    $harga_tiket = sanitize($input['harga_tiket'] ?? '');
    $id_kategori = filter_var($input['id_kategori'], FILTER_VALIDATE_INT);
    $latitude    = filter_var($input['latitude'], FILTER_VALIDATE_FLOAT);
    $longitude   = filter_var($input['longitude'], FILTER_VALIDATE_FLOAT);
    
    if (!isValidCoordinate($latitude) || !isValidCoordinate($longitude)) {
        jsonResponse(false, 'Koordinat tidak valid', null, 400);
    }
    
    if ($latitude < 3.0 || $latitude > 4.0 || $longitude < 98.0 || $longitude > 99.0) {
        jsonResponse(false, 'Koordinat di luar wilayah Medan', null, 400);
    }
    

    $sql = "
        INSERT INTO museum 
            (nama, alamat, deskripsi, telepon, jam_buka, harga_tiket, id_kategori, geom)
        VALUES 
            (:nama, :alamat, :deskripsi, :telepon, :jam_buka, :harga_tiket, :id_kategori,
             ST_SetSRID(ST_MakePoint(:longitude, :latitude), 4326))
        RETURNING id
    ";
    
    $stmt = $db->query($sql, [
        ':nama'        => $nama,
        ':alamat'      => $alamat,
        ':deskripsi'   => $deskripsi,
        ':telepon'     => $telepon,
        ':jam_buka'    => $jam_buka,
        ':harga_tiket' => $harga_tiket,
        ':id_kategori' => $id_kategori,
        ':longitude'   => $longitude,
        ':latitude'    => $latitude,
    ]);
    
    $result = $stmt->fetch();
    
    $db->query("REFRESH MATERIALIZED VIEW mv_statistik_museum");
    
    jsonResponse(true, 'Museum berhasil ditambahkan', [
        'id' => $result['id']
    ], 201);
}

function handlePut($action) {
    requireAdmin();
    
    $db = getDB();
    
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$id) jsonResponse(false, 'ID tidak valid', null, 400);
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $required = ['nama', 'alamat', 'id_kategori', 'latitude', 'longitude'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            jsonResponse(false, "Field '$field' wajib diisi", null, 400);
        }
    }
    
    $nama        = sanitize($input['nama']);
    $alamat      = sanitize($input['alamat']);
    $deskripsi   = sanitize($input['deskripsi'] ?? '');
    $telepon     = sanitize($input['telepon'] ?? '');
    $jam_buka    = sanitize($input['jam_buka'] ?? '');
    $harga_tiket = sanitize($input['harga_tiket'] ?? '');
    $id_kategori = filter_var($input['id_kategori'], FILTER_VALIDATE_INT);
    $latitude    = filter_var($input['latitude'], FILTER_VALIDATE_FLOAT);
    $longitude   = filter_var($input['longitude'], FILTER_VALIDATE_FLOAT);
    
    if (!isValidCoordinate($latitude) || !isValidCoordinate($longitude)) {
        jsonResponse(false, 'Koordinat tidak valid', null, 400);
    }
    
    $sql = "
        UPDATE museum SET
            nama        = :nama,
            alamat      = :alamat,
            deskripsi   = :deskripsi,
            telepon     = :telepon,
            jam_buka    = :jam_buka,
            harga_tiket = :harga_tiket,
            id_kategori = :id_kategori,
            geom        = ST_SetSRID(ST_MakePoint(:longitude, :latitude), 4326),
            updated_at  = CURRENT_TIMESTAMP
        WHERE id = :id
    ";
    
    $db->query($sql, [
        ':nama'        => $nama,
        ':alamat'      => $alamat,
        ':deskripsi'   => $deskripsi,
        ':telepon'     => $telepon,
        ':jam_buka'    => $jam_buka,
        ':harga_tiket' => $harga_tiket,
        ':id_kategori' => $id_kategori,
        ':longitude'   => $longitude,
        ':latitude'    => $latitude,
        ':id'          => $id,
    ]);
    
    $db->query("REFRESH MATERIALIZED VIEW mv_statistik_museum");
    
    jsonResponse(true, 'Museum berhasil diperbarui');
}

function handleDelete($action) {
    requireAdmin();
    
    $db = getDB();
    
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$id) jsonResponse(false, 'ID tidak valid', null, 400);
    
    $sql = "UPDATE museum SET status = 'nonaktif', updated_at = CURRENT_TIMESTAMP WHERE id = :id";
    $db->query($sql, [':id' => $id]);
    
    $db->query("REFRESH MATERIALIZED VIEW mv_statistik_museum");
    
    jsonResponse(true, 'Museum berhasil dihapus');
}

function requireAdmin() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        jsonResponse(false, 'Akses ditolak. Silakan login sebagai admin.', null, 401);
    }
}
?>