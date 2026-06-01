<?php
require_once '../config/koneksi.php';
require_once '../includes/auth_check.php';

// Proses Tambah / Replace Data
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_pasien'])) {
    try {
        $pdo->beginTransaction();

        // CEK APAKAH INI MODE REPLACE (Konfirmasi NIK Sama)
        if (isset($_POST['is_replace']) && !empty($_POST['is_replace'])) {
            $id_pasien = $_POST['is_replace'];

            // 1. Update (Replace) tabel pasien dengan data baru
            $stmt_p = $pdo->prepare("UPDATE pasien SET nama=?, jenis_kelamin=?, tanggal_lahir=?, alamat=?, no_hp=? WHERE id_pasien=?");
            $stmt_p->execute([
                $_POST['nama'],
                $_POST['jenis_kelamin'],
                $_POST['tanggal_lahir'],
                $_POST['alamat'],
                $_POST['no_hp'],
                $id_pasien
            ]);

            // 2. Insert ke tabel atribut_kesehatan sebagai riwayat periksa baru
            $stmt_ak = $pdo->prepare("INSERT INTO atribut_kesehatan 
                (id_pasien, tanggal_pemeriksaan, tekanan_sistolik, tekanan_diastolik, imt, merokok, konsumsi_alkohol, kurang_buah_sayur, diabetes, riwayat_hipertensi) 
                VALUES (?, CURDATE(), ?,?,?,?,?,?,?,?)");
            $stmt_ak->execute([
                $id_pasien,
                $_POST['tekanan_sistolik'],
                $_POST['tekanan_diastolik'],
                $_POST['imt'],
                $_POST['merokok'],
                $_POST['konsumsi_alkohol'],
                $_POST['kurang_buah_sayur'],
                $_POST['diabetes'],
                $_POST['riwayat_hipertensi']
            ]);

            $pdo->commit();
            header("Location: data_pasien.php?pesan=replace_sukses");
            exit;
        } else {
            // Insert ke tabel pasien
            $stmt_p = $pdo->prepare("INSERT INTO pasien (nik, nama, jenis_kelamin, tanggal_lahir, alamat, no_hp) VALUES (?,?,?,?,?,?)");
            $stmt_p->execute([
                $_POST['nik'],
                $_POST['nama'],
                $_POST['jenis_kelamin'],
                $_POST['tanggal_lahir'],
                $_POST['alamat'],
                $_POST['no_hp']
            ]);
            $id_pasien = $pdo->lastInsertId();

            // Insert ke tabel atribut_kesehatan
            $stmt_ak = $pdo->prepare("INSERT INTO atribut_kesehatan 
                (id_pasien, tanggal_pemeriksaan, tekanan_sistolik, tekanan_diastolik, imt, merokok, konsumsi_alkohol, kurang_buah_sayur, diabetes, riwayat_hipertensi) 
                VALUES (?, CURDATE(), ?,?,?,?,?,?,?,?)");
            $stmt_ak->execute([
                $id_pasien,
                $_POST['tekanan_sistolik'],
                $_POST['tekanan_diastolik'],
                $_POST['imt'],
                $_POST['merokok'],
                $_POST['konsumsi_alkohol'],
                $_POST['kurang_buah_sayur'],
                $_POST['diabetes'],
                $_POST['riwayat_hipertensi']
            ]);

            $pdo->commit();
            header("Location: data_pasien.php?sukses=1");
            exit;
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }
}

// Proses Edit Data Pasien
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_pasien'])) {
    try {
        $pdo->beginTransaction();

        // 1. Update ke tabel pasien
        $stmt_p = $pdo->prepare("UPDATE pasien SET nik=?, nama=?, jenis_kelamin=?, tanggal_lahir=?, alamat=?, no_hp=? WHERE id_pasien=?");
        $stmt_p->execute([
            $_POST['nik'],
            $_POST['nama'],
            $_POST['jenis_kelamin'],
            $_POST['tanggal_lahir'],
            $_POST['alamat'],
            $_POST['no_hp'],
            $_POST['id_pasien']
        ]);

        // 2. Update ke tabel atribut_kesehatan
        $stmt_ak = $pdo->prepare("UPDATE atribut_kesehatan SET tekanan_sistolik=?, tekanan_diastolik=?, imt=?, merokok=?, konsumsi_alkohol=?, kurang_buah_sayur=?, diabetes=?, riwayat_hipertensi=? WHERE id_atribut=?");
        $stmt_ak->execute([
            $_POST['tekanan_sistolik'],
            $_POST['tekanan_diastolik'],
            $_POST['imt'],
            $_POST['merokok'],
            $_POST['konsumsi_alkohol'],
            $_POST['kurang_buah_sayur'],
            $_POST['diabetes'],
            $_POST['riwayat_hipertensi'],
            $_POST['id_atribut']
        ]);

        $pdo->commit();
        header("Location: data_pasien.php?pesan=edit_sukses");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error Edit: " . $e->getMessage());
    }
}

// Proses Hapus Data Pasien
if (isset($_GET['hapus_pasien'])) {
    try {
        $id_hapus = $_GET['hapus_pasien'];

        // Hapus atribut kesehatannya terlebih dahulu (mencegah foreign key constraint)
        $stmt1 = $pdo->prepare("DELETE FROM atribut_kesehatan WHERE id_pasien = ?");
        $stmt1->execute([$id_hapus]);

        // Kemudian hapus pasiennya
        $stmt2 = $pdo->prepare("DELETE FROM pasien WHERE id_pasien = ?");
        $stmt2->execute([$id_hapus]);

        header("Location: data_pasien.php?pesan=hapus_sukses");
        exit;
    } catch (Exception $e) {
        die("Error Hapus: " . $e->getMessage());
    }
}

// Ambil data pasien yang atribut kesehatannya belum ada di tabel hasil_prediksi
$query = "SELECT p.*, ak.*, TIMESTAMPDIFF(YEAR, p.tanggal_lahir, CURDATE()) AS umur 
          FROM pasien p 
          JOIN atribut_kesehatan ak ON p.id_pasien = ak.id_pasien 
          LEFT JOIN hasil_prediksi hp ON ak.id_atribut = hp.id_atribut_kesehatan
          WHERE hp.id_prediksi IS NULL ORDER BY ak.id_atribut DESC";
$pasien = $pdo->query($query)->fetchAll();
$jumlah_antrean = count($pasien);

// Cek jumlah data latih (training data)
$count_training = $pdo->query("SELECT COUNT(*) FROM hasil_prediksi")->fetchColumn();

include '../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <h2 class="mb-0 fs-4 fw-bold"><i class="fas fa-users-medical text-primary me-2"></i>Data Pasien <span class="d-none d-sm-inline badge bg-warning text-dark align-middle ms-2" style="font-size:12px;">Perlu Diagnosa</span></h2>
    <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-outline-warning diagnosa border-2 rounded-pill shadow-sm flex-fill flex-md-grow-0 fw-bold" style="padding: 10px 24px; letter-spacing: 0.3px; box-shadow: 0 8px 15px rgba(0, 114, 255, 0.2) !important; transition: all 0.3s ease !important;" data-bs-toggle="modal" data-bs-target="#batchPredictModal" <?= $jumlah_antrean == 0 ? 'disabled' : '' ?>>
            <i class="fas fa-clipboard-list me-1"></i><span class="d-none d-sm-inline">Diagnosa Pasien</span>
        </button>
        <button class="btn btn-outline-success border-2 rounded-pill shadow-sm flex-fill flex-md-grow-0 fw-bold" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="fas fa-file-excel me-1"></i><span class="d-none d-sm-inline">Import Excel</span>
        </button>
        <button class="btn btn-primary bg-primary border-0 shadow-sm rounded-pill flex-fill flex-md-grow-0 fw-bold" data-bs-toggle="modal" data-bs-target="#tambahModal">
            <i class="fas fa-plus-circle me-1"></i><span class="d-none d-sm-inline">Pasien Baru</span>
        </button>
    </div>
</div>

<!-- Alert Messages -->
<?php if (isset($_GET['sukses'])): ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-4">
        <i class="fas fa-check-circle me-2"></i>Data identitas dan atribut kesehatan pasien baru berhasil dimasukkan ke sistem antrean!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['pesan']) && $_GET['pesan'] == 'replace_sukses'): ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-4">
        <i class="fas fa-check-double me-2"></i>Data biodata lama berhasil di-replace, dan data pemeriksaan baru telah ditambahkan ke antrean!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['pesan']) && $_GET['pesan'] == 'edit_sukses'): ?>
    <div class="alert alert-info alert-dismissible fade show border-0 shadow-sm rounded-4">
        <i class="fas fa-check-circle me-2"></i>Perubahan data pasien berhasil disimpan ke dalam sistem!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['pesan']) && $_GET['pesan'] == 'hapus_sukses'): ?>
    <div class="alert alert-secondary alert-dismissible fade show border border-secondary border-opacity-25 shadow-sm rounded-4 text-dark">
        <i class="fas fa-trash-alt me-2 text-danger"></i>Data antrean pasien berhasil dihapus secara permanen.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- INDIKATOR STATUS DATA LATIH AI -->
