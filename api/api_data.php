<?php
header('Content-Type: application/json');
require_once '../config/koneksi.php';

if(session_status() === PHP_SESSION_NONE) { session_start(); }
if(!isset($_SESSION['user_id'])) {
    echo json_encode(['status'=>'error', 'message'=>'Unauthorized Access']);
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action == 'get_map_data') {
    // Ambil rekap data per desa/alamat dari pasien -> hasil prediksi
    $stmt = $pdo->query("SELECT p.alamat as desa, 
        SUM(CASE WHEN hp.hasil_prediksi = 'Tinggi' THEN 1 ELSE 0 END) as tinggi,
        SUM(CASE WHEN hp.hasil_prediksi = 'Rendah' THEN 1 ELSE 0 END) as rendah,
        COUNT(hp.id_prediksi) as total
        FROM pasien p 
        JOIN hasil_prediksi hp ON p.id_pasien = hp.id_pasien
        GROUP BY p.alamat");
    echo json_encode($stmt->fetchAll());

} elseif ($action == 'get_training_data') {
    $stmt = $pdo->query("SELECT 
        p.jenis_kelamin, TIMESTAMPDIFF(YEAR, p.tanggal_lahir, CURDATE()) as umur, p.alamat as desa,
        ak.tekanan_sistolik, ak.tekanan_diastolik, ak.imt, ak.merokok, ak.konsumsi_alkohol,
        ak.kurang_buah_sayur, ak.diabetes, ak.riwayat_hipertensi,
        hp.hasil_prediksi
        FROM hasil_prediksi hp
        JOIN pasien p ON hp.id_pasien = p.id_pasien
        JOIN atribut_kesehatan ak ON hp.id_atribut_kesehatan = ak.id_atribut");
    echo json_encode($stmt->fetchAll());

} elseif ($action == 'get_pasien_belum') {
    $id_atribut = isset($_GET['id_atribut']) ? (int)$_GET['id_atribut'] : 0;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 0;

    if($id_atribut) {
        $stmt = $pdo->prepare("SELECT 
            p.id_pasien, p.nik, p.nama, p.jenis_kelamin, TIMESTAMPDIFF(YEAR, p.tanggal_lahir, CURDATE()) as umur, p.alamat as desa,
            ak.id_atribut, ak.tekanan_sistolik, ak.tekanan_diastolik, ak.imt, ak.merokok, ak.konsumsi_alkohol,
            ak.kurang_buah_sayur, ak.diabetes, ak.riwayat_hipertensi
            FROM atribut_kesehatan ak
            JOIN pasien p ON ak.id_pasien = p.id_pasien
            WHERE ak.id_atribut = ?");
        $stmt->execute([$id_atribut]);
        echo json_encode($stmt->fetch());
    } elseif ($limit > 0) {
        $stmt = $pdo->prepare("SELECT 
            p.id_pasien, p.nik, p.nama, p.jenis_kelamin, TIMESTAMPDIFF(YEAR, p.tanggal_lahir, CURDATE()) as umur, p.alamat as desa,
            ak.id_atribut, ak.tekanan_sistolik, ak.tekanan_diastolik, ak.imt, ak.merokok, ak.konsumsi_alkohol,
            ak.kurang_buah_sayur, ak.diabetes, ak.riwayat_hipertensi
            FROM atribut_kesehatan ak
            JOIN pasien p ON ak.id_pasien = p.id_pasien
            LEFT JOIN hasil_prediksi hp ON ak.id_atribut = hp.id_atribut_kesehatan
            WHERE hp.id_prediksi IS NULL
            ORDER BY ak.id_atribut DESC
            LIMIT ?");
        $stmt->execute([$limit]);
        echo json_encode($stmt->fetchAll());
    }
} elseif ($action == 'get_pasien_sudah') {
    $id_atribut = isset($_GET['id_atribut']) ? (int)$_GET['id_atribut'] : 0;
    if($id_atribut) {
        $stmt = $pdo->prepare("SELECT 
            p.id_pasien, p.nik, p.nama, p.jenis_kelamin, TIMESTAMPDIFF(YEAR, p.tanggal_lahir, CURDATE()) as umur, p.alamat as desa,
            ak.id_atribut, ak.tekanan_sistolik, ak.tekanan_diastolik, ak.imt, ak.merokok, ak.konsumsi_alkohol,
            ak.kurang_buah_sayur, ak.diabetes, ak.riwayat_hipertensi
            FROM atribut_kesehatan ak
            JOIN pasien p ON ak.id_pasien = p.id_pasien
            WHERE ak.id_atribut = ?");
        $stmt->execute([$id_atribut]);
        echo json_encode($stmt->fetch());
    }
}
exit;
?>
