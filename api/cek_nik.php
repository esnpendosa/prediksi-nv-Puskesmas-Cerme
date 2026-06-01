<?php
// Mencegah PHP mencetak error default (HTML) yang dapat merusak format JSON
error_reporting(0);
ini_set('display_errors', 0);

// Set header wajib untuk API
header('Content-Type: application/json; charset=utf-8');

try {
    require_once '../config/koneksi.php';

    // Ambil NIK dan hilangkan spasi berlebih
    $nik = isset($_GET['nik']) ? trim($_GET['nik']) : '';

    if (!empty($nik)) {
        // Cari pasien berdasarkan NIK
        $stmt = $pdo->prepare("SELECT id_pasien, nama FROM pasien WHERE nik = ?");
        $stmt->execute([$nik]);
        $pasien = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($pasien) {
            // Jika ditemukan, kembalikan status exists = true beserta data id dan nama
            echo json_encode([
                'success' => true,
                'exists' => true,
                'id_pasien' => $pasien['id_pasien'],
                'nama' => $pasien['nama']
            ]);
        } else {
            // NIK belum terdaftar
            echo json_encode(['success' => true, 'exists' => false]);
        }
    } else {
        // NIK kosong
        echo json_encode(['success' => true, 'exists' => false, 'message' => 'Format NIK tidak valid atau kosong.']);
    }
} catch (PDOException $e) {
    // Tangkap jika ada error koneksi / sintaks database
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'exists' => false,
        'error' => 'Database Error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    // Tangkap error sistem lainnya
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'exists' => false,
        'error' => 'System Error: ' . $e->getMessage()
    ]);
}