<?php if ($count_training < 30): ?>
    <div class="alert alert-warning border-0 shadow-sm rounded-4 d-flex align-items-center mb-4">
        <i class="fas fa-exclamation-triangle fs-3 me-3 text-warning"></i>
        <div>
            <strong>Status Data Latih Model AI: <span class="text-danger"><?= $count_training ?> Data</span> (Kurang Optimal)</strong><br>
            <span class="small text-dark">Sistem Machine Learning Naïve Bayes merekomendasikan minimal <strong>30 data riwayat</strong> yang sudah terdiagnosa agar akurasi matematika optimal. Silakan <strong>Import Excel</strong> data rekam medis terdahulu.</span>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-info shadow-sm rounded-4 d-flex align-items-center mb-4">
        <i class="fas fa-brain fs-3 me-3 text-primary"></i>
        <div>
            <strong class="text-primary">Kesiapan Model Naive Bayes : Optimal (Total Data: <?= $count_training ?>)</strong><br>
            <span class="small text-muted">Jumlah data latih sudah memadai. Akurasi klasifikasi sistem akan terus meningkat secara otomatis seiring bertambahnya data pasien baru yang Anda simpan.</span>
        </div>
    </div>
<?php endif; ?>

<div class="card glass-card hover-card border-0 shadow-sm mb-4" style="border-radius: 1.5rem;">
    <div class="card-body p-4">
        <div class="table-responsive custom-scrollbar">
            <table id="tablePasien" class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase" style="letter-spacing: 0.5px;">
                    <tr>
                        <th class="border-0 pb-3 text-nowrap">Tgl Periksa</th>
                        <th class="border-0 pb-3 text-nowrap">NIK</th>
                        <th class="border-0 pb-3 text-nowrap">Nama Pasien</th>
                        <th class="border-0 pb-3 text-nowrap">L/P</th>
                        <th class="border-0 pb-3 text-nowrap">Umur</th>
                        <th class="border-0 pb-3 text-nowrap">Desa (Alamat)</th>
                        <th class="border-0 pb-3 text-nowrap text-center">Tensi (S/D)</th>
                        <th class="border-0 pb-3 text-nowrap text-end">Aksi Sistem</th>
                    </tr>
                </thead>
                <tbody class="border-top-0 bg-white">
                    <?php foreach ($pasien as $p): ?>
                        <tr>
                            <td class="text-nowrap text-muted border-0 border-bottom"><?= date('d M Y', strtotime($p['tanggal_pemeriksaan'])) ?></td>
                            <td class="border-0 border-bottom font-monospace text-secondary"><?= htmlspecialchars($p['nik']) ?></td>
                            <td class="fw-bold border-0 border-bottom text-dark text-nowrap">
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-size: 14px;">
                                        <?= strtoupper(substr($p['nama'], 0, 1)) ?>
                                    </div>
                                    <?= htmlspecialchars($p['nama']) ?>
                                </div>
                            </td>
                            <td class="border-0 border-bottom"><?= $p['jenis_kelamin'] == 'laki-laki' ? '<i class="fas fa-mars text-primary"></i>' : '<i class="fas fa-venus text-danger"></i>' ?></td>
                            <td class="text-nowrap border-0 border-bottom"><?= $p['umur'] ?> <small class="text-muted">thn</small></td>
                            <td class="text-nowrap border-0 border-bottom text-muted"><i class="fas fa-map-pin me-1 opacity-50"></i><?= htmlspecialchars($p['alamat']) ?></td>
                            <td class="text-nowrap text-center border-0 border-bottom fw-bold" style="color: #475569;"><?= $p['tekanan_sistolik'] ?>/<?= $p['tekanan_diastolik'] ?></td>
                            <td class="text-end border-0 border-bottom">
                                <!-- Aksi Edit, Hapus, Diagnosa -->
                                <div class="d-flex justify-content-end gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-warning shadow-sm border-0 rounded-circle" style="width: 32px; height: 32px;"
                                        data-bs-toggle="modal" data-bs-target="#editModal"
                                        data-json="<?= htmlspecialchars(json_encode($p)) ?>"
                                        onclick="fillEditForm(this)" title="Edit Pasien">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger border-0 shadow-sm rounded-circle" style="width: 32px; height: 32px;"
                                        onclick="confirmDelete(<?= $p['id_pasien'] ?>)" title="Hapus Pasien">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                    <a href="prediksi.php?id_atribut=<?= $p['id_atribut'] ?>" class="btn btn-sm btn-primary bg-medical-blue border-0 shadow-sm text-nowrap rounded-pill px-3 ms-1 d-flex align-items-center" title="Mulai Diagnosa">
                                        <i class="fas fa-magic me-1"></i><span class="d-none d-xl-inline">Diagnosa</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Data Pasien -->
