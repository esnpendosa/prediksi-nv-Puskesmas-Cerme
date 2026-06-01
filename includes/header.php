<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$active_role = $_SESSION['user_role'] === 'admin' ? 'Administrator' : 'Petugas';
$active_name = $_SESSION['user_name'];
$active_foto = isset($_SESSION['user_foto']) ? $_SESSION['user_foto'] : "https://ui-avatars.com/api/?name=" . urlencode($active_name) . "&background=198754&color=fff";
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Klasifikasi Risiko Hipertensi</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">

    <!-- Core JS Libraries (Move to header to support inline scripts) -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body data-bs-theme="light">
    <!-- Sidebar Overlay Mobile -->
    <div id="sidebar-overlay"></div>

    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->
        <div class="bg-medical-blue text-white" id="sidebar-wrapper">
            <div class="sidebar-heading text-center py-4 fs-5 fw-bold">
                <i class="fas fa-hospital me-2"></i>Puskesmas Cerme
            </div>
            <div class="list-group list-group-flush shadow-sm">
                <a href="dashboard.php" class="list-group-item list-group-item-action bg-transparent text-white fw-bold"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
                <a href="data_pasien.php" class="list-group-item list-group-item-action bg-transparent text-white fw-bold"><i class="fas fa-users me-2"></i>Data Pasien</a>
                <a href="prediksi.php" class="list-group-item list-group-item-action bg-transparent text-white fw-bold"><i class="fas fa-brain me-2"></i>Prediksi Risiko</a>
                <a href="laporan.php" class="list-group-item list-group-item-action bg-transparent text-white fw-bold"><i class="fas fa-file-alt me-2"></i>Laporan</a>
                <a href="profil.php" class="list-group-item list-group-item-action bg-transparent text-white fw-bold"><i class="fas fa-user-cog me-2"></i>Profil & Akun</a>
                <a href="logout.php" class="list-group-item list-group-item-action bg-transparent text-white fw-bold border-top mt-5"><i class="fas fa-sign-out-alt me-2"></i>Logout<br><small class="fw-normal"><?= htmlspecialchars($active_name) ?></small></a>
            </div>
        </div>

        <!-- Page Content -->
        <div id="page-content-wrapper" class="w-100 bg-light-theme">
            <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm px-3 px-md-4">
                <div class="d-flex align-items-center">
                    <button class="btn btn-primary bg-medical-blue border-0 me-3" id="menu-toggle" style="border-radius: 8px;"><i class="fas fa-bars"></i></button>
                    <!-- Tampilan Desktop/Tablet -->
                    <div class="d-none d-sm-block animate-slide-up" style="animation-delay: 0.1s;">
                        <h5 class="mb-0 fw-bold navbar-brand-mobile" style="color: var(--medical-blue); letter-spacing: 0.5px;">Panel Klasifikasi Hipertensi</h5>
                        <small class="text-muted fw-bold text-uppercase navbar-subtitle-mobile" style="font-size: 10px; letter-spacing: 1px;"><i class="fas fa-hospital me-1"></i> Pusat Data Pasien Puskesmas Cerme</small>
                    </div>
                    <!-- Tampilan Mobile Kecil (Singkat agar tidak tabrakan) -->
                    <div class="d-block d-sm-none animate-slide-up" style="animation-delay: 0.1s;">
                        <h6 class="mb-0 fw-bold text-medical-blue">SIG Cerme</h6>
                    </div>
                </div>
                <div class="ms-auto d-flex align-items-center">
                    <!-- User Profile Dropdown -->
                    <div class="dropdown">
                        <a class="text-decoration-none fw-bold text-dark-theme dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="<?= htmlspecialchars($active_foto) ?>" class="rounded-circle me-2 object-fit-cover shadow-sm" width="37" height="37" alt="Profile">
                            <div class="d-none d-md-block text-start">
                                <span class="d-block lh-1 text-dark"><?= htmlspecialchars($active_name) ?></span>
                                <small class="fw-light text-muted" style="font-size: 0.7em;"><?= $active_role ?></small>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-2">
                            <li class="d-block d-md-none px-3 py-2 border-bottom">
                                <span class="d-block fw-bold text-dark"><?= htmlspecialchars($active_name) ?></span>
                                <small class="text-muted"><?= $active_role ?></small>
                            </li>
                            <li><a class="dropdown-item py-2" href="profil.php"><i class="fas fa-user-cog me-2 text-secondary"></i>Pengaturan Profil</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item text-danger fw-bold py-2" href="logout.php"><i class="fas fa-power-off me-2"></i>Keluar/Logout</a></li>
                        </ul>
                    </div>
                </div>
            </nav>
            <div class="container-fluid p-3 p-md-4">