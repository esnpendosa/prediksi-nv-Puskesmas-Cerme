<?php
require_once '../config/koneksi.php';
require_once '../includes/auth_check.php';

// Ambil data laporan (gabungan pasien, atribut_kesehatan, dan hasil_prediksi)
$query = "SELECT hp.id_prediksi, p.id_pasien, ak.id_atribut,
          p.nik, p.nama, p.jenis_kelamin, p.tanggal_lahir, p.alamat, p.no_hp,
          ak.tekanan_sistolik, ak.tekanan_diastolik, ak.imt, ak.merokok, ak.konsumsi_alkohol,
          ak.kurang_buah_sayur, ak.diabetes, ak.riwayat_hipertensi,
          hp.hasil_prediksi, hp.tanggal_prediksi,
          TIMESTAMPDIFF(YEAR, p.tanggal_lahir, CURDATE()) AS umur 
          FROM hasil_prediksi hp
          JOIN pasien p ON hp.id_pasien = p.id_pasien
          JOIN atribut_kesehatan ak ON hp.id_atribut_kesehatan = ak.id_atribut
          ORDER BY hp.tanggal_prediksi DESC, hp.id_prediksi DESC";
$laporan = $pdo->query($query)->fetchAll();

// Ambil logo gresik dan puskesmas sebagai base64 untuk offline embedding (PDF & Cetak)
$logo_gresik_base64 = '';
$logo_gresik_path = '../assets/icon gresik.png';
if (file_exists($logo_gresik_path)) {
    $logo_gresik_base64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logo_gresik_path));
}

$logo_puskesmas_base64 = '';
$logo_puskesmas_path = '../assets/icon puskesmas.png';
if (file_exists($logo_puskesmas_path)) {
    $logo_puskesmas_base64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logo_puskesmas_path));
}

// Buat URL absolut untuk Excel agar Microsoft Excel bisa me-load gambar via HTTP (karena Excel tidak mendukung Base64)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$requestUri = $_SERVER['REQUEST_URI'];
$currentDir = dirname($requestUri);
$parentDir = dirname($currentDir);
$parentDirUrl = str_replace('\\', '/', $parentDir);
$baseUrl = $protocol . $host . rtrim($parentDirUrl, '/') . '/';

$logo_gresik_url = $baseUrl . 'assets/icon%20gresik.png';
$logo_puskesmas_url = $baseUrl . 'assets/icon%20puskesmas.png';

include '../includes/header.php';
?>

<!-- Library Eksternal untuk PDF & Excel -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<style>
    /* Styling Khusus untuk Container PDF & Print Mode */
    #pdfContent {
        padding: 20px;
        background-color: white;
        font-family: 'Times New Roman', Times, serif;
    }

    .kop-surat-container {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 5px;
    }

    .garis-kop {
        border-top: 3px solid black;
        border-bottom: 1px solid black;
        height: 2px;
        margin-bottom: 15px;
    }

    .table-print {
        width: 100%;
        border-collapse: collapse;
        font-size: 11px;
    }

    .table-print th,
    .table-print td {
        border: 1px solid #000;
        padding: 6px;
        color: black;
    }

    @media print {

        .no-print,
        #sidebar-wrapper,
        .navbar,
        footer,
        .modal {
            display: none !important;
        }

        #page-content-wrapper {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }

        #pdfContent {
            display: block !important;
        }
    }
</style>

<!-- TAMPILAN DASHBOARD UTAMA -->
<div id="pdfContent" style="display: none;">
    <div class="kop-surat-container">
        <img src="<?= $logo_gresik_base64 ?>" alt="Logo" style="width: 60px;">
        <div class="text-center" style="flex-grow: 1;">
            <h5 class="mb-0 fw-bold">PEMERINTAH KABUPATEN GRESIK</h5>
            <h5 class="mb-0 fw-bold">DINAS KESEHATAN</h5>
            <h4 class="mb-0 fw-bold">UPT PUSKESMAS CERME</h4>
            <p class="mb-0 small">Jl. Raya Cerme Lor No. 123, Kec. Cerme, Gresik</p>
        </div>
        <img src="<?= $logo_puskesmas_base64 ?>" alt="Logo" style="width: 60px;">
    </div>
    <div class="garis-kop"></div>
    <div class="text-center mb-3">
        <h6 class="fw-bold text-decoration-underline">LAPORAN HASIL DIAGNOSA KESEHATAN (NAIVE BAYES)</h6>
    </div>
    <table class="table-print">
        <thead>
            <tr>
                <th>Tgl Prediksi</th>
                <th>Nama Pasien</th>
                <th>Desa</th>
                <th>Tensi & IMT</th>
                <th>Hasil</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($laporan as $p): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($p['tanggal_prediksi'])) ?></td>
                    <td><?= htmlspecialchars($p['nama']) ?> (<?= $p['umur'] ?> th)</td>
                    <td><?= htmlspecialchars($p['alamat']) ?></td>
                    <td><?= $p['tekanan_sistolik'] ?>/<?= $p['tekanan_diastolik'] ?> | IMT: <?= $p['imt'] ?></td>
                    <td><strong><?= $p['hasil_prediksi'] ?></strong></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="no-print">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <h2 class="mb-0 fs-4 fw-bold"><i class="fas fa-file-medical-alt text-primary me-2"></i>Laporan Diagnosa</h2>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-primary border-2 shadow-sm fw-bold rounded-pill" style="padding: 10px 24px; letter-spacing: 0.3px; box-shadow: 0 8px 15px rgba(0, 114, 255, 0.2) !important; transition: all 0.3s ease !important;">
                <i class="fas fa-print me-2"></i>Cetak Laporan
            </button>
            <button onclick="eksporPDF()" class="btn btn-danger shadow-sm fw-bold rounded-pill" style="padding: 10px 24px; letter-spacing: 0.3px; box-shadow: 0 8px 15px rgba(239, 68, 68, 0.2) !important; transition: all 0.3s ease !important;">
                <i class="fas fa-file-pdf me-2"></i>Ekspor PDF
            </button>
            <button onclick="eksporExcel()" class="btn btn-success shadow-sm fw-bold rounded-pill" style="padding: 10px 24px; letter-spacing: 0.3px; box-shadow: 0 8px 15px rgba(16, 185, 129, 0.2) !important; transition: all 0.3s ease !important;">
                <i class="fas fa-file-excel me-2"></i>Ekspor Excel
            </button>
        </div>
    </div>

    <div class="card glass-card border-0 shadow-sm mb-4" style="border-radius: 1.5rem;">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table id="tableLaporan" class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th>Tgl Prediksi</th>
                            <th>Nama Pasien</th>
                            <th>Desa (Alamat)</th>
                            <th class="text-center">Tensi & IMT</th>
                            <th class="text-center">Status Risiko</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($laporan as $p): ?>
                            <tr>
                                <td class="text-muted"><?= date('d M Y', strtotime($p['tanggal_prediksi'])) ?></td>
                                <td class="fw-bold">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                            <?= strtoupper(substr($p['nama'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <?= htmlspecialchars($p['nama']) ?><br>
                                            <small class="text-muted fw-normal"><?= $p['umur'] ?> th • <?= $p['jenis_kelamin'] ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($p['alamat']) ?></td>
                                <td class="text-center"><?= $p['tekanan_sistolik'] ?>/<?= $p['tekanan_diastolik'] ?> | <?= $p['imt'] ?></td>
                                <td class="text-center">
                                    <?php if ($p['hasil_prediksi'] == 'Tinggi'): ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-3 py-2 rounded-pill"><i class="fas fa-chart-line me-1"></i>Risiko Tinggi</span>
                                    <?php else: ?>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 rounded-pill"><i class="fas fa-shield-alt me-1"></i>Risiko Rendah</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-warning border shadow-sm rounded-circle" style="width: 32px; height: 32px;"
                                            data-bs-toggle="modal" data-bs-target="#editModal"
                                            data-json="<?= htmlspecialchars(json_encode($p)) ?>"
                                            onclick="fillEditForm(this)" title="Edit Pasien">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger border shadow-sm rounded-circle" style="width: 32px; height: 32px;"
                                            onclick="confirmDelete(<?= $p['id_prediksi'] ?>)" title="Hapus Pasien">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- Modal Edit & Diagnosa Ulang -->
<div class="modal fade no-print" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-fullscreen-lg-down">
        <div class="modal-content border-0 shadow" style="border-radius: 1rem;">
            <form id="formEditLaporan">
                <!-- ID Tersembunyi -->
                <input type="hidden" name="id_pasien" id="edit_id_pasien">
                <input type="hidden" name="id_atribut" id="edit_id_atribut">
                <input type="hidden" name="id_prediksi" id="edit_id_prediksi">
                <input type="hidden" name="hasil_prediksi_baru" id="hasil_prediksi_baru">

                <!-- MODAL HEADER -->
                <div class="modal-header bg-primary bg-gradient text-white p-3 border-0" style="border-radius: 1rem 1rem 0 0;">
                    <h5 class="modal-title fw-bold fs-6" id="editModalLabel">
                        <i class="fas fa-user-edit me-2"></i>Koreksi Laporan & Diagnosa Ulang
                    </h5>
                    <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- MODAL BODY -->
                <div class="modal-body p-3 bg-light">

                    <!-- SECTION 1: IDENTITAS PASIEN -->
                    <div class="card border-0 shadow-sm rounded-3 mb-3">
                        <div class="card-body p-3">
                            <h6 class="fw-bold text-primary border-bottom pb-2 mb-3 small">
                                <i class="fas fa-id-card flex-shrink-0 me-2"></i>Identitas Pasien
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label small fw-bold text-muted mb-1">NIK</label>
                                    <input type="text" name="nik" id="edit_nik" class="form-control form-control-sm bg-light border-0" required>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label small fw-bold text-muted mb-1">Nama Lengkap</label>
                                    <input type="text" name="nama" id="edit_nama" class="form-control form-control-sm bg-light border-0" required>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label small fw-bold text-muted mb-1">Tanggal Lahir</label>
                                    <input type="date" name="tanggal_lahir" id="edit_tanggal_lahir" class="form-control form-control-sm bg-light border-0 text-muted" required>
                                </div>

                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label small fw-bold text-muted mb-1">Jenis Kelamin</label>
                                    <select name="jenis_kelamin" id="edit_jenis_kelamin" class="form-select form-select-sm bg-light border-0">
                                        <option value="laki-laki">Laki-laki</option>
                                        <option value="perempuan">Perempuan</option>
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label small fw-bold text-muted mb-1">Desa Wilayah (Cerme)</label>
                                    <select name="alamat" id="edit_alamat" class="form-select form-select-sm bg-light border-0" required>
                                        <option value="Banjarsari">Banjarsari</option>
                                        <option value="Betiting">Betiting</option>
                                        <option value="Cagak Agung">Cagak Agung</option>
                                        <option value="Cerme Kidul">Cerme Kidul</option>
                                        <option value="Cerme Lor">Cerme Lor</option>
                                        <option value="Dadapkuning">Dadapkuning</option>
                                        <option value="Dampaan">Dampaan</option>
                                        <option value="Dooro">Dooro (Dohoagung)</option>
                                        <option value="Dungus">Dungus</option>
                                        <option value="Gedangkulut">Gedangkulut</option>
                                        <option value="Guranganyar">Guranganyar</option>
                                        <option value="Iker-iker Geger">Iker-iker Geger</option>
                                        <option value="Jono">Jono</option>
                                        <option value="Kambingan">Kambingan</option>
                                        <option value="Kandangan">Kandangan</option>
                                        <option value="Lengkong">Lengkong</option>
                                        <option value="Morowudi">Morowudi</option>
                                        <option value="Ngabetan">Ngabetan</option>
                                        <option value="Ngembung">Ngembung</option>
                                        <option value="Padeg">Padeg</option>
                                        <option value="Pandu">Pandu</option>
                                        <option value="Semampir">Semampir</option>
                                        <option value="Sukoanyar">Sukoanyar</option>
                                        <option value="Tambakberas">Tambakberas</option>
                                        <option value="Wedani">Wedani</option>
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label small fw-bold text-muted mb-1">No. HP</label>
                                    <input type="text" name="no_hp" id="edit_no_hp" class="form-control form-control-sm bg-light border-0">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: INDIKATOR MEDIS & DIAGNOSA -->
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-body p-3">
                            <h6 class="fw-bold text-success border-bottom pb-2 mb-3 small">
                                <i class="fas fa-stethoscope flex-shrink-0 me-2"></i>Indikator Medis & Diagnosa
                            </h6>
                            <div class="row g-3">
                                <!-- Vital Signs -->
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label small fw-bold text-muted mb-1">Sistolik</label>
                                    <div class="input-group input-group-sm overflow-hidden rounded-2">
                                        <input type="number" name="tekanan_sistolik" id="edit_tekanan_sistolik" class="form-control bg-light border-0" required>
                                        <span class="input-group-text bg-light border-0 text-muted" style="font-size: 0.75rem;">mmHg</span>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label small fw-bold text-muted mb-1">Diastolik</label>
                                    <div class="input-group input-group-sm overflow-hidden rounded-2">
                                        <input type="number" name="tekanan_diastolik" id="edit_tekanan_diastolik" class="form-control bg-light border-0" required>
                                        <span class="input-group-text bg-light border-0 text-muted" style="font-size: 0.75rem;">mmHg</span>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label small fw-bold text-muted mb-1">Indeks Massa Tubuh</label>
                                    <input type="number" step="0.01" name="imt" id="edit_imt" class="form-control form-control-sm bg-light border-0" required>
                                </div>

                                <!-- Anamnesa & Hasil Diagnosa -->
                                <div class="col-6 col-lg-2">
                                    <label class="form-label small fw-bold text-muted mb-1">Diabetes</label>
                                    <select name="diabetes" id="edit_diabetes" class="form-select form-select-sm bg-light border-0">
                                        <option value="Tidak">Tidak</option>
                                        <option value="Ya">Ya</option>
                                    </select>
                                </div>
                                <div class="col-6 col-lg-2">
                                    <label class="form-label small fw-bold text-muted mb-1">Riw. Hipertensi</label>
                                    <select name="riwayat_hipertensi" id="edit_riwayat_hipertensi" class="form-select form-select-sm bg-light border-0">
                                        <option value="Tidak">Tidak</option>
                                        <option value="Ya">Ya</option>
                                    </select>
                                </div>
                                <div class="col-6 col-lg-2">
                                    <label class="form-label small fw-bold text-muted mb-1">Perokok Aktif</label>
                                    <select name="merokok" id="edit_merokok" class="form-select form-select-sm bg-light border-0">
                                        <option value="Tidak">Tidak</option>
                                        <option value="Ya">Ya</option>
                                    </select>
                                </div>
                                <div class="col-6 col-lg-2">
                                    <label class="form-label small fw-bold text-muted mb-1">Alkohol</label>
                                    <select name="konsumsi_alkohol" id="edit_konsumsi_alkohol" class="form-select form-select-sm bg-light border-0" required>
                                        <option value="Tidak">Tidak</option>
                                        <option value="Ya">Ya</option>
                                    </select>
                                </div>
                                <div class="col-6 col-lg-2">
                                    <label class="form-label small fw-bold text-muted mb-1">Gizi Kurang</label>
                                    <select name="kurang_buah_sayur" id="edit_kurang_buah_sayur" class="form-select form-select-sm bg-light border-0" required>
                                        <option value="Tidak">Tidak</option>
                                        <option value="Ya">Ya</option>
                                    </select>
                                </div>
                                <div class="col-6 col-lg-2">
                                    <label class="form-label small fw-bold text-primary mb-1">Hasil Diagnosa</label>
                                    <select name="hasil_prediksi" id="edit_hasil_prediksi" class="form-select form-select-sm bg-warning bg-opacity-25 border-0 fw-bold text-dark">
                                        <option value="Tinggi">Tinggi</option>
                                        <option value="Rendah">Rendah</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- MODAL FOOTER -->
                <div class="modal-footer bg-white p-3 border-0 d-flex flex-wrap justify-content-end gap-2" style="border-radius: 0 0 1rem 1rem;">
                    <button type="button" class="btn btn-sm btn-light px-4 rounded-pill fw-bold text-muted shadow-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-outline-primary px-4 rounded-pill fw-bold shadow-sm">
                        <i class="fas fa-check-circle me-1"></i> Simpan Saja
                    </button>
                    <button type="button" onclick="prosesPrediksiUlang(event)" class="btn btn-sm btn-warning px-4 rounded-pill fw-bold shadow-sm text-dark">
                        <i class="fas fa-sync-alt me-1"></i> Simpan & Diagnosa Ulang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#tableLaporan').DataTable({
            "order": [],
            "stateSave": false,
            "responsive": true, // Menambahkan responsivitas yang sebelumnya ada di script.js
            "destroy": true
        });
    });

    function fillEditForm(btn) {
        const data = JSON.parse(btn.getAttribute('data-json'));
        console.log('Data to fill:', data);

        document.getElementById('hasil_prediksi_baru').value = "";

        document.getElementById('edit_id_pasien').value = data.id_pasien;
        document.getElementById('edit_id_atribut').value = data.id_atribut;
        document.getElementById('edit_id_prediksi').value = data.id_prediksi;

        document.getElementById('edit_nik').value = data.nik || '';
        document.getElementById('edit_nama').value = data.nama || '';
        document.getElementById('edit_tanggal_lahir').value = data.tanggal_lahir || '';
        document.getElementById('edit_jenis_kelamin').value = data.jenis_kelamin || 'laki-laki';
        document.getElementById('edit_no_hp').value = data.no_hp || '';

        const alamatSelect = document.getElementById('edit_alamat');
        const alamatValueFromDB = (data.alamat || '').trim();
        let found = false;

        for (let i = 0; i < alamatSelect.options.length; i++) {
            if (alamatSelect.options[i].value === alamatValueFromDB) {
                alamatSelect.selectedIndex = i;
                found = true;
                break;
            }
        }

        if (!found && alamatValueFromDB !== '') {
            alamatSelect.add(new Option(alamatValueFromDB, alamatValueFromDB));
            alamatSelect.value = alamatValueFromDB;
        }

        document.getElementById('edit_tekanan_sistolik').value = data.tekanan_sistolik || 0;
        document.getElementById('edit_tekanan_diastolik').value = data.tekanan_diastolik || 0;
        document.getElementById('edit_imt').value = data.imt || 0;
        document.getElementById('edit_hasil_prediksi').value = data.hasil_prediksi || 'Rendah';
        document.getElementById('edit_diabetes').value = data.diabetes || 'Tidak';
        document.getElementById('edit_riwayat_hipertensi').value = data.riwayat_hipertensi || 'Tidak';
        document.getElementById('edit_merokok').value = data.merokok || 'Tidak';
        document.getElementById('edit_konsumsi_alkohol').value = data.konsumsi_alkohol || 'Tidak';
        document.getElementById('edit_kurang_buah_sayur').value = data.kurang_buah_sayur || 'Tidak';
    }

    document.getElementById('formEditLaporan').addEventListener('submit', function(e) {
        e.preventDefault();

        // Ambil nilai hasil_prediksi_baru: jika ada (dari diagnosa ulang), pakai itu; jika tidak, pakai pilihan manual
        const hasilBaru = document.getElementById('hasil_prediksi_baru').value;
        const hasilManual = document.getElementById('edit_hasil_prediksi').value;
        const isDiagnosaUlang = (hasilBaru !== '');

        const payload = {
            id_prediksi:        document.getElementById('edit_id_prediksi').value,
            id_atribut:         document.getElementById('edit_id_atribut').value,
            id_pasien:          document.getElementById('edit_id_pasien').value,
            nik:                document.getElementById('edit_nik').value.trim(),
            nama:               document.getElementById('edit_nama').value.trim(),
            tanggal_lahir:      document.getElementById('edit_tanggal_lahir').value,
            jenis_kelamin:      document.getElementById('edit_jenis_kelamin').value,
            alamat:             document.getElementById('edit_alamat').value,
            no_hp:              document.getElementById('edit_no_hp').value.trim(),
            sistolik:           document.getElementById('edit_tekanan_sistolik').value,
            diastolik:          document.getElementById('edit_tekanan_diastolik').value,
            imt:                document.getElementById('edit_imt').value,
            hasil_prediksi:     isDiagnosaUlang ? hasilBaru : hasilManual,
            is_diagnosa_ulang:  isDiagnosaUlang,
            diabetes:           document.getElementById('edit_diabetes').value,
            riwayat_hipertensi: document.getElementById('edit_riwayat_hipertensi').value,
            merokok:            document.getElementById('edit_merokok').value,
            kurang_buah:        document.getElementById('edit_kurang_buah_sayur').value,
            alkohol:            document.getElementById('edit_konsumsi_alkohol').value
        };

        console.log('Mengirim Payload:', payload);

        fetch('../api/update_data_lengkap.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(async response => {
                const text = await response.text();
                try {
                    return JSON.parse(text);
                } catch (err) {
                    console.error('Server returned non-JSON:', text);
                    throw new Error('Respon server tidak valid (bukan JSON). Hubungi administrator.');
                }
            })
            .then(res => {
                console.log('Final Response:', res);
                if (res.success) {
                    const stats = res.affected || {};
                    const isAnyChange = (stats.pasien > 0 || stats.atribut > 0 || stats.hasil > 0);
                    
                    Swal.fire({
                        icon: isAnyChange ? 'success' : 'info',
                        title: isAnyChange ? 'Data Berhasil Disimpan' : 'Tidak Ada Perubahan',
                        html: `
                            <div class="text-start small mt-2">
                                <p class="mb-1">Statistik perubahan:</p>
                                <ul class="mb-0">
                                    <li>Data Pasien: ${stats.pasien > 0 ? '<span class="text-success fw-bold">DIPERBARUI</span>' : '<span class="text-muted">Tetap</span>'}</li>
                                    <li>Data Klinis: ${stats.atribut > 0 ? '<span class="text-success fw-bold">DIPERBARUI</span>' : '<span class="text-muted">Tetap</span>'}</li>
                                    <li>Hasil Prediksi: ${stats.hasil > 0 ? '<span class="text-success fw-bold">DIPERBARUI</span>' : '<span class="text-muted">Tetap</span>'}</li>
                                </ul>
                            </div>
                        `,
                        confirmButtonText: 'OK, Segarkan Halaman'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Menyimpan!',
                        text: res.message || 'Terjadi kesalahan pada sistem.',
                        footer: '<small>Periksa apakah NIK sudah digunakan oleh pasien lain</small>'
                    });
                }
            })
            .catch(err => {
                console.error('Fetch error:', err);
                Swal.fire({
                    icon: 'error',
                    title: 'Kesalahan Sistem',
                    text: err.message || 'Gagal menghubungi server.',
                    footer: 'Hubungi IT support jika masalah berlanjut'
                });
            });
    });

    async function prosesPrediksiUlang(event) {
        const btn = event.currentTarget;
        const originalText = btn.innerHTML;

        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Mengkalkulasi AI...';
        btn.disabled = true;

        try {
            const resTraining = await fetch('../api/api_data.php?action=get_training_data');
            const dataset = await resTraining.json();

            const getUmurCat = (val) => {
                const valF = parseFloat(val);
                if (isNaN(valF)) return ">= 40 Tahun";
                return valF < 40 ? "< 40 Tahun" : ">= 40 Tahun";
            };

            const datasetKalkulasi = dataset.map(d => ({
                ...d,
                umur: getUmurCat(d.umur)
            }));

            const tgl = document.getElementById('edit_tanggal_lahir').value;
            const umur = Math.abs(new Date(Date.now() - new Date(tgl).getTime()).getUTCFullYear() - 1970);

            const p = {
                jenis_kelamin: document.getElementById('edit_jenis_kelamin').value,
                merokok: document.getElementById('edit_merokok').value,
                konsumsi_alkohol: document.getElementById('edit_konsumsi_alkohol').value,
                kurang_buah_sayur: document.getElementById('edit_kurang_buah_sayur').value,
                diabetes: document.getElementById('edit_diabetes').value,
                riwayat_hipertensi: document.getElementById('edit_riwayat_hipertensi').value,
                umur: umur,
                tekanan_sistolik: parseFloat(document.getElementById('edit_tekanan_sistolik').value),
                tekanan_diastolik: parseFloat(document.getElementById('edit_tekanan_diastolik').value),
                imt: parseFloat(document.getElementById('edit_imt').value)
            };

            const pKalkulasi = {
                ...p,
                umur: getUmurCat(p.umur)
            };

            const cT = datasetKalkulasi.filter(d => d.hasil_prediksi === 'Tinggi').length;
            const cR = datasetKalkulasi.filter(d => d.hasil_prediksi === 'Rendah').length;
            let logT = Math.log((cT + 1) / (datasetKalkulasi.length + 2));
            let logR = Math.log((cR + 1) / (datasetKalkulasi.length + 2));

            const categorical = ['umur', 'jenis_kelamin', 'merokok', 'konsumsi_alkohol', 'kurang_buah_sayur', 'diabetes', 'riwayat_hipertensi'];
            categorical.forEach(attr => {
                const subT = datasetKalkulasi.filter(d => d.hasil_prediksi === 'Tinggi');
                const subR = datasetKalkulasi.filter(d => d.hasil_prediksi === 'Rendah');
                const uniqVals = new Set(datasetKalkulasi.map(d => d[attr])).size;
                logT += Math.log((subT.filter(d => d[attr] === pKalkulasi[attr]).length + 1) / (subT.length + uniqVals));
                logR += Math.log((subR.filter(d => d[attr] === pKalkulasi[attr]).length + 1) / (subR.length + uniqVals));
            });

            function getMeanVar(attr, kelas) {
                const subset = datasetKalkulasi.filter(d => d.hasil_prediksi === kelas);
                const values = subset.map(d => parseFloat(d[attr]));
                const mean = values.reduce((a, b) => a + b, 0) / values.length;
                const variance = values.reduce((a, b) => a + Math.pow(b - mean, 2), 0) / values.length;
                return {
                    mean,
                    variance
                };
            }

            function gaussianLikelihood(x, mean, varc) {
                if (varc <= 0) varc = 0.0001;
                const exp = Math.exp(-Math.pow(x - mean, 2) / (2 * varc));
                const likelihood = (1 / Math.sqrt(2 * Math.PI * varc)) * exp;
                return Math.max(likelihood, 1e-9);
            }

            const numeric = ['tekanan_sistolik', 'tekanan_diastolik', 'imt'];
            numeric.forEach(attr => {
                const val = parseFloat(pKalkulasi[attr]);
                const sT = getMeanVar(attr, 'Tinggi');
                const sR = getMeanVar(attr, 'Rendah');

                logT += Math.log(gaussianLikelihood(val, sT.mean, sT.variance));
                logR += Math.log(gaussianLikelihood(val, sR.mean, sR.variance));
            });

            const finalResult = (logT > logR) ? 'Tinggi' : 'Rendah';
            document.getElementById('hasil_prediksi_baru').value = finalResult;

            Swal.fire({
                title: 'Diagnosa Ulang Berhasil',
                html: `Hasil Analisis AI: <br><strong class="fs-4 text-${finalResult == 'Tinggi' ? 'danger' : 'success'}">Risiko ${finalResult}</strong>`,
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                const form = document.getElementById('formEditLaporan');
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
                }
            });

        } catch (err) {
            console.error(err);
            Swal.fire('Error', 'Gagal memproses data AI', 'error');
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }

    function eksporPDF() {
        const el = document.getElementById('pdfContent');
        el.style.display = 'block';

        const opt = {
            margin: 10,
            filename: 'Laporan_Puskesmas_Cerme.pdf',
            image: {
                type: 'jpeg',
                quality: 0.98
            },
            html2canvas: {
                scale: 2,
                useCORS: true
            },
            jsPDF: {
                unit: 'mm',
                format: 'a4',
                orientation: 'portrait'
            }
        };

        html2pdf().from(el).set(opt).save().then(() => {
            el.style.display = 'none';
        });
    }

    function confirmDelete(id) {
        Swal.fire({
            title: 'Hapus Laporan?',
            text: "Data diagnosa ini akan dihapus permanen dari riwayat!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`../api/hapus_prediksi.php?id=${id}`).then(() => {
                    Swal.fire('Terhapus!', 'Data riwayat telah dihapus.', 'success').then(() => location.reload());
                });
            }
        });
    }

    const logoGresikUrl = <?php echo json_encode($logo_gresik_url); ?>;
    const logoPuskesmasUrl = <?php echo json_encode($logo_puskesmas_url); ?>;

    const dataLaporan = <?php echo json_encode(array_map(function($p) {
        return [
            'Tanggal Prediksi' => date('d/m/Y', strtotime($p['tanggal_prediksi'])),
            'Nama Pasien' => $p['nama'] . ' (' . $p['umur'] . ' th)',
            'Desa' => $p['alamat'],
            'Tensi & IMT' => $p['tekanan_sistolik'] . '/' . $p['tekanan_diastolik'] . ' | IMT: ' . number_format((float)$p['imt'], 2, '.', ''),
            'Hasil' => $p['hasil_prediksi']
        ];
    }, $laporan)); ?>;

    function eksporExcel() {
        if (!dataLaporan || dataLaporan.length === 0) {
            Swal.fire('Info', 'Tidak ada data untuk diekspor.', 'info');
            return;
        }

        // Tampilkan loader sebentar karena merender gambar
        Swal.fire({
            title: 'Mempersiapkan Excel...',
            html: 'Sedang merender logo dan data laporan secara presisi...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        setTimeout(() => {
            // Bangun baris-baris data HTML
            let dataRowsHtml = "";
            dataLaporan.forEach(item => {
                const hasilStyle = item['Hasil'] === 'Tinggi' ? 'color: #ef4444; font-weight: bold;' : 'color: #10b981; font-weight: bold;';
                dataRowsHtml += `
                    <tr>
                        <td style="border: 1px solid black; padding: 6px; text-align: center; font-family: sans-serif; font-size: 11px;">${item['Tanggal Prediksi']}</td>
                        <td style="border: 1px solid black; padding: 6px; font-family: sans-serif; font-size: 11px;">${item['Nama Pasien']}</td>
                        <td style="border: 1px solid black; padding: 6px; text-align: center; font-family: sans-serif; font-size: 11px;">${item['Desa']}</td>
                        <td style="border: 1px solid black; padding: 6px; text-align: center; font-family: sans-serif; font-size: 11px;">${item['Tensi & IMT']}</td>
                        <td style="border: 1px solid black; padding: 6px; text-align: center; font-family: sans-serif; font-size: 11px; ${hasilStyle}">${item['Hasil']}</td>
                    </tr>
                `;
            });

            // Bangun dokumen HTML Excel dengan KOP SURAT BERGAMBAR DAN BERGAYA PERSIS PDF (menggunakan HTTP URL absolut)
            const excelHtml = `
                <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
                <head>
                    <!--[if gte mso 9]>
                    <xml>
                        <x:ExcelWorkbook>
                            <x:ExcelWorksheets>
                                <x:ExcelWorksheet>
                                    <x:Name>Laporan Kesehatan</x:Name>
                                    <x:WorksheetOptions>
                                        <x:DisplayGridlines/>
                                    </x:WorksheetOptions>
                                </x:ExcelWorksheet>
                            </x:ExcelWorksheets>
                        </x:ExcelWorkbook>
                    </xml>
                    <![endif]-->
                    <meta http-equiv="content-type" content="text/plain; charset=UTF-8"/>
                </head>
                <body>
                    <!-- Tabel Kop Surat Bergambar dan Terformat -->
                    <table style="width: 100%; border: none; border-collapse: collapse;">
                        <!-- Baris 1: PEMERINTAH KABUPATEN GRESIK -->
                        <tr>
                            <td rowspan="4" align="center" valign="middle" style="width: 70px; border: none;">
                                <img src="${logoGresikUrl}" width="45">
                            </td>
                            <td colspan="3" align="center" valign="middle" style="border: none; font-family: 'Times New Roman', serif; font-weight: bold; font-size: 14px;">
                                PEMERINTAH KABUPATEN GRESIK
                            </td>
                            <td rowspan="4" align="center" valign="middle" style="width: 70px; border: none;">
                                <img src="${logoPuskesmasUrl}" width="45">
                            </td>
                        </tr>
                        <!-- Baris 2: DINAS KESEHATAN -->
                        <tr>
                            <td colspan="3" align="center" valign="middle" style="border: none; font-family: 'Times New Roman', serif; font-weight: bold; font-size: 14px;">
                                DINAS KESEHATAN
                            </td>
                        </tr>
                        <!-- Baris 3: UPT PUSKESMAS CERME -->
                        <tr>
                            <td colspan="3" align="center" valign="middle" style="border: none; font-family: 'Times New Roman', serif; font-weight: bold; font-size: 16px;">
                                UPT PUSKESMAS CERME
                            </td>
                        </tr>
                        <!-- Baris 4: ALAMAT -->
                        <tr>
                            <td colspan="3" align="center" valign="middle" style="border: none; font-family: 'Times New Roman', serif; font-size: 10px; font-style: italic;">
                                Jl. Raya Cerme Lor No. 123, Kec. Cerme, Gresik
                            </td>
                        </tr>
                        <!-- Baris garis pembatas kop (Garis Ganda Tebal) -->
                        <tr>
                            <td colspan="5" style="border: none; border-top: 3px double #000000; height: 5px; padding: 0;"></td>
                        </tr>
                        <!-- Padding -->
                        <tr>
                            <td colspan="5" style="border: none; height: 15px;"></td>
                        </tr>
                        <!-- Judul Laporan -->
                        <tr>
                            <td colspan="5" style="text-align: center; border: none; font-family: 'Times New Roman', serif; font-weight: bold; font-size: 13px; text-decoration: underline;">
                                LAPORAN HASIL DIAGNOSA KESEHATAN (NAIVE BAYES)
                            </td>
                        </tr>
                        <!-- Padding -->
                        <tr>
                            <td colspan="5" style="border: none; height: 15px;"></td>
                        </tr>
                    </table>

                    <!-- Tabel Data Laporan Diagnosa -->
                    <table style="width: 100%; border-collapse: collapse; border: 1px solid black;">
                        <thead>
                            <tr style="background-color: #f2f2f2;">
                                <th style="border: 1px solid black; padding: 8px; font-weight: bold; text-align: center; font-family: sans-serif; font-size: 11px; width: 100px;">Tgl Prediksi</th>
                                <th style="border: 1px solid black; padding: 8px; font-weight: bold; text-align: center; font-family: sans-serif; font-size: 11px; width: 250px;">Nama Pasien</th>
                                <th style="border: 1px solid black; padding: 8px; font-weight: bold; text-align: center; font-family: sans-serif; font-size: 11px; width: 150px;">Desa</th>
                                <th style="border: 1px solid black; padding: 8px; font-weight: bold; text-align: center; font-family: sans-serif; font-size: 11px; width: 200px;">Tensi & IMT</th>
                                <th style="border: 1px solid black; padding: 8px; font-weight: bold; text-align: center; font-family: sans-serif; font-size: 11px; width: 100px;">Hasil</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${dataRowsHtml}
                        </tbody>
                    </table>
                </body>
                </html>
            `;

            // Unduh file XLS Excel menggunakan Blob HTML
            const blob = new Blob([excelHtml], { type: 'application/vnd.ms-excel;charset=utf-8' });
            const link = document.createElement("a");
            link.href = URL.createObjectURL(blob);
            link.download = "Laporan_Hasil_Diagnosa_Puskesmas_Cerme.xls";
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            Swal.close();
        }, 800);
    }
</script>

<?php include '../includes/footer.php'; ?>