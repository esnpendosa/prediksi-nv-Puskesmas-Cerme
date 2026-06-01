<?php
require_once '../config/koneksi.php';
require_once '../includes/auth_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$id_prediksi = $_POST['id_prediksi'];
$id_pasien = $_POST['id_pasien'];
$id_atribut = $_POST['id_atribut'];

try {
    $pdo->beginTransaction();

    // 1. Update Tabel Pasien
    $stmt_p = $pdo->prepare("UPDATE pasien SET nik = ?, nama = ?, tanggal_lahir = ?, jenis_kelamin = ?, alamat = ? WHERE id_pasien = ?");
    $stmt_p->execute([
        $_POST['nik'], 
        $_POST['nama'], 
        $_POST['tanggal_lahir'], 
        $_POST['jenis_kelamin'], 
        $_POST['alamat'], 
        $id_pasien
    ]);

    // 2. Update Tabel Atribut Kesehatan
    $stmt_ak = $pdo->prepare("UPDATE atribut_kesehatan SET tekanan_sistolik = ?, tekanan_diastolik = ?, imt = ? WHERE id_atribut = ?");
    $stmt_ak->execute([
        $_POST['tekanan_sistolik'], 
        $_POST['tekanan_diastolik'], 
        $_POST['imt'], 
        $id_atribut
    ]);

    // 3. Update Tabel Hasil Prediksi (Override)
    $stmt_hp = $pdo->prepare("UPDATE hasil_prediksi SET hasil_prediksi = ? WHERE id_prediksi = ?");
    $stmt_hp->execute([
        $_POST['hasil_prediksi'],
        $id_prediksi
    ]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Laporan berhasil diperbarui']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Gagal memperbarui: ' . $e->getMessage()]);
}
