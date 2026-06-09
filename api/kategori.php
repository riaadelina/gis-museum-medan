<?php

require_once '../config/database.php';

$db = getDB();

$sql = "SELECT id, nama_kategori, deskripsi FROM kategori_museum ORDER BY nama_kategori";
$kategori = $db->fetchAll($sql);

jsonResponse(true, 'Berhasil', $kategori);
?>