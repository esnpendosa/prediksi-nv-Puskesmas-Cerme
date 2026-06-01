<?php
require_once '../config/koneksi.php';
require_once '../includes/auth_check.php';

// Ambil data atribut pasien yang BELUM ada di hasil_prediksi
$query = "SELECT p.nama, p.nik, ak.id_atribut, ak.tanggal_pemeriksaan 
          FROM atribut_kesehatan ak
          JOIN pasien p ON ak.id_pasien = p.id_pasien
          LEFT JOIN hasil_prediksi hp ON ak.id_atribut = hp.id_atribut_kesehatan
          WHERE hp.id_prediksi IS NULL
          ORDER BY ak.tanggal_pemeriksaan DESC";
$pasien_belum = $pdo->query($query)->fetchAll();

$select_id = isset($_GET['id_atribut']) ? $_GET['id_atribut'] : '';
include '../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <h2 class="mb-0 fw-bold fs-4"><i class="fas fa-brain text-medical-blue me-2"></i>Sistem Inferensi AI (Diagnosa)</h2>
    <span class="badge bg-medical-blue text-white p-2 rounded-pill shadow-sm"><i class="fas fa-robot me-1"></i> Metode Naïve Bayes</span>
</div>

<div class="row g-4">
    <!-- PANEL KIRI: PILIHAN DATA -->
    <div class="col-xl-4 col-lg-5">
        <div class="card glass-card border-0 shadow-sm sticky-top" style="top: 80px; z-index: 10;">
            <div class="card-header bg-white pt-4 pb-2 border-0">
                <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-clipboard-list text-warning me-2"></i>Antrean Pasien</h5>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <label class="form-label text-muted small fw-bold text-uppercase" style="letter-spacing: 0.5px;">Pilih Identitas Pasien</label>
                    <select id="pilihPasien" class="form-select form-select-lg shadow-sm bg-light border-0 fw-bold" style="font-size: 0.95rem;">
                        <option value="">-- Silahkan Pilih Pasien --</option>
                        <?php foreach ($pasien_belum as $p): ?>
                            <option value="<?= $p['id_atribut'] ?>" <?= $p['id_atribut'] == $select_id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['nama']) ?> - (<?= htmlspecialchars($p['nik']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button id="btnHitung" class="btn btn-primary bg-blue w-100 py-3 fw-bold shadow hover-card" style="border-radius: 12px;">
                    <i class="fas fa-brain me-2 fs-5 align-middle"></i>Jalankan Algoritma Naïve Bayes
                </button>
            </div>
        </div>
    </div>

    <!-- PANEL KANAN: MEDICAL LOG & HASIL -->
    <div class="col-xl-8 col-lg-7">

        <!-- Placeholder Sebelum Hitung -->
        <div id="placeholderPrediksi" class="card shadow-sm border-0 d-flex flex-column justify-content-center align-items-center h-100" style="min-height: 400px; background: rgba(255,255,255,0.8); border-radius: 1.5rem;">
            <div class="text-center p-4">
                <div class="mb-4 position-relative d-inline-block">
                    <i class="fas fa-hourglass text-primary opacity-25 fa-spin" style="font-size: 6rem;"></i>
                    <!-- <i class="fas fa-cogs text-secondary position-absolute bottom-0 end-0 opacity-50 fa-spin" style="font-size: 2rem;"></i> -->
                </div>
                <h4 class="fw-bold text-dark">Sistem Menunggu Permintaan</h4>
                <p class="text-muted mb-0">Pilih data pasien pada panel antrean, lalu jalankan analisis algoritma naive bayes untuk memprediksi klasifikasi tingkat risiko hipertensi.</p>
            </div>
        </div>

        <!-- Container Hasil -->
        <div id="hasilContainer" style="display: none;" class="animate-slide-up">

            <!-- Logika Naive Bayes Medis -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 1.5rem; overflow: hidden;">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center">
                    <i class="fas fa-server text-medical-blue me-2"></i>
                    <h6 class="mb-0 fw-bold">Log Komputasi Diagnostik</h6>
                </div>
                <!-- Box untuk merender JavaScript Log -->
                <div id="logikaNB" class="card-body bg-light custom-scrollbar p-4" style="max-height: 500px; overflow-y: auto;">
                    <!-- Detail JS Masuk Sini -->
                </div>
            </div>

            <!-- Panel Kesimpulan Akhir -->
            <div id="cardDiagnosa" class="card border border-2 shadow-sm" style="border-radius: 1.5rem; transition: all 0.3s ease;">
                <div id="panelDiagnosa" class="card-body p-4 p-md-5 d-flex flex-column flex-sm-row align-items-center text-center text-sm-start transition-all" style="border-radius: 1.5rem 1.5rem 0 0;">
                    <div class="bg-white rounded-circle shadow-sm d-flex align-items-center justify-content-center me-sm-4 mb-3 mb-sm-0" style="width: 80px; height: 80px; min-width: 80px;">
                        <i class="fas fa-heartbeat fs-1 text-dark animate__animated animate__pulse animate__infinite"></i>
                    </div>
                    <div>
                        <p class="mb-1 text-muted fw-bold text-uppercase" style="letter-spacing: 1px; font-size: 0.8rem;">Hasil Kesimpulan Akhir</p>
                        <h2 class="mb-2 fw-black" id="hasilAkhirTeks" style="letter-spacing: 0.5px;"></h2>
                        <p class="mb-0 text-muted small fw-bold"></p> <!-- Dikosongkan, diisi oleh JS -->
                    </div>
                </div>
                <div class="card-footer bg-white border-top-0 p-3 p-md-4 pt-2" style="border-radius: 0 0 1.5rem 1.5rem;">
                    <button id="btnSimpan" class="btn btn-success btn-lg w-100 fw-bold shadow-sm" style="border-radius: 12px;">
                        <i class="fas fa-save me-2"></i>Simpan Diagnosa ke Database
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>