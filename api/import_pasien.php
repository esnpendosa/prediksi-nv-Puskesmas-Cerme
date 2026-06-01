<?php
require_once '../config/koneksi.php';
require_once '../includes/auth_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !is_array($data)) {
    echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Siapkan query yang dibutuhkan
    $stmt_cek_nik = $pdo->prepare("SELECT id_pasien FROM pasien WHERE nik = ?");
    $stmt_p_insert = $pdo->prepare("INSERT INTO pasien (nik, nama, jenis_kelamin, tanggal_lahir, alamat, no_hp) VALUES (?,?,?,?,?,?)");
    $stmt_p_update = $pdo->prepare("UPDATE pasien SET nama=?, jenis_kelamin=?, tanggal_lahir=?, alamat=?, no_hp=? WHERE id_pasien=?");

    $stmt_ak = $pdo->prepare("INSERT INTO atribut_kesehatan 
        (id_pasien, tanggal_pemeriksaan, tekanan_sistolik, tekanan_diastolik, imt, merokok, konsumsi_alkohol, kurang_buah_sayur, diabetes, riwayat_hipertensi) 
        VALUES (?, CURDATE(), ?,?,?,?,?,?,?,?)");

    // Query tambahan untuk insert ke tabel hasil_prediksi (jika Data Latih)
    $stmt_hp = $pdo->prepare("INSERT INTO hasil_prediksi (id_pasien, id_atribut_kesehatan, tanggal_prediksi, hasil_prediksi) VALUES (?, ?, CURDATE(), ?)");

    $success_count = 0;
    $errors = [];

    foreach ($data as $index => $row) {
        try {
            // Basic validation
            if (empty($row['nik']) || empty($row['nama'])) {
                continue;
            }

            // 1. Cek apakah NIK sudah ada di database
            $stmt_cek_nik->execute([$row['nik']]);
            $existing = $stmt_cek_nik->fetch();

            $id_pasien = null;

            if ($existing) {
                // Jika sudah ada, Replace (Update) data biodatanya
                $id_pasien = $existing['id_pasien'];
                $stmt_p_update->execute([
                    $row['nama'],
                    strtolower($row['jenis_kelamin']),
                    $row['tanggal_lahir'],
                    $row['alamat'],
                    $row['no_hp'],
                    $id_pasien
                ]);
            } else {
                // Jika tidak ada, Insert sebagai pasien baru
                $stmt_p_insert->execute([
                    $row['nik'],
                    $row['nama'],
                    strtolower($row['jenis_kelamin']),
                    $row['tanggal_lahir'],
                    $row['alamat'],
                    $row['no_hp']
                ]);
                $id_pasien = $pdo->lastInsertId();
            }

            // 2. Insert Atribut Kesehatan sebagai history pemeriksaan baru
            $stmt_ak->execute([
                $id_pasien,
                $row['tekanan_sistolik'],
                $row['tekanan_diastolik'],
                $row['imt'],
                $row['merokok'] ?: 'Tidak',
                $row['konsumsi_alkohol'] ?: 'Tidak',
                $row['kurang_buah_sayur'] ?: 'Tidak',
                $row['diabetes'] ?: 'Tidak',
                $row['riwayat_hipertensi'] ?: 'Tidak'
            ]);
            $id_atribut = $pdo->lastInsertId(); // Ambil ID atribut untuk direlasikan ke prediksi

            // 3. Jika membawa data 'hasil_prediksi' (Artinya ini adalah Data Latih)
            if (isset($row['hasil_prediksi']) && !empty($row['hasil_prediksi'])) {
                // Normalisasi string (tinggi/rendah)
                $hasil = (strtolower(trim($row['hasil_prediksi'])) == 'tinggi') ? 'Tinggi' : 'Rendah';
                $stmt_hp->execute([$id_pasien, $id_atribut, $hasil]);
            }

            $success_count++;
        } catch (Exception $e) {
            $errors[] = "Baris " . ($index + 1) . ": " . $e->getMessage();
        }
    }

    if (empty($errors)) {
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => "$success_count data pasien berhasil diimport"]);
    } else {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Gagal mengimport data', 'errors' => $errors]);
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Sistem Error: ' . $e->getMessage()]);
}
?>
```

### 2. Update View Modal & JS Parser

Buka file `prediksinv/views/data_pasien.php`.

**Langkah A: Ganti HTML `#importModal`**
Cari kode `<div class="modal fade" id="importModal" tabindex="-1">` sampai dengan `</div>` penutupnya (sekitar baris 417), lalu ganti dengan ini:

```html
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 1.5rem;">
            <div class="modal-header bg-success text-white p-4 border-0">
                <h5 class="modal-title fw-bold"><i class="fas fa-file-excel me-2"></i>Import Massal Data Excel</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <div class="alert alert-info py-3 shadow-sm border-0 rounded-4" style="font-size: 0.9rem;">
                    <i class="fas fa-info-circle me-2 text-primary fs-5 mb-2"></i><br>
                    Unggah file berformat <strong>.xlsx / .xls</strong>. Jika NIK pada excel sudah pernah ada, maka sistem akan memperbarui (replace) biodata medis yang lama.<br>
                    <a href="../assets/template_import_pasien.xlsx" download class="fw-bold text-decoration-none mt-2 d-inline-block"><i class="fas fa-download me-1"></i>Unduh Template Excel Disini</a>
                </div>

                <div class="mb-4 mt-3 bg-white p-3 rounded-4 shadow-sm border">
                    <label class="form-label fw-bold text-dark small mb-3">Tujuan Import Data:</label>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="jenisImport" id="importUji" value="uji" checked onchange="updateHint()">
                        <label class="form-check-label text-dark fw-bold" for="importUji">
                            Data Antrean Baru (Data Uji)
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="jenisImport" id="importLatih" value="latih" onchange="updateHint()">
                        <label class="form-check-label text-dark fw-bold" for="importLatih">
                            Data Historis (Data Latih AI)
                        </label>
                    </div>
                    <small id="importHint" class="text-muted d-block mt-2" style="font-size: 11px; line-height:1.4;">
                        <i class="fas fa-info-circle me-1"></i> Data akan masuk ke antrean untuk didiagnosa. Tidak perlu kolom "Hasil Prediksi".
                    </small>
                </div>

                <div class="mb-3 mt-2">
                    <label class="form-label fw-bold text-muted small">Pilih Berkas Komputer</label>
                    <input type="file" id="excelFile" class="form-control form-control-lg border-0 shadow-sm bg-white" accept=".xlsx, .xls">
                </div>
                <div id="importProgress" class="d-none mt-4">
                    <div class="text-center text-muted small fw-bold mb-2"><i class="fas fa-cog fa-spin me-1"></i>Sistem sedang mengekstraksi data...</div>
                    <div class="progress rounded-pill shadow-sm" style="height: 10px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 100%"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white border-0 p-4" style="border-radius: 0 0 1.5rem 1.5rem;">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
                <button type="button" id="btnProsesImport" class="btn btn-success rounded-pill px-4 shadow-sm"><i class="fas fa-cloud-upload-alt me-2"></i>Mulai Import Excel</button>
            </div>
        </div>
    </div>
</div>