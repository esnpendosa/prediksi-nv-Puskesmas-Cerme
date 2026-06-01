<?php
require_once '../config/koneksi.php';

// Lindungi halaman
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id_pengguna = $_SESSION['user_id'];
$msg = '';
$err = '';

// Jika tombol Simpan / Ganti Profil ditekan
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $password_baru = trim($_POST['password_baru']);

    // 1. Handle Upload Gambar
    $foto_path = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../assets/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $tmpName = $_FILES['foto']['tmp_name'];
        $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9.-]/', '_', $_FILES['foto']['name']);
        $destPath = $uploadDir . $fileName;

        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $fileType = mime_content_type($tmpName);

        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($tmpName, $destPath)) {
                $foto_path = $destPath;
            } else {
                $err = "Gagal memindahkan file yang diunggah.";
            }
        } else {
            $err = "Hanya file JPG, PNG, dan WebP yang diizinkan untuk profil.";
        }
    }

    // 2. Simpan Ke Database
    if (empty($err)) {
        try {
            if (!empty($password_baru)) {
                $hashed = password_hash($password_baru, PASSWORD_DEFAULT);
                if ($foto_path) {
                    $stmt = $pdo->prepare("UPDATE pengguna SET nama=?, email=?, password=?, foto_profil=? WHERE id_pengguna=?");
                    $stmt->execute([$nama, $email, $hashed, $foto_path, $id_pengguna]);
                    $_SESSION['user_foto'] = $foto_path;
                } else {
                    $stmt = $pdo->prepare("UPDATE pengguna SET nama=?, email=?, password=? WHERE id_pengguna=?");
                    $stmt->execute([$nama, $email, $hashed, $id_pengguna]);
                }
            } else {
                if ($foto_path) {
                    $stmt = $pdo->prepare("UPDATE pengguna SET nama=?, email=?, foto_profil=? WHERE id_pengguna=?");
                    $stmt->execute([$nama, $email, $foto_path, $id_pengguna]);
                    $_SESSION['user_foto'] = $foto_path;
                } else {
                    $stmt = $pdo->prepare("UPDATE pengguna SET nama=?, email=? WHERE id_pengguna=?");
                    $stmt->execute([$nama, $email, $id_pengguna]);
                }
            }
            $_SESSION['user_name'] = $nama; // Update header nama
            $msg = "Profil berhasil diperbarui dan disimpan!";
        } catch (PDOException $e) {
            $err = "Kesalahan Database: " . $e->getMessage();
        }
    }
}

// Ambil data terbaru dari DB
$stmtGet = $pdo->prepare("SELECT * FROM pengguna WHERE id_pengguna = ?");
$stmtGet->execute([$id_pengguna]);
$currentUser = $stmtGet->fetch();

$display_foto = !empty($currentUser['foto_profil']) ? $currentUser['foto_profil'] : "https://ui-avatars.com/api/?name=" . urlencode($currentUser['nama']) . "&background=198754&color=fff";

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0 fw-bold"><i class="fas fa-user-circle text-primary me-2"></i>Pengaturan Profil & Akun</h2>
</div>

<div class="row">
    <div class="col-lg-4 mb-4">
        <div class="card shadow border-0 overflow-hidden text-center h-100">
            <div class="card-header bg-medical-blue py-4 border-0">
                <!-- Cover Area -->
            </div>
            <div class="card-body position-relative pt-0">
                <!-- Avatar Terkini -->
                <div class="position-relative d-inline-block" style="margin-top: -40px;">
                    <img src="<?= htmlspecialchars($display_foto) ?>" class="rounded-circle shadow object-fit-cover bg-white p-1" style="width: 130px; height: 130px; border: 3px solid #fff;" alt="Foto Profil">
                </div>

                <h4 class="mt-3 fw-bold mb-1"><?= htmlspecialchars($currentUser['nama']) ?></h4>
                <p class="text-muted small mb-2"><i class="fas fa-id-badge me-1"></i><?= $currentUser['Role'] === 'admin' ? 'Administrator Kepala' : 'Petugas Medis' ?></p>
                <span class="badge bg-success rounded-pill px-3 py-2"><i class="fas fa-user-check me-1"></i>Akun Aktif Server</span>

                <hr class="my-4">

                <div class="d-flex justify-content-between text-start small mb-2">
                    <span class="text-muted">Username ID</span>
                    <span class="fw-bold">@<?= htmlspecialchars($currentUser['username']) ?></span>
                </div>
                <div class="d-flex justify-content-between text-start small">
                    <span class="text-muted">Email</span>
                    <span class="fw-bold"><?= htmlspecialchars($currentUser['email']) ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8 mb-4">
        <div class="card shadow border-0 flex-fill h-100">
            <div class="card-header bg-white pt-4 border-0">
                <h5 class="fw-bold mb-0">Ubah Konfigurasi Profil</h5>
            </div>
            <div class="card-body p-4">

                <?php if ($msg): ?>
                    <div class="alert alert-success d-flex align-items-center shadow-sm">
                        <i class="fas fa-check-circle fs-4 me-3"></i>
                        <div><?= $msg ?></div>
                    </div>
                    <script>
                        // Render ulang img src di nav bar top secara dinamis
                        document.addEventListener("DOMContentLoaded", () => {
                            Array.from(document.querySelectorAll('.rounded-circle')).forEach(img => {
                                if (img.width == 37) img.src = "<?= htmlspecialchars($display_foto) ?>";
                            });
                            document.querySelector('.dropdown-toggle .d-block').innerText = "<?= htmlspecialchars($nama) ?>";
                        });
                    </script>
                <?php endif; ?>

                <?php if ($err): ?>
                    <div class="alert alert-danger d-flex align-items-center shadow-sm">
                        <i class="fas fa-exclamation-triangle fs-4 me-3"></i>
                        <div><?= $err ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold">Ubah Nama Tampilan</label>
                            <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($currentUser['nama']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold">Ubah Alamat Email</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($currentUser['email']) ?>" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-secondary small fw-bold">Pilih Foto Avatar Baru (Maks 2MB, JPG/PNG)</label>
                        <input type="file" name="foto" class="form-control" accept="image/png, image/jpeg, image/webp">
                        <div class="form-text">Biarkan kosong jika tidak ingin mengubah foto profil yang lama.</div>
                    </div>

                    <div class="mb-4 p-3 bg-light rounded text-dark">
                        <h6 class="fw-bold fs-6 text-danger"><i class="fas fa-lock me-2"></i>Keamanan Kata Sandi</h6>
                        <label class="form-label text-secondary small fw-bold mt-2">Password Baru</label>
                        <input type="password" name="password_baru" class="form-control mb-2" placeholder="Kosongkan jika tidak ingin merubah password asli">
                        <small class="text-muted">Aksi ini akan menimpa sandi lama secara permanen.</small>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary bg-medical-blue fw-bold shadow-sm px-4">
                            <i class="fas fa-save me-2"></i>Simpan Perubahan
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>