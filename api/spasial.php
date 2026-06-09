<?php

require_once '../config/database.php';

session_start();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    if ($method === 'GET') {
        switch ($action) {
            case 'nearest':
                getNearestMuseum();
                break;
            case 'radius':
                getMuseumInRadius();
                break;
            case 'distance':
                calculateDistance();
                break;
            case 'geocode':
                geocodeAddress();
                break;
            case 'explain':
                explainQuery();
                break;
            default:
                jsonResponse(false, 'Action tidak dikenali', null, 400);
        }
    } else {
        jsonResponse(false, 'Method tidak diizinkan', null, 405);
    }
} catch (Exception $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage(), null, 500);
}

function getNearestMuseum() {
    $db = getDB();
    
    $lat   = filter_input(INPUT_GET, 'lat', FILTER_VALIDATE_FLOAT);
    $lon   = filter_input(INPUT_GET, 'lon', FILTER_VALIDATE_FLOAT);
    $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?? 5;
    
    if (!$lat || !$lon) {
        jsonResponse(false, 'Parameter lat dan lon wajib diisi', null, 400);
    }
    
    $limit = min((int)$limit, 20);
    
    $sql = "
        SELECT 
            m.id,
            m.nama,
            m.alamat,
            k.nama_kategori AS kategori,
            m.jam_buka,
            m.harga_tiket,
            ST_Y(m.geom) AS latitude,
            ST_X(m.geom) AS longitude,
            -- Hitung jarak dalam meter menggunakan geography
            ROUND(
                ST_Distance(
                    m.geom::geography,
                    ST_SetSRID(ST_MakePoint(:lon, :lat), 4326)::geography
                )::numeric,
                2
            ) AS jarak_meter,
            -- Konversi ke kilometer untuk tampilan
            ROUND(
                ST_Distance(
                    m.geom::geography,
                    ST_SetSRID(ST_MakePoint(:lon2, :lat2), 4326)::geography
                )::numeric / 1000,
                3
            ) AS jarak_km
        FROM museum m
        JOIN kategori_museum k ON m.id_kategori = k.id
        WHERE m.status = 'aktif'
        -- KNN operator: urutkan berdasarkan kedekatan jarak
        -- Ini sangat efisien karena menggunakan GIST spatial index
        ORDER BY m.geom <-> ST_SetSRID(ST_MakePoint(:lon3, :lat3), 4326)
        LIMIT :limit
    ";
    
    $results = $db->fetchAll($sql, [
        ':lat'   => $lat,   ':lon'   => $lon,
        ':lat2'  => $lat,   ':lon2'  => $lon,
        ':lat3'  => $lat,   ':lon3'  => $lon,
        ':limit' => $limit,
    ]);
    
    $queryDisplay = "SELECT nama, alamat,
    ROUND(ST_Distance(
        geom::geography,
        ST_SetSRID(ST_MakePoint($lon, $lat), 4326)::geography
    )::numeric / 1000, 3) AS jarak_km
FROM museum
WHERE status = 'aktif'
ORDER BY geom <-> ST_SetSRID(ST_MakePoint($lon, $lat), 4326)
LIMIT $limit;";
    
    jsonResponse(true, 'Berhasil', [
        'user_location' => ['lat' => $lat, 'lon' => $lon],
        'museums'       => $results,
        'total'         => count($results),
        'query'         => $queryDisplay,
        'penjelasan'    => 'Operator <-> menggunakan KNN (K-Nearest Neighbor) dengan GIST spatial index untuk menemukan museum terdekat secara efisien. ST_Distance dengan cast ::geography menghitung jarak dalam meter menggunakan model ellipsoid bumi.'
    ]);
}