<div class="modal fade" id="tambahModal" tabindex="-1" aria-labelledby="tambahModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-fullscreen-lg-down">
        <div class="modal-content border-0 shadow" style="border-radius: 1rem;">
            <form method="POST" id="formTambahPasien">

                <!-- MODAL HEADER -->
                <div class="modal-header bg-primary bg-gradient text-white p-3 border-0" style="border-radius: 1rem 1rem 0 0;">
                    <h5 class="modal-title fw-bold fs-6" id="tambahModalLabel">
                        <i class="fas fa-clipboard-check me-2"></i>Form Registrasi & Pemeriksaan Fisik
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
                                    <input type="text" name="nik" class="form-control form-control-sm bg-light border-0" placeholder="Contoh: 3525..." required>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label small fw-bold text-muted mb-1">Nama Lengkap</label>
                                    <input type="text" name="nama" class="form-control form-control-sm bg-light border-0" placeholder="Nama Lengkap KTP" required>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label small fw-bold text-muted mb-1">Tanggal Lahir</label>
                                    <input type="date" name="tanggal_lahir" class="form-control form-control-sm bg-light border-0 text-muted" required>
                                </div>

                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label small fw-bold text-muted mb-1">Jenis Kelamin</label>
                                    <select name="jenis_kelamin" class="form-select form-select-sm bg-light border-0" required>
                                        <option value="" disabled selected>Pilih...</option>
                                        <option value="laki-laki">Laki-laki</option>
                                        <option value="perempuan">Perempuan</option>
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label small fw-bold text-muted mb-1">Desa Wilayah (Cerme)</label>
                                    <select name="alamat" class="form-select form-select-sm bg-light border-0" required>
                                        <option value="" disabled selected>Pilih Desa...</option>
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
                                    <input type="text" name="no_hp" class="form-control form-control-sm bg-light border-0" placeholder="08..." required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: INDIKATOR MEDIS -->
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
                                        <input type="number" min="50" max="300" name="tekanan_sistolik" class="form-control bg-light border-0" placeholder="120" required>
                                        <span class="input-group-text bg-light border-0 text-muted" style="font-size: 0.75rem;">mmHg</span>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label small fw-bold text-muted mb-1">Diastolik</label>
                                    <div class="input-group input-group-sm overflow-hidden rounded-2">
                                        <input type="number" min="30" max="200" name="tekanan_diastolik" class="form-control bg-light border-0" placeholder="80" required>
                                        <span class="input-group-text bg-light border-0 text-muted" style="font-size: 0.75rem;">mmHg</span>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label small fw-bold text-muted mb-1">Indeks Massa Tubuh (IMT)</label>
                                    <input type="number" step="0.01" min="10" max="60" name="imt" class="form-control form-control-sm bg-light border-0" placeholder="Misal: 24.5" required>
                                </div>

                                <!-- Anamnesa -->
                                <div class="col-6 col-md-4 col-lg-2">
                                    <label class="form-label small fw-bold text-muted mb-1">Riw. Hipertensi</label>
                                    <select name="riwayat_hipertensi" class="form-select form-select-sm bg-light border-0" required>
                                        <option value="Tidak">Tidak</option>
                                        <option value="Ya">Ya</option>
                                    </select>
                                </div>
                                <div class="col-6 col-md-4 col-lg-2">
                                    <label class="form-label small fw-bold text-muted mb-1">Diabetes</label>
                                    <select name="diabetes" class="form-select form-select-sm bg-light border-0" required>
                                        <option value="Tidak">Tidak</option>
                                        <option value="Ya">Ya</option>
                                    </select>
                                </div>
                                <div class="col-6 col-md-4 col-lg-2">
                                    <label class="form-label small fw-bold text-muted mb-1">Perokok Aktif</label>
                                    <select name="merokok" class="form-select form-select-sm bg-light border-0" required>
                                        <option value="Tidak">Tidak</option>
                                        <option value="Ya">Ya</option>
                                    </select>
                                </div>
                                <div class="col-6 col-md-6 col-lg-3">
                                    <label class="form-label small fw-bold text-muted mb-1">Konsumsi Alkohol</label>
                                    <select name="konsumsi_alkohol" class="form-select form-select-sm bg-light border-0" required>
                                        <option value="Tidak">Tidak</option>
                                        <option value="Ya">Ya</option>
                                    </select>
                                </div>
                                <div class="col-6 col-md-6 col-lg-3">
                                    <label class="form-label small fw-bold text-muted mb-1">Kurang Sayur/Buah</label>
                                    <select name="kurang_buah_sayur" class="form-select form-select-sm bg-light border-0" required>
                                        <option value="Tidak">Tidak</option>
                                        <option value="Ya">Ya</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- MODAL FOOTER -->
                <div class="modal-footer bg-white p-3 border-0 d-flex flex-wrap justify-content-end gap-2" style="border-radius: 0 0 1rem 1rem;">
                    <button type="button" class="btn btn-sm btn-light px-4 rounded-pill fw-bold text-muted shadow-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary px-4 rounded-pill fw-bold shadow-sm">
                        <i class="fas fa-save me-1"></i> Simpan & Antre Diagnosa
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Data Pasien -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-fullscreen-lg-down">
        <div class="modal-content border-0 shadow" style="border-radius: 1rem;">
            <form method="POST">
                <!-- HIDDEN IDS -->
                <input type="hidden" name="id_pasien" id="edit_id_pasien">
                <input type="hidden" name="id_atribut" id="edit_id_atribut">

                <!-- MODAL HEADER -->
                <div class="modal-header bg-primary bg-gradient text-white p-3 border-0" style="border-radius: 1rem 1rem 0 0;">
                    <h5 class="modal-title fw-bold fs-6" id="editModalLabel">
                        <i class="fas fa-user-edit me-2"></i>Edit Data Registrasi & Pemeriksaan Pasien
                    </h5>
                    <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- MODAL BODY -->
                <div class="modal-body p-3 bg-light">

                    <!-- SECTION 1: IDENTITAS PASIEN -->
                    <div class="card border-0 shadow-sm rounded-3 mb-3">
                        <div class="card-body p-3">
                            <h6 class="fw-bold text-primary border-bottom pb-2 mb-3 small">
                                <i class="fas fa-id-card flex-shrink-0 me-2"></i>Informasi Identitas Pasien
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label small fw-bold text-muted mb-1">Nomor Induk (NIK)</label>
                                    <input type="text" name="nik" id="edit_nik" class="form-control form-control-sm bg-light border-0" placeholder="Masukkan 16 digit NIK" required>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label small fw-bold text-muted mb-1">Nama Lengkap</label>
                                    <input type="text" name="nama" id="edit_nama" class="form-control form-control-sm bg-light border-0" placeholder="Sesuai KTP" required>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label small fw-bold text-muted mb-1">Tanggal Lahir</label>
                                    <input type="date" name="tanggal_lahir" id="edit_tanggal_lahir" class="form-control form-control-sm bg-light border-0 text-muted" required>
                                </div>

                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label small fw-bold text-muted mb-1">Jenis Kelamin</label>
                                    <select name="jenis_kelamin" id="edit_jenis_kelamin" class="form-select form-select-sm bg-light border-0" required>
                                        <option value="" disabled selected>Pilih...</option>
                                        <option value="laki-laki">Laki-laki</option>
                                        <option value="perempuan">Perempuan</option>
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label small fw-bold text-muted mb-1">Desa Wilayah (Cerme)</label>
                                    <select name="alamat" id="edit_alamat" class="form-select form-select-sm bg-light border-0" required>
                                        <option value="" disabled selected>Pilih Desa...</option>
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
                                        <!-- Sisanya bisa Anda tambahkan seperti sebelumnya -->
                                        <option value="Wedani">Wedani</option>
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label small fw-bold text-muted mb-1">No. Handphone / WA</label>
                                    <input type="text" name="no_hp" id="edit_no_hp" class="form-control form-control-sm bg-light border-0" placeholder="Contoh: 0812xxxx" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: ATRIBUT KESEHATAN -->
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-body p-3">
                            <h6 class="fw-bold text-success border-bottom pb-2 mb-3 small">
                                <i class="fas fa-stethoscope flex-shrink-0 me-2"></i>Hasil Pemeriksaan Klinis
                            </h6>
                            <div class="row g-3">
                                <!-- Vital Signs -->
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label small fw-bold text-muted mb-1">Sistolik</label>
                                    <div class="input-group input-group-sm overflow-hidden rounded-2">
                                        <input type="number" min="50" max="300" name="tekanan_sistolik" id="edit_tekanan_sistolik" class="form-control bg-light border-0" placeholder="120" required>
                                        <span class="input-group-text bg-light border-0 text-muted" style="font-size: 0.75rem;">mmHg</span>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label small fw-bold text-muted mb-1">Diastolik</label>
                                    <div class="input-group input-group-sm overflow-hidden rounded-2">
                                        <input type="number" min="30" max="200" name="tekanan_diastolik" id="edit_tekanan_diastolik" class="form-control bg-light border-0" placeholder="80" required>
                                        <span class="input-group-text bg-light border-0 text-muted" style="font-size: 0.75rem;">mmHg</span>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label small fw-bold text-muted mb-1">Indeks Massa Tubuh (IMT)</label>
                                    <input type="number" step="0.01" min="10" max="60" name="imt" id="edit_imt" class="form-control form-control-sm bg-light border-0" placeholder="22.5" required>
                                </div>

                                <!-- Anamnesa / Riwayat -->
                                <div class="col-md-6 col-lg-3">
                                    <label class="form-label small fw-bold text-muted mb-1">Riwayat Kel. Hipertensi</label>
                                    <select name="riwayat_hipertensi" id="edit_riwayat_hipertensi" class="form-select form-select-sm bg-light border-0" required>
                                        <option value="Tidak">Tidak Ada</option>
                                        <option value="Ya">Ada Riwayat</option>
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <label class="form-label small fw-bold text-muted mb-1">Riwayat Diabetes</label>
                                    <select name="diabetes" id="edit_diabetes" class="form-select form-select-sm bg-light border-0" required>
                                        <option value="Tidak">Tidak</option>
                                        <option value="Ya">Mengidap</option>
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-2">
                                    <label class="form-label small fw-bold text-muted mb-1">Merokok</label>
                                    <select name="merokok" id="edit_merokok" class="form-select form-select-sm bg-light border-0" required>
                                        <option value="Tidak">Tidak</option>
                                        <option value="Ya">Aktif</option>
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-2">
                                    <label class="form-label small fw-bold text-muted mb-1">Alkohol</label>
                                    <select name="konsumsi_alkohol" id="edit_konsumsi_alkohol" class="form-select form-select-sm bg-light border-0" required>
                                        <option value="Tidak">Tidak</option>
                                        <option value="Ya">Ya</option>
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-2">
                                    <label class="form-label small fw-bold text-muted mb-1">Gizi Sayur/Buah</label>
                                    <select name="kurang_buah_sayur" id="edit_kurang_buah_sayur" class="form-select form-select-sm bg-light border-0" required>
                                        <option value="Tidak">Baik</option>
                                        <option value="Ya">Kurang</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- MODAL FOOTER -->
                <div class="modal-footer bg-white p-3 border-0" style="border-radius: 0 0 1rem 1rem;">
                    <button type="button" class="btn btn-sm btn-light px-4 rounded-pill fw-bold text-muted shadow-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="edit_pasien" class="btn btn-sm btn-primary px-4 rounded-pill fw-bold shadow-sm">
                        <i class="fas fa-save me-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Import Excel -->
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

                <!-- Tambahan Pilihan Jenis Import -->
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

