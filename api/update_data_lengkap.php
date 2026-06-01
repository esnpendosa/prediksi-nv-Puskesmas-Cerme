<?php
header('Content-Type: application/json');
require_once '../config/koneksi.php';

$jsonInput = file_get_contents('php://input');
$data = json_decode($jsonInput, true);

try {
    if (!$data) throw new Exception("Data tidak diterima oleh server");

    // Pastikan ID esensial ada
    if (empty($data['id_pasien']) || empty($data['id_atribut']) || empty($data['id_prediksi'])) {
        throw new Exception("ID data (Pasien/Atribut/Prediksi) tidak boleh kosong!");
    }

    $pdo->beginTransaction();

    // VALIDASI: Cek apakah ID Pasien benar-benar ada di database
    $checkP = $pdo->prepare("SELECT id_pasien FROM pasien WHERE id_pasien = ?");
    $checkP->execute([(int)$data['id_pasien']]);
    if (!$checkP->fetch()) {
        throw new Exception("ID Pasien (" . $data['id_pasien'] . ") TIDAK DITEMUKAN di database.");
    }

    // VALIDASI: Cek apakah ID Atribut benar-benar ada di database
    $checkA = $pdo->prepare("SELECT id_atribut FROM atribut_kesehatan WHERE id_atribut = ?");
    $checkA->execute([(int)$data['id_atribut']]);
    if (!$checkA->fetch()) {
        throw new Exception("ID Atribut (" . $data['id_atribut'] . ") TIDAK DITEMUKAN di database.");
    }

    // VALIDASI: Cek apakah ID Prediksi benar-benar ada di database
    $checkHp = $pdo->prepare("SELECT id_prediksi FROM hasil_prediksi WHERE id_prediksi = ?");
    $checkHp->execute([(int)$data['id_prediksi']]);
    if (!$checkHp->fetch()) {
        throw new Exception("ID Prediksi (" . $data['id_prediksi'] . ") TIDAK DITEMUKAN di database.");
    }

    // 1. UPDATE TABEL pasien
    $sqlP = "UPDATE pasien SET 
                nik        = :nik, 
                nama       = :nama, 
                jenis_kelamin = :jk, 
                tanggal_lahir = :tgl, 
                alamat     = :alamat, 
                no_hp      = :hp 
             WHERE id_pasien = :idp";
    $stmtP = $pdo->prepare($sqlP);
    $stmtP->execute([
        ':nik'    => trim($data['nik']),
        ':nama'   => trim($data['nama']),
        ':jk'     => $data['jenis_kelamin'],
        ':tgl'    => $data['tanggal_lahir'],
        ':alamat' => trim($data['alamat']),
        ':hp'     => trim($data['no_hp']),
        ':idp'    => (int)$data['id_pasien']
    ]);
    $affectedP = $stmtP->rowCount();

    // 2. UPDATE TABEL atribut_kesehatan
    // BUG FIX: Nama field dari laporan.php adalah 'alkohol' dan 'kurang_buah'
    // (bukan 'konsumsi_alkohol' dan 'kurang_buah_sayur') — disesuaikan di sini
    $sqlAk = "UPDATE atribut_kesehatan SET 
                tekanan_sistolik   = :sis, 
                tekanan_diastolik  = :dia, 
                imt                = :imt, 
                merokok            = :mrk, 
                konsumsi_alkohol   = :alk, 
                kurang_buah_sayur  = :buah, 
                diabetes           = :db, 
                riwayat_hipertensi = :rh 
              WHERE id_atribut = :ida";
    $stmtAk = $pdo->prepare($sqlAk);
    $stmtAk->execute([
        ':sis'   => (int)$data['sistolik'],
        ':dia'   => (int)$data['diastolik'],
        ':imt'   => (float)$data['imt'],
        ':mrk'   => $data['merokok'],
        ':alk'   => $data['alkohol'],       // dikirim sebagai 'alkohol' dari JS
        ':buah'  => $data['kurang_buah'],   // dikirim sebagai 'kurang_buah' dari JS
        ':db'    => $data['diabetes'],
        ':rh'    => $data['riwayat_hipertensi'],
        ':ida'   => (int)$data['id_atribut']
    ]);
    $affectedAk = $stmtAk->rowCount();

    // 3. UPDATE TABEL hasil_prediksi
    // BUG FIX: Kolom tanggal_prediksi tidak perlu diubah saat edit manual,
    // hanya update saat "Diagnosa Ulang". Kita update tanggal hanya jika
    // field 'is_diagnosa_ulang' dikirim true dari JS.
    if (!empty($data['is_diagnosa_ulang'])) {
        $sqlHp = "UPDATE hasil_prediksi SET 
                    hasil_prediksi   = :hasil, 
                    tanggal_prediksi = CURDATE() 
                  WHERE id_prediksi = :idpr";
    } else {
        $sqlHp = "UPDATE hasil_prediksi SET 
                    hasil_prediksi = :hasil 
                  WHERE id_prediksi = :idpr";
    }
    $stmtHp = $pdo->prepare($sqlHp);
    $stmtHp->execute([
        ':hasil' => $data['hasil_prediksi'],
        ':idpr'  => (int)$data['id_prediksi']
    ]);
    $affectedHp = $stmtHp->rowCount();

    $pdo->commit();
    
    echo json_encode([
        'success'  => true, 
        'message'  => 'Update Berhasil',
        'affected' => [
            'pasien'  => $affectedP,
            'atribut' => $affectedAk,
            'hasil'   => $affectedHp
        ]
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()]);
}