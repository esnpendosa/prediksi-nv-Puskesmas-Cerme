<?php
require_once '../config/koneksi.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = $_POST['nama'] ?? '';
    $email = $_POST['email'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'petugas';

    $stmtCek = $pdo->prepare("SELECT id_pengguna FROM pengguna WHERE username = ?");
    $stmtCek->execute([$username]);
    if ($stmtCek->fetch()) {
        $error = "Pendaftaran digagalkan. Username '$username' telah digunakan!";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $default_avatars = [
            'https://api.dicebear.com/7.x/micah/svg?seed=Felix&backgroundColor=e0ecfc',
            'https://api.dicebear.com/7.x/micah/svg?seed=Aneka&backgroundColor=d4edda',
            'https://api.dicebear.com/7.x/micah/svg?seed=Jack&backgroundColor=ffeeba',
            'https://api.dicebear.com/7.x/micah/svg?seed=Jasmine&backgroundColor=f8d7da',
            'https://api.dicebear.com/7.x/micah/svg?seed=Abby&backgroundColor=e2e3e5'
        ];
        $foto_profil = $default_avatars[array_rand($default_avatars)];

        $stmt = $pdo->prepare("INSERT INTO pengguna (nama, email, username, password, Role, foto_profil) VALUES (?,?,?,?,?,?)");
        if ($stmt->execute([$nama, $email, $username, $hashed, $role, $foto_profil])) {
            header("Location: login.php?registered=1");
            exit;
        } else {
            $error = "Terjadi kesalahan sistem saat mendaftar.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Petugas - Puskesmas Cerme</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f8fafc;
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }

        .auth-wrapper {
            max-width: 1000px;
            width: 100%;
            margin: 2rem auto;
        }

        .auth-card {
            border: none;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .auth-brand {
            /* background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); */
            background: linear-gradient(135deg, #4cb5df 0%, #47f9a3 100%);
            color: white;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .auth-brand .icon-circle {
            width: 90px;
            height: 90px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            backdrop-filter: blur(10px);
        }

        .auth-form-area {
            padding: 3rem;
            background: #ffffff;
        }

        .form-control,
        .form-select {
            border-radius: 12px;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus,
        .form-select:focus {
            background-color: #ffffff;
            border-color: #0072FF;
            box-shadow: 0 0 0 4px rgba(0, 114, 255, 0.1);
        }

        /* Penyesuaian border radius untuk input group (fitur mata) */
        .input-group .form-control {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
            border-right: none;
        }

        .input-group .btn {
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
            border: 1px solid #e2e8f0;
            border-left: none;
            background-color: #f8fafc;
            color: #64748b;
        }

        .input-group .btn:hover {
            background-color: #e2e8f0;
            color: #0072FF;
        }

        .btn-register {
            border-radius: 12px;
            padding: 0.8rem;
            font-weight: 700;
            background: linear-gradient(135deg, #4cb5df 0%, #47f9a3 100%);
            border: none;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(0, 114, 255, 0.2);
        }
    </style>
</head>

<body>

    <div class="container auth-wrapper">
        <div class="card auth-card animate__animated animate__fadeInUp">
            <div class="row g-0 flex-column-reverse flex-md-row">

                <div class="col-md-7 auth-form-area">
                    <div class="mb-4">
                        <h3 class="fw-bold text-dark mb-1">Pendaftaran Akses</h3>
                        <p class="text-muted small">Lengkapi data diri untuk mendaftarkan akun staf/petugas.</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger py-2 px-3 small fw-bold rounded-3 border-0">
                            <i class="fas fa-exclamation-triangle me-2"></i><?= $error ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row g-3 mb-3">
                            <div class="col-12">
                                <label class="form-label text-dark fw-bold small">Nama Lengkap (Sesuai NIP/KTP)</label>
                                <input type="text" name="nama" class="form-control" placeholder="Cth: Dr. Agus Setiawan" required autofocus>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-dark fw-bold small">Email Aktif</label>
                                <input type="email" name="email" class="form-control" placeholder="email@puskesmas.id" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-dark fw-bold small">Posisi Tugas</label>
                                <select name="role" class="form-select text-dark">
                                    <option value="petugas">Petugas Faskes (Poli/UGD)</option>
                                    <option value="admin">Administrator IT</option>
                                </select>
                            </div>
                        </div>

                        <hr class="my-4" style="opacity: 0.1;">

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label text-dark fw-bold small">Username</label>
                                <input type="text" name="username" class="form-control" placeholder="Cth: agus_cerme" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-dark fw-bold small">Kata Sandi</label>
                                <div class="input-group">
                                    <input type="password" name="password" id="password" class="form-control" placeholder="Buat sandi yang aman" required>
                                    <button class="btn" type="button" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-register btn-primary w-100 text-white mb-4">
                            Kirim Berkas Pendaftaran <i class="fas fa-paper-plane ms-2"></i>
                        </button>

                        <div class="text-center text-muted small">
                            Sudah memiliki akun? <br>
                            <a href="login.php" class="text-decoration-none fw-bold" style="color: #0072FF;">Kembali ke halaman Login</a>
                        </div>
                    </form>
                </div>

                <div class="col-md-5 d-none d-md-flex auth-brand">
                    <div class="icon-circle">
                        <i class="fas fa-user-md fa-3x"></i>
                    </div>
                    <h3 class="fw-bold mb-2">Akses Staf Medis</h3>
                    <p class="text-white-100 small mb-0 px-3">Halaman registrasi ini dikhususkan untuk Staf Internal UPT Puskesmas Cerme untuk mengakses panel AI Diagnostik.</p>
                </div>

            </div>
        </div>
    </div>

    <script>
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    </script>
</body>

</html>