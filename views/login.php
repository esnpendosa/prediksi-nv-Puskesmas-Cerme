<?php
require_once '../config/koneksi.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM pengguna WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    $authSuccess = false;

    if ($user) {
        if (password_verify($password, $user['password'])) {
            $authSuccess = true;
        } elseif ($password == $user['password']) {
            $authSuccess = true;
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE pengguna SET password=? WHERE id_pengguna=?")->execute([$hashed, $user['id_pengguna']]);
        }
    }

    if ($authSuccess) {
        $_SESSION['user_id'] = $user['id_pengguna'];
        $_SESSION['user_name'] = $user['nama'];
        $_SESSION['user_role'] = $user['Role'];
        $foto = !empty($user['foto_profil']) ? $user['foto_profil'] : "https://ui-avatars.com/api/?name=" . urlencode($user['nama']) . "&background=random&color=fff";
        $_SESSION['user_foto'] = $foto;

        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Username atau password salah! Coba periksa kembali.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Klasifikasi Hipertensi</title>
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
            max-width: 900px;
            width: 100%;
            margin: 0 auto;
        }

        .auth-card {
            border: none;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .auth-brand {
            /* background: linear-gradient(135deg, #4ca9df 0%, #a1ffb3 100%); */
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

        .form-control {
            border-radius: 12px;
            padding: 0.8rem 1rem;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
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

        .btn-login {
            border-radius: 12px;
            padding: 0.8rem;
            font-weight: 700;
            background: linear-gradient(135deg, #4cb5df 0%, #47f9a3 100%);
            border: none;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(0, 114, 255, 0.2);
        }
    </style>
</head>

<body>

    <div class="container auth-wrapper">
        <div class="card auth-card animate__animated animate__zoomIn">
            <div class="row g-0">

                <div class="col-md-5 d-none d-md-flex auth-brand">
                    <div class="icon-circle">
                        <i class="fas fa-heartbeat fa-3x"></i>
                    </div>
                    <h3 class="fw-bold mb-2">Puskesmas Cerme</h3>
                    <p class="text-white-200 small mb-0">Sistem Pakar Klasifikasi Risiko Hipertensi Metode Naïve Bayes.</p>
                </div>

                <div class="col-md-7 auth-form-area d-flex flex-column justify-content-center">
                    <div class="mb-4">
                        <h3 class="fw-bold text-dark mb-1">Selamat Datang</h3>
                        <p class="text-muted small">Silakan masuk menggunakan kredensial Anda.</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger py-2 px-3 small fw-bold rounded-3 border-0">
                            <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['registered'])): ?>
                        <div class="alert alert-success py-2 px-3 small fw-bold rounded-3 border-0">
                            <i class="fas fa-check-circle me-2"></i>Akun barhasil diregistrasi. Silakan login.
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label text-dark fw-bold small">ID Pengguna / Username</label>
                            <input type="text" name="username" class="form-control" placeholder="Masukkan username" required autofocus>
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-dark fw-bold small">Kata Sandi</label>
                            <div class="input-group">
                                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
                                <button class="btn" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-login btn-primary w-100 text-white mb-4">
                            Masuk ke Dasbor <i class="fas fa-arrow-right ms-2"></i>
                        </button>

                        <div class="text-center text-muted small">
                            Belum memiliki akses medis? <br>
                            <a href="register.php" class="text-decoration-none fw-bold" style="color: #0072FF;">Daftar Akun Petugas</a>
                        </div>
                    </form>
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