function getMuseumInRadius() {
    $db = getDB();
    
    $lat    = filter_input(INPUT_GET, 'lat', FILTER_VALIDATE_FLOAT);
    $lon    = filter_input(INPUT_GET, 'lon', FILTER_VALIDATE_FLOAT);
    $radius = filter_input(INPUT_GET, 'radius', FILTER_VALIDATE_INT); // dalam meter
    
    if (!$lat || !$lon || !$radius) {
        jsonResponse(false, 'Parameter lat, lon, dan radius wajib diisi', null, 400);
    }
    
    $radius = min((int)$radius, 50000);
    
    $sql = "
        SELECT 
            m.id,
            m.nama,
            m.alamat,
            k.nama_kategori AS kategori,
            m.jam_buka,
            m.harga_tiket,
            ST_Y(m.geom) AS latitude,
            ST_X(m.geom) AS longitude,
            ROUND(
                ST_Distance(
                    m.geom::geography,
                    ST_SetSRID(ST_MakePoint(:lon, :lat), 4326)::geography
                )::numeric,
                2
            ) AS jarak_meter,
            ROUND(
                ST_Distance(
                    m.geom::geography,
                    ST_SetSRID(ST_MakePoint(:lon2, :lat2), 4326)::geography
                )::numeric / 1000,
                3
            ) AS jarak_km,
            -- Persentase dari radius (untuk visualisasi)
            ROUND(
                (ST_Distance(
                    m.geom::geography,
                    ST_SetSRID(ST_MakePoint(:lon3, :lat3), 4326)::geography
                ) / :radius * 100)::numeric,
                1
            ) AS persen_radius
        FROM museum m
        JOIN kategori_museum k ON m.id_kategori = k.id
        WHERE m.status = 'aktif'
        -- ST_DWithin untuk filter radius, efisien dengan GIST index
        AND ST_DWithin(
            m.geom::geography,
            ST_SetSRID(ST_MakePoint(:lon4, :lat4), 4326)::geography,
            :radius2
        )
        ORDER BY jarak_meter ASC
    ";
    
    $results = $db->fetchAll($sql, [
        ':lat'     => $lat,    ':lon'     => $lon,
        ':lat2'    => $lat,    ':lon2'    => $lon,
        ':lat3'    => $lat,    ':lon3'    => $lon,
        ':lat4'    => $lat,    ':lon4'    => $lon,
        ':radius'  => $radius, ':radius2' => $radius,
    ]);
    
    $queryDisplay = "SELECT nama, alamat,
    ROUND(ST_Distance(
        geom::geography,
        ST_SetSRID(ST_MakePoint($lon, $lat), 4326)::geography
    )::numeric / 1000, 3) AS jarak_km
FROM museum
WHERE status = 'aktif'
AND ST_DWithin(
    geom::geography,
    ST_SetSRID(ST_MakePoint($lon, $lat), 4326)::geography,
    $radius  
)
ORDER BY jarak_km ASC;";
    
    jsonResponse(true, 'Berhasil', [
        'user_location' => ['lat' => $lat, 'lon' => $lon],
        'radius_meter'  => $radius,
        'radius_km'     => $radius / 1000,
        'museums'       => $results,
        'total'         => count($results),
        'query'         => $queryDisplay,
        'penjelasan'    => "ST_DWithin() memeriksa apakah jarak antara dua geometri ≤ nilai radius yang ditentukan. Dengan cast ::geography, jarak diukur dalam meter menggunakan model spheroid bumi. Fungsi ini memanfaatkan GIST spatial index sehingga sangat efisien."
    ]);
}

function calculateDistance() {
    $db = getDB();
    
    $museum_a_id = filter_input(INPUT_GET, 'museum_a', FILTER_VALIDATE_INT);
    $museum_b_id = filter_input(INPUT_GET, 'museum_b', FILTER_VALIDATE_INT);
    
    $lat_a = filter_input(INPUT_GET, 'lat_a', FILTER_VALIDATE_FLOAT);
    $lon_a = filter_input(INPUT_GET, 'lon_a', FILTER_VALIDATE_FLOAT);
    $lat_b = filter_input(INPUT_GET, 'lat_b', FILTER_VALIDATE_FLOAT);
    $lon_b = filter_input(INPUT_GET, 'lon_b', FILTER_VALIDATE_FLOAT);
    
    if ($museum_a_id && $museum_b_id) {
        $sql = "
            SELECT 
                a.nama AS nama_museum_a,
                a.alamat AS alamat_a,
                ST_Y(a.geom) AS lat_a,
                ST_X(a.geom) AS lon_a,
                b.nama AS nama_museum_b,
                b.alamat AS alamat_b,
                ST_Y(b.geom) AS lat_b,
                ST_X(b.geom) AS lon_b,
                -- Hitung jarak dalam meter
                ROUND(
                    ST_Distance(a.geom::geography, b.geom::geography)::numeric,
                    2
                ) AS jarak_meter,
                -- Hitung jarak dalam kilometer
                ROUND(
                    ST_Distance(a.geom::geography, b.geom::geography)::numeric / 1000,
                    3
                ) AS jarak_km
            FROM museum a, museum b
            WHERE a.id = :id_a AND b.id = :id_b
            AND a.status = 'aktif' AND b.status = 'aktif'
        ";
        
        $result = $db->fetchOne($sql, [':id_a' => $museum_a_id, ':id_b' => $museum_b_id]);
        
        if (!$result) {
            jsonResponse(false, 'Museum tidak ditemukan', null, 404);
        }
        
        $queryDisplay = "SELECT 
    a.nama AS museum_a,
    b.nama AS museum_b,
    ROUND(ST_Distance(
        a.geom::geography, 
        b.geom::geography
    )::numeric / 1000, 3) AS jarak_km
FROM museum a, museum b
WHERE a.id = $museum_a_id AND b.id = $museum_b_id;";
        
        jsonResponse(true, 'Berhasil', [
            'hasil'      => $result,
            'query'      => $queryDisplay,
            'penjelasan' => 'ST_Distance() menghitung jarak terpendek antara dua geometri. Dengan cast ::geography, jarak dihitung dalam meter menggunakan model ellipsoid bumi (lebih akurat dari flat earth geometry).'
        ]);
        
    } elseif ($lat_a && $lon_a && $lat_b && $lon_b) {
        $sql = "
            SELECT 
                ROUND(
                    ST_Distance(
                        ST_SetSRID(ST_MakePoint(:lon_a, :lat_a), 4326)::geography,
                        ST_SetSRID(ST_MakePoint(:lon_b, :lat_b), 4326)::geography
                    )::numeric,
                    2
                ) AS jarak_meter,
                ROUND(
                    ST_Distance(
                        ST_SetSRID(ST_MakePoint(:lon_a2, :lat_a2), 4326)::geography,
                        ST_SetSRID(ST_MakePoint(:lon_b2, :lat_b2), 4326)::geography
                    )::numeric / 1000,
                    3
                ) AS jarak_km
        ";
        
        $result = $db->fetchOne($sql, [
            ':lat_a'  => $lat_a, ':lon_a'  => $lon_a,
            ':lat_b'  => $lat_b, ':lon_b'  => $lon_b,
            ':lat_a2' => $lat_a, ':lon_a2' => $lon_a,
            ':lat_b2' => $lat_b, ':lon_b2' => $lon_b,
        ]);
        
        $queryDisplay = "SELECT ROUND(
    ST_Distance(
        ST_SetSRID(ST_MakePoint($lon_a, $lat_a), 4326)::geography,
        ST_SetSRID(ST_MakePoint($lon_b, $lat_b), 4326)::geography
    )::numeric / 1000, 3
) AS jarak_km;";
        
        jsonResponse(true, 'Berhasil', [
            'titik_a'    => ['lat' => $lat_a, 'lon' => $lon_a],
            'titik_b'    => ['lat' => $lat_b, 'lon' => $lon_b],
            'jarak_meter'=> $result['jarak_meter'],
            'jarak_km'   => $result['jarak_km'],
            'query'      => $queryDisplay,
            'penjelasan' => 'ST_MakePoint(longitude, latitude) membuat titik geometri dari koordinat. Cast ::geography menggunakan model bumi spheroid untuk perhitungan jarak yang akurat.'
        ]);
        
    } else {
        jsonResponse(false, 'Parameter tidak lengkap. Gunakan museum_a & museum_b (ID), atau lat_a, lon_a, lat_b, lon_b', null, 400);
    }
}

function geocodeAddress() {
    $address = filter_input(INPUT_GET, 'address', FILTER_SANITIZE_SPECIAL_CHARS);
    
    if (empty($address)) {
        jsonResponse(false, 'Alamat wajib diisi', null, 400);
    }
    
    $query = urlencode($address . ', Medan, Sumatera Utara, Indonesia');
    
    $url = "https://nominatim.openstreetmap.org/search?q={$query}&format=json&limit=5&addressdetails=1";
    
    $context = stream_context_create([
        'http' => [
            'header' => "User-Agent: MuseumMedanGIS/1.0 (tugas-sig@university.ac.id)\r\n",
            'timeout' => 10
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        jsonResponse(false, 'Gagal menghubungi layanan geocoding', null, 500);
    }
    
    $results = json_decode($response, true);
    
    if (empty($results)) {
        jsonResponse(false, 'Alamat tidak ditemukan', null, 404);
    }
    
    $locations = [];
    foreach ($results as $item) {
        $locations[] = [
            'nama_lokasi' => $item['display_name'],
            'latitude'    => (float)$item['lat'],
            'longitude'   => (float)$item['lon'],
            'tipe'        => $item['type'] ?? 'unknown',
        ];
    }
    
    jsonResponse(true, 'Berhasil menemukan ' . count($locations) . ' lokasi', [
        'query'     => $address,
        'locations' => $locations
    ]);
}

function explainQuery() {
    $db = getDB();
    
    $type = $_GET['type'] ?? 'nearest';
    $lat  = filter_input(INPUT_GET, 'lat', FILTER_VALIDATE_FLOAT) ?? 3.5952;
    $lon  = filter_input(INPUT_GET, 'lon', FILTER_VALIDATE_FLOAT) ?? 98.6722;
    
    switch ($type) {
        case 'nearest':
            $sql = "EXPLAIN ANALYZE
                SELECT nama, 
                    ST_Distance(geom::geography, ST_SetSRID(ST_MakePoint($lon, $lat), 4326)::geography) AS jarak
                FROM museum
                WHERE status = 'aktif'
                ORDER BY geom <-> ST_SetSRID(ST_MakePoint($lon, $lat), 4326)
                LIMIT 5";
            break;
        case 'radius':
            $radius = filter_input(INPUT_GET, 'radius', FILTER_VALIDATE_INT) ?? 2000;
            $sql = "EXPLAIN ANALYZE
                SELECT nama FROM museum
                WHERE status = 'aktif'
                AND ST_DWithin(
                    geom::geography,
                    ST_SetSRID(ST_MakePoint($lon, $lat), 4326)::geography,
                    $radius
                )";
            break;
        default:
            jsonResponse(false, 'Type tidak valid', null, 400);
    }
    
    $results = $db->fetchAll($sql);
    $plan = array_column($results, 'QUERY PLAN');
    
    jsonResponse(true, 'EXPLAIN ANALYZE berhasil', [
        'type'     => $type,
        'plan'     => $plan,
        'catatan'  => 'Perhatikan "Index Scan using idx_museum_geom" yang menunjukkan GIST spatial index digunakan.'
    ]);
}
?>