<!-- Modal Prediksi Massal -->
<div class="modal fade" id="batchPredictModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 1.5rem;">
            <div class="modal-header bg-warning text-dark p-4 border-0">
                <h5 class="modal-title fw-bold"><i class="fas fa-clipboard-list me-2"></i>Diagnosa Pasien berdasarkan Data</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <p class="text-muted small">Fitur ini akan menjalankan algoritma Naïve Bayes secara otomatis pada data antrean pasien dari urutan paling atas.</p>
                <div class="mb-3">
                    <label class="form-label fw-bold text-dark small">Tentukan Jumlah Data yang Ingin Dihitung</label>
                    <div class="input-group input-group-lg shadow-sm border-0 rounded-3 overflow-hidden">
                        <input type="number" id="batchCount" class="form-control border-0" min="1" max="<?= $jumlah_antrean ?>" value="<?= min(10, $jumlah_antrean) ?>">
                        <span class="input-group-text bg-white border-0 text-muted small fw-bold">Pasien</span>
                    </div>
                    <small class="text-muted mt-2 d-block"><i class="fas fa-info-circle me-1"></i>Terdapat total <strong><?= $jumlah_antrean ?></strong> pasien dalam antrean saat ini.</small>
                </div>
                <div id="batchProgressContainer" class="d-none mt-4">
                    <div class="d-flex justify-content-between text-muted small fw-bold mb-2">
                        <span><i class="fas fa-cog fa-spin me-1 text-warning"></i>Memproses Analisis Data...</span>
                        <span id="batchProgressText" class="text-dark">0 / 0</span>
                    </div>
                    <div class="progress rounded-pill shadow-sm" style="height: 10px; background-color: #f1f5f9;">
                        <div id="batchProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-warning" style="width: 0%"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white border-0 p-4" style="border-radius: 0 0 1.5rem 1.5rem;">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                <button type="button" id="btnProsesBatch" class="btn btn-warning rounded-pill px-4 shadow-sm fw-bold"><i class="fas fa-play-circle me-2"></i>Mulai Prediksi</button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Hilangkan CSS File Input Biasa */
    input[type=file]::file-selector-button {
        background: #e2e8f0;
        border: 0;
        border-right: 1px solid #cbd5e1;
        padding: .5rem 1rem;
        margin-right: 1rem;
        border-radius: .5rem;
        transition: .2s;
        font-weight: bold;
        color: #475569;
    }

    input[type=file]::file-selector-button:hover {
        background: #cbd5e1;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script>
    // --- SCRIPT PERUBAHAN TEKS HINT IMPORT ---
    function updateHint() {
        const hint = document.getElementById('importHint');
        if (document.getElementById('importUji').checked) {
            hint.innerHTML = '<i class="fas fa-info-circle me-1"></i> Data akan masuk ke antrean untuk didiagnosa. Tidak perlu kolom "Hasil Prediksi".';
            hint.classList.replace('text-primary', 'text-muted');
        } else {
            hint.innerHTML = '<i class="fas fa-brain me-1"></i> Data akan langsung disimpan ke Riwayat Laporan untuk melatih AI. <strong>Wajib memiliki kolom bernama "Hasil Prediksi" atau "Risiko" di file excel</strong> (isi dengan Tinggi/Rendah).';
            hint.classList.replace('text-muted', 'text-primary');
        }
    }

    // --- SCRIPT UNTUK KONFIRMASI HAPUS (SWEETALERT) ---
    function confirmDelete(id) {
        Swal.fire({
            title: 'Apakah Anda yakin?',
            text: "Data pasien beserta hasil pemeriksaan fisiknya yang belum didiagnosa ini akan dihapus secara permanen!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: 'Ya, Hapus Data',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'data_pasien.php?hapus_pasien=' + id;
            }
        });
    }

    // --- SCRIPT UNTUK MENCEGAH INPUT DOUBLE NIK DENGAN AJAX ---
    document.getElementById('formTambahPasien').addEventListener('submit', async function(e) {
        e.preventDefault(); // Hentikan proses submit asli
        const form = this;
        const nik = form.querySelector('input[name="nik"]').value;
        const submitBtn = form.querySelector('button[type="submit"]');

        // Ubah tombol jadi loading
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Mengecek NIK...';
        submitBtn.disabled = true;

        try {
            // Verifikasi NIK ke database melalui API
            const response = await fetch('../api/cek_nik.php?nik=' + encodeURIComponent(nik));

            if (!response.ok) {
                throw new Error(`HTTP Error: ${response.status}`);
            }

            const result = await response.json();

            submitBtn.innerHTML = originalBtnText;
            submitBtn.disabled = false;

            if (result.exists) {
                // Tampilkan peringatan Replace
                Swal.fire({
                    title: 'NIK Sudah Terdaftar!',
                    html: `Pasien dengan NIK <strong>${nik}</strong> (Nama: <strong>${result.nama}</strong>) sudah ada di sistem.<br><br>Apakah Anda ingin mereplace/memperbarui biodata pasien ini dan menambahkan hasil medis sebagai antrean pemeriksaan yang baru?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#0072FF',
                    cancelButtonColor: '#94a3b8',
                    confirmButtonText: 'Ya, Replace Data',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then((res) => {
                    if (res.isConfirmed) {
                        if (!document.getElementById('input_is_replace')) {
                            const inputReplace = document.createElement('input');
                            inputReplace.type = 'hidden';
                            inputReplace.name = 'is_replace';
                            inputReplace.id = 'input_is_replace';
                            inputReplace.value = result.id_pasien;
                            form.appendChild(inputReplace);
                        }

                        if (!document.getElementById('input_tambah_pasien')) {
                            const btnSubmit = document.createElement('input');
                            btnSubmit.type = 'hidden';
                            btnSubmit.name = 'tambah_pasien';
                            btnSubmit.id = 'input_tambah_pasien';
                            btnSubmit.value = '1';
                            form.appendChild(btnSubmit);
                        }

                        HTMLFormElement.prototype.submit.call(form);
                    }
                });
            } else {
                if (!document.getElementById('input_tambah_pasien')) {
                    const btnSubmit = document.createElement('input');
                    btnSubmit.type = 'hidden';
                    btnSubmit.name = 'tambah_pasien';
                    btnSubmit.id = 'input_tambah_pasien';
                    btnSubmit.value = '1';
                    form.appendChild(btnSubmit);
                }

                HTMLFormElement.prototype.submit.call(form);
            }
        } catch (error) {
            console.error("Fetch Error:", error);
            submitBtn.innerHTML = originalBtnText;
            submitBtn.disabled = false;
            Swal.fire('Koneksi Error', 'Gagal memverifikasi NIK. Pastikan file <b>api/cek_nik.php</b> sudah ada dan formatnya benar.<br>Detail: ' + error.message, 'error');
        }
    });

    function fillEditForm(btn) {
        const data = JSON.parse(btn.getAttribute('data-json'));

        document.getElementById('edit_id_pasien').value = data.id_pasien;
        document.getElementById('edit_id_atribut').value = data.id_atribut;
        document.getElementById('edit_nik').value = data.nik;
        document.getElementById('edit_nama').value = data.nama;
        document.getElementById('edit_tanggal_lahir').value = data.tanggal_lahir;
        document.getElementById('edit_jenis_kelamin').value = data.jenis_kelamin;

        const alamatSelect = document.getElementById('edit_alamat');
        const alamatValue = data.alamat ? data.alamat.trim() : '';
        let optionFound = false;

        for (let i = 0; i < alamatSelect.options.length; i++) {
            if (alamatSelect.options[i].value.toLowerCase() === alamatValue.toLowerCase()) {
                alamatSelect.selectedIndex = i;
                optionFound = true;
                break;
            }
        }

        if (!optionFound && alamatValue !== '') {
            let newOption = new Option(alamatValue, alamatValue);
            alamatSelect.add(newOption, undefined);
            alamatSelect.value = alamatValue;
        }

        document.getElementById('edit_no_hp').value = data.no_hp;
        document.getElementById('edit_tekanan_sistolik').value = data.tekanan_sistolik;
        document.getElementById('edit_tekanan_diastolik').value = data.tekanan_diastolik;
        document.getElementById('edit_imt').value = data.imt;
        document.getElementById('edit_riwayat_hipertensi').value = data.riwayat_hipertensi;
        document.getElementById('edit_diabetes').value = data.diabetes;
        document.getElementById('edit_merokok').value = data.merokok;
        document.getElementById('edit_konsumsi_alkohol').value = data.konsumsi_alkohol;
        document.getElementById('edit_kurang_buah_sayur').value = data.kurang_buah_sayur;
    }

    // --- SCRIPT EXCEL IMPORT ---
    document.getElementById('btnProsesImport').addEventListener('click', function() {
        const fileInput = document.getElementById('excelFile');
        const file = fileInput.files[0];
        const importProgress = document.getElementById('importProgress');
        const progressTextDiv = importProgress.children[0];
        const btnImport = document.getElementById('btnProsesImport');
        const jenisImport = document.querySelector('input[name="jenisImport"]:checked').value;

        if (!file) {
            Swal.fire('Perhatian', 'Harap lampirkan file excel terlebih dahulu!', 'warning');
            return;
        }

        const reader = new FileReader();
        reader.onload = async function(e) {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, {
                type: 'array',
                cellDates: true
            });
            const firstSheet = workbook.SheetNames[0];
            const rows = XLSX.utils.sheet_to_json(workbook.Sheets[firstSheet], {
                raw: false
            });

            if (rows.length === 0) {
                Swal.fire('Perhatian', 'File excel kosong tidak dapat diproses!', 'warning');
                return;
            }

            importProgress.classList.remove('d-none');
            btnImport.disabled = true;
            progressTextDiv.innerHTML = '<i class="fas fa-search fa-spin me-1"></i>Memverifikasi NIK dengan database...';

            const mappedData = rows.map(row => {
                let tglLahir = row['Tanggal Lahir'] || row['tanggal_lahir'] || '';
                if (tglLahir && !isNaN(Date.parse(tglLahir))) {
                    const d = new Date(tglLahir);
                    tglLahir = d.toISOString().split('T')[0];
                }

                let hasil_prediksi = null;
                if (jenisImport === 'latih') {
                    let rawHasil = row['Hasil Prediksi'] || row['hasil_prediksi'] || row['Risiko'] || row['risiko'] || 'Rendah';
                    if (rawHasil.toString().toLowerCase().includes('tinggi')) {
                        hasil_prediksi = 'Tinggi';
                    } else {
                        hasil_prediksi = 'Rendah';
                    }
                }

                return {
                    nik: (row['NIK'] || row['nik'] || '').toString().trim(),
                    nama: row['Nama'] || row['nama'] || '',
                    jenis_kelamin: (row['Jenis Kelamin'] || row['jenis_kelamin'] || '').toLowerCase().startsWith('l') ? 'laki-laki' : 'perempuan',
                    tanggal_lahir: tglLahir,
                    alamat: row['Alamat'] || row['alamat'] || '',
                    no_hp: row['No HP'] || row['no_hp'] || '',
                    tekanan_sistolik: parseInt(row['Sistolik'] || row['tekanan_sistolik'] || 0),
                    tekanan_diastolik: parseInt(row['Diastolik'] || row['tekanan_diastolik'] || 0),
                    imt: parseFloat(row['IMT'] || row['imt'] || 0),
                    merokok: row['Merokok'] || row['merokok'] || 'Tidak',
                    konsumsi_alkohol: row['Alkohol'] || row['konsumsi_alkohol'] || 'Tidak',
                    kurang_buah_sayur: row['Sayur/Buah'] || row['kurang_buah_sayur'] || 'Tidak',
                    diabetes: row['Diabetes'] || row['diabetes'] || 'Tidak',
                    riwayat_hipertensi: row['Keluarga Hipertensi'] || row['riwayat_hipertensi'] || 'Tidak',
                    hasil_prediksi: hasil_prediksi
                };
            });

            let existingPatients = [];
            try {
                const checkPromises = mappedData.map(async (row) => {
                    if (!row.nik) return null;
                    const res = await fetch('../api/cek_nik.php?nik=' + encodeURIComponent(row.nik));
                    if (!res.ok) return null;
                    const resultData = await res.json();
                    if (resultData.exists) {
                        return {
                            nik: row.nik,
                            nama: resultData.nama
                        };
                    }
                    return null;
                });

                const checkResults = await Promise.all(checkPromises);
                existingPatients = checkResults.filter(item => item !== null);
            } catch (err) {
                console.warn("Pengecekan NIK massal terlewati karena error koneksi", err);
            }

            importProgress.classList.add('d-none');

            const proceedWithImport = () => {
                importProgress.classList.remove('d-none');
                progressTextDiv.innerHTML = '<i class="fas fa-cog fa-spin me-1"></i>Sistem sedang mengekstraksi dan mengimpor data...';
                btnImport.disabled = true;

                fetch('../api/import_pasien.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(mappedData)
                    })
                    .then(async response => {
                        const text = await response.text();
                        if (!response.ok) throw new Error(text);
                        try {
                            const start = text.indexOf('{');
                            const end = text.lastIndexOf('}');
                            if (start !== -1 && end !== -1) {
                                return JSON.parse(text.substring(start, end + 1));
                            }
                            return JSON.parse(text);
                        } catch (e) {
                            throw new Error("Format respon server tidak valid/rusak.");
                        }
                    })
                    .then(result => {
                        if (result.success) {
                            let infoTarget = jenisImport === 'latih' ? 'sebagai Data Latih AI.' : 'ke Antrean Pasien.';
                            Swal.fire('Import Selesai', result.message + " " + infoTarget, 'success').then(() => location.reload());
                        } else {
                            let msg = result.message;
                            if (result.errors && result.errors.length > 0) {
                                msg += "<br><br><small class='text-danger'>" + result.errors[0] + "</small>";
                            }
                            Swal.fire({
                                title: 'Gagal Membaca File',
                                html: msg,
                                icon: 'error'
                            });
                        }
                    })
                    .catch(err => {
                        Swal.fire({
                            title: 'Koneksi / Sintaks Error',
                            html: 'Gagal mengirim data ke server!<br><small class="text-danger">' + err.message + '</small>',
                            icon: 'error'
                        });
                    })
                    .finally(() => {
                        importProgress.classList.add('d-none');
                        btnImport.disabled = false;
                    });
            };

            if (existingPatients.length > 0) {
                let patientNames = existingPatients.slice(0, 3).map(p => `<li>${p.nama} (NIK: ${p.nik})</li>`).join('');
                let moreText = existingPatients.length > 3 ? `<li class="text-muted small">...dan ${existingPatients.length - 3} data lainnya</li>` : '';

                Swal.fire({
                    title: 'Ditemukan NIK Terdaftar!',
                    html: `Sistem mendeteksi <strong>${existingPatients.length} NIK</strong> pada file Excel yang sudah terdaftar di database, di antaranya:<br>
                           <ul class="text-start mt-3 mb-3 text-danger fw-bold" style="font-size:14px;">
                           ${patientNames}
                           ${moreText}
                           </ul>
                           Apakah Anda ingin <strong>mereplace/memperbarui</strong> biodata medis mereka dengan data terbaru dari Excel ini?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#0072FF',
                    cancelButtonColor: '#94a3b8',
                    confirmButtonText: 'Ya, Replace & Import',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then((res) => {
                    if (res.isConfirmed) proceedWithImport();
                    else {
                        btnImport.disabled = false;
                        fileInput.value = '';
                    }
                });
            } else {
                proceedWithImport();
            }
        };
        reader.readAsArrayBuffer(file);
    });

    // --- SCRIPT PREDIKSI MASSAL (BATCH) ---
    document.getElementById('btnProsesBatch')?.addEventListener('click', async function() {
        const countInput = document.getElementById('batchCount').value;
        const count = parseInt(countInput);
        const pasienBelum = <?php echo json_encode($pasien); ?>;

        if (isNaN(count) || count < 1 || count > pasienBelum.length) {
            Swal.fire('Perhatian', 'Input jumlah data tidak valid atau melebihi antrean!', 'warning');
            return;
        }

        const targetPasien = pasienBelum.slice(0, count);

        document.getElementById('btnProsesBatch').disabled = true;
        document.getElementById('batchCount').disabled = true;
        document.getElementById('batchProgressContainer').classList.remove('d-none');
        const progressBar = document.getElementById('batchProgressBar');
        const progressText = document.getElementById('batchProgressText');

        try {
            const resTraining = await fetch('../api/api_data.php?action=get_training_data');
            const trainingData = await resTraining.json();

            if (trainingData.length === 0) {
                Swal.fire('Peringatan', 'Belum ada data latih (training data) untuk referensi sistem. AI belum bisa memprediksi.', 'error');
                document.getElementById('btnProsesBatch').disabled = false;
                return;
            }

            const getUmurCat = (val) => {
                const valF = parseFloat(val);
                if (isNaN(valF)) return ">= 40 Tahun";
                return valF < 40 ? "< 40 Tahun" : ">= 40 Tahun";
            };

            const trainingDataKalkulasi = trainingData.map(d => ({
                ...d,
                umur: getUmurCat(d.umur)
            }));

            const cTinggi = trainingDataKalkulasi.filter(d => d.hasil_prediksi === 'Tinggi').length;
            const cRendah = trainingDataKalkulasi.filter(d => d.hasil_prediksi === 'Rendah').length;
            const pTinggi = cTinggi / trainingDataKalkulasi.length;
            const pRendah = cRendah / trainingDataKalkulasi.length;

            function countKategorik(attr, valObj, kelas) {
                const subset = trainingDataKalkulasi.filter(d => d.hasil_prediksi === kelas);
                const c = subset.filter(d => d[attr] === valObj).length;
                const uniqueValues = new Set(trainingDataKalkulasi.map(d => d[attr])).size;
                return (c + 1) / (subset.length + uniqueValues); // Laplace Smoothing
            }

            function getMeanVar(attr, kelas) {
                const subset = trainingDataKalkulasi.filter(d => d.hasil_prediksi === kelas);
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

            // Desa dihilangkan dari kalkulasi massal
            const attrsCat = ['umur', 'jenis_kelamin', 'merokok', 'konsumsi_alkohol', 'kurang_buah_sayur', 'diabetes', 'riwayat_hipertensi'];
            const attrsNum = ['tekanan_sistolik', 'tekanan_diastolik', 'imt'];

            let successCount = 0;

            for (let i = 0; i < targetPasien.length; i++) {
                const p = targetPasien[i];
                const pKalkulasi = {
                    ...p,
                    umur: getUmurCat(p.umur)
                };

                let logTinggi = pTinggi > 0 ? Math.log(pTinggi) : Math.log(1e-9);
                let logRendah = pRendah > 0 ? Math.log(pRendah) : Math.log(1e-9);

                attrsCat.forEach(attr => {
                    const val = pKalkulasi[attr];
                    logTinggi += Math.log(countKategorik(attr, val, 'Tinggi'));
                    logRendah += Math.log(countKategorik(attr, val, 'Rendah'));
                });

                attrsNum.forEach(attr => {
                    const val = parseFloat(pKalkulasi[attr]);
                    const sT = getMeanVar(attr, 'Tinggi');
                    const sR = getMeanVar(attr, 'Rendah');

                    logTinggi += Math.log(gaussianLikelihood(val, sT.mean, sT.variance));
                    logRendah += Math.log(gaussianLikelihood(val, sR.mean, sR.variance));
                });

                const finalPrediction = (logTinggi > logRendah) ? 'Tinggi' : 'Rendah';

                const saveRes = await fetch('../api/simpan_prediksi.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id_pasien: p.id_pasien,
                        id_atribut: p.id_atribut,
                        hasil_prediksi: finalPrediction
                    })
                });

                const saveResult = await saveRes.json();
                if (saveResult.status === 'success') {
                    successCount++;
                }

                const pct = Math.round(((i + 1) / count) * 100);
                progressBar.style.width = pct + '%';
                progressText.innerText = (i + 1) + ' / ' + count;
            }

            Swal.fire({
                icon: 'success',
                title: 'Prediksi Selesai',
                text: successCount + ' data pasien berhasil dihitung & disimpan!',
                confirmButtonColor: '#10b981'
            }).then(() => {
                location.reload();
            });

        } catch (err) {
            Swal.fire('Error System', 'Terjadi kesalahan sistem: ' + err.toString(), 'error');
            document.getElementById('btnProsesBatch').disabled = false;
        }
    });
</script>

<?php include '../includes/footer.php'; ?>