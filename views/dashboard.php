<?php
require_once '../config/koneksi.php';
require_once '../includes/auth_check.php';
include '../includes/header.php';

// Fetch ringkasan data dari tabel pasien dan hasil_prediksi
$total_pasien = $pdo->query("SELECT COUNT(*) FROM pasien p JOIN hasil_prediksi hp ON p.id_pasien = hp.id_pasien")->fetchColumn();
$total_tinggi = $pdo->query("SELECT COUNT(*) FROM hasil_prediksi WHERE hasil_prediksi = 'Tinggi'")->fetchColumn();
$total_rendah = $pdo->query("SELECT COUNT(*) FROM hasil_prediksi WHERE hasil_prediksi = 'Rendah'")->fetchColumn();

// Fetch 5 prediksi terbaru untuk ditampilkan di riwayat
$query = "SELECT p.nama, TIMESTAMPDIFF(YEAR, p.tanggal_lahir, CURDATE()) as umur, p.alamat, 
          ak.tekanan_sistolik, ak.tekanan_diastolik, hp.hasil_prediksi
          FROM hasil_prediksi hp
          JOIN pasien p ON hp.id_pasien = p.id_pasien
          JOIN atribut_kesehatan ak ON hp.id_atribut_kesehatan = ak.id_atribut
          ORDER BY hp.id_prediksi DESC LIMIT 5";
$latest = $pdo->query($query)->fetchAll();
?>

<!-- Baris 1: Card Widget -->
<div class="row mb-4">
    <div class="col-md-4 animate-slide-up" style="animation-delay: 0.1s;">
        <div class="card glass-card hover-card border-0 h-100">
            <div class="card-body d-flex align-items-center">
                <div>
                    <h6 class="text-muted fw-bold mb-1 text-uppercase" style="font-size: 11px; letter-spacing: 0.8px;">Total Diagnosa</h6>
                    <h2 class="fw-black mb-0" id="count-total" style="font-weight: 800; color: var(--medical-blue); font-size: 2.5rem;"><?= $total_pasien ?></h2>
                </div>
                <div class="ms-auto text-white rounded-4 d-flex align-items-center justify-content-center shadow-lg" style="background: linear-gradient(135deg, var(--medical-blue), var(--medical-cyan)); width: 64px; height: 64px;">
                    <i class="fas fa-users-viewfinder fs-3"></i>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 pt-0 pb-3">
                <small class="text-success fw-bold"><i class="fas fa-arrow-up me-1"></i>Sistem Aktif</small>
            </div>
        </div>
    </div>
    <div class="col-md-4 animate-slide-up" style="animation-delay: 0.2s;">
        <div class="card glass-card hover-card border-0 h-100">
            <div class="card-body d-flex align-items-center">
                <div>
                    <h6 class="text-muted fw-bold mb-1 text-uppercase" style="font-size: 11px; letter-spacing: 0.8px;">Risiko Tinggi</h6>
                    <h2 class="fw-black mb-0" id="count-tinggi" style="font-weight: 800; color: var(--danger-red); font-size: 2.5rem;"><?= $total_tinggi ?></h2>
                </div>
                <div class="ms-auto text-white rounded-4 d-flex align-items-center justify-content-center shadow-lg" style="background: linear-gradient(135deg, #ef4444, #f87171); width: 64px; height: 64px;">
                    <i class="fas fa-heart-pulse fs-3"></i>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 pt-0 pb-3">
                <small class="text-danger fw-bold"><i class="fas fa-exclamation-triangle me-1"></i>Perlu Tindakan</small>
            </div>
        </div>
    </div>
    <div class="col-md-4 animate-slide-up" style="animation-delay: 0.3s;">
        <div class="card glass-card hover-card border-0 h-100">
            <div class="card-body d-flex align-items-center">
                <div>
                    <h6 class="text-muted fw-bold mb-1 text-uppercase" style="font-size: 11px; letter-spacing: 0.8px;">Risiko Rendah</h6>
                    <h2 class="fw-black mb-0" id="count-rendah" style="font-weight: 800; color: var(--success-green); font-size: 2.5rem;"><?= $total_rendah ?></h2>
                </div>
                <div class="ms-auto text-white rounded-4 d-flex align-items-center justify-content-center shadow-lg" style="background: linear-gradient(135deg, #10b981, #34d399); width: 64px; height: 64px;">
                    <i class="fas fa-shield-heart fs-3"></i>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 pt-0 pb-3">
                <small class="text-secondary fw-bold"><i class="fas fa-check-circle me-1"></i>Kondisi Aman</small>
            </div>
        </div>
    </div>
</div>

<!-- Baris 2: Peta (Kiri) & Panel Pencarian (Kanan) -->
<div class="row mb-4 g-4 flex-lg-row">

    <!-- Bagian Kiri: Peta -->
    <div class="col-lg-8 animate-slide-up" style="animation-delay: 0.4s;">
        <div class="card glass-card hover-card border-0 h-100 overflow-hidden" style="border-radius: 1.5rem;">
            <div class="card-header bg-transparent pt-4 px-4 border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="fas fa-map-location-dot text-primary me-2"></i>Peta Zonasi Risiko</h5>
                <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill" style="font-size: 10px;">WILAYAH CERME</span>
            </div>
            <div class="card-body p-0 position-relative h-100">
                <div id="map" style="height: 100%; min-height: 520px; z-index: 1;"></div>

                <!-- Legend Overlay Diperbarui -->
                <div class="position-absolute bottom-0 start-0 m-4 p-3 glass-card border-0 rounded-4 shadow-lg text-dark d-none d-md-block" style="z-index: 1000; min-width: 220px; background: rgba(255,255,255,0.95);">
                    <h6 class="text-muted fw-bold mb-2 border-bottom pb-2" style="font-size: 10px; letter-spacing: 1px; text-transform: uppercase;">Zonasi Berdasarkan Rasio Diagnosa</h6>
                    <div class="d-flex align-items-center mb-2">
                        <div style="width: 12px; height: 12px; border-radius: 3px; background: #ef4444;" class="me-2 shadow-sm"></div>
                        <span style="font-size: 11px; font-weight: 700; color: #334155;">Risiko Tinggi (&ge; 65%)</span>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <div style="width: 12px; height: 12px; border-radius: 3px; background: #f59e0b;" class="me-2 shadow-sm"></div>
                        <span style="font-size: 11px; font-weight: 700; color: #334155;">Risiko Sedang (45% - 64%)</span>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <div style="width: 12px; height: 12px; border-radius: 3px; background: #10b981;" class="me-2 shadow-sm"></div>
                        <span style="font-size: 11px; font-weight: 700; color: #334155;">Risiko Rendah (&lt; 44%)</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <div style="width: 12px; height: 12px; border-radius: 3px; background: #cbd5e1;" class="me-2 shadow-sm border"></div>
                        <span style="font-size: 11px; font-weight: 700; color: #64748b;">Belum Ada Data</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bagian Kanan: Panel Search List -->
    <div class="col-lg-4 animate-slide-up" style="animation-delay: 0.5s;">
        <div class="card h-100 border-0 shadow-sm panel-pencarian" style="border-radius: 1.5rem; background-color: #ffffff;">
            <div class="card-body p-4 d-flex flex-column h-100">
                <!-- Search Input -->
                <div class="position-relative mb-4">
                    <i class="fas fa-search position-absolute text-muted" style="left: 15px; top: 50%; transform: translateY(-50%); font-size: 1.2rem; color: #94a3b8 !important;"></i>
                    <input type="text" id="searchDesa" class="form-control form-control-lg bg-light border-0" placeholder="Cari desa di Cerme..." style="padding-left: 45px; border-radius: 1rem; font-size: 0.95rem;">
                </div>

                <!-- Filter Kategori -->
                <h6 class="text-muted fw-bold text-uppercase mb-3" style="font-size: 11px; letter-spacing: 1px;">Kategori Wilayah</h6>
                <div class="d-flex gap-2 mb-4">
                    <button id="filterSemua" class="btn btn-filter active flex-fill fw-bold py-2" style="border-radius: 0.8rem; font-size: 0.9rem;">Semua</button>
                    <button id="filterTinggi" class="btn btn-filter flex-fill fw-bold py-2" style="border-radius: 0.8rem; font-size: 0.9rem;">Risiko Tinggi</button>
                </div>

                <!-- List Desa -->
                <div id="villageListContainer" class="flex-grow-1 overflow-auto pe-2 custom-scrollbar" style="max-height: 400px;">
                    <!-- Data List Di-render oleh JS -->
                    <div class="text-center py-5 text-muted small" id="loadingList">
                        <div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div><br>Memuat data wilayah...
                    </div>
                </div>
            </div>
            <!-- Footer Panel -->
            <div class="card-footer bg-light border-0 px-4 py-3 d-flex justify-content-between align-items-center" style="border-radius: 0 0 1.5rem 1.5rem;">
                <span class="text-muted fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">GEOMETRI VORONOI AKTIF</span>
                <span class="fw-bold" style="font-size: 10px; color: #10b981;"><i class="fas fa-circle me-1" style="font-size: 8px;"></i> PRESISI TINGGI</span>
            </div>
        </div>
    </div>
</div>


<!-- Baris 3: Tabel 5 Pasien Terbaru -->
<div class="row animate-slide-up mb-4" style="animation-delay: 0.6s;">
    <div class="col-12">
        <div class="card glass-card border-0 shadow-sm hover-card" style="border-radius: 1.5rem;">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-2 px-4 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <div class="bg-info bg-opacity-10 p-2 text-info rounded-3 me-3">
                        <i class="fas fa-history fs-5"></i>
                    </div>
                    <h5 class="mb-0 fw-bold" style="font-size: 16px; letter-spacing: 0.3px;">Riwayat Prediksi Terbaru</h5>
                </div>
                <a href="laporan.php" class="btn btn-sm btn-light rounded-pill px-3 fw-bold text-primary border shadow-sm">Lihat Semua Data</a>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 border-top">
                        <thead class="table-light">
                            <tr>
                                <th class="border-0 px-4 py-3 text-muted text-uppercase" style="font-size: 10px; letter-spacing: 1px;">Identitas Pasien</th>
                                <th class="border-0 py-3 text-muted text-uppercase" style="font-size: 10px; letter-spacing: 1px;">Alamat (Desa)</th>
                                <th class="border-0 py-3 text-muted text-uppercase" style="font-size: 10px; letter-spacing: 1px;">Tekanan Darah</th>
                                <th class="border-0 pe-4 py-3 text-muted text-uppercase text-end" style="font-size: 10px; letter-spacing: 1px;">Status Diagnosa</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0 bg-white">
                            <?php if (count($latest) == 0): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-5">
                                        <i class="fas fa-folder-open fs-2 mb-3 text-light"></i><br>
                                        Belum ada data riwayat prediksi.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($latest as $row): ?>
                                    <tr>
                                        <td class="px-4 py-3 border-0 border-bottom">
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center text-primary fw-bold me-3 shadow-sm border" style="width: 40px; height: 40px;">
                                                    <?= substr($row['nama'], 0, 1) ?>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 fw-bold text-dark" style="font-size: 14px;"><?= htmlspecialchars($row['nama']) ?></h6>
                                                    <small class="text-muted"><?= $row['umur'] ?> Tahun</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3 border-0 border-bottom text-muted" style="font-size: 13px;">
                                            <span class="badge bg-light text-dark border px-2 py-1"><i class="fas fa-map-marker-alt text-muted me-1"></i><?= htmlspecialchars($row['alamat']) ?></span>
                                        </td>
                                        <td class="py-3 border-0 border-bottom">
                                            <span class="fw-bold" style="color:#475569; font-size: 13px;"><i class="fas fa-heartbeat text-danger me-1"></i><?= $row['tekanan_sistolik'] ?>/<?= $row['tekanan_diastolik'] ?></span>
                                        </td>
                                        <td class="pe-4 py-3 border-0 border-bottom text-end">
                                            <?php if ($row['hasil_prediksi'] == 'Tinggi'): ?>
                                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-3 py-2 rounded-pill" style="font-size: 11px;"><i class="fas fa-chart-line me-1"></i>Risiko Tinggi</span>
                                            <?php else: ?>
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 rounded-pill" style="font-size: 11px;"><i class="fas fa-shield-alt me-1"></i>Risiko Rendah</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .custom-popup .leaflet-popup-content-wrapper {
        border-radius: 20px;
        padding: 0;
        overflow: hidden;
        box-shadow: var(--shadow-hover);
        border: 1px solid rgba(255, 255, 255, 0.3);
        backdrop-filter: blur(10px);
    }

    .custom-popup .leaflet-popup-content {
        margin: 0;
        width: 320px !important;
    }

    .village-label {
        background: rgba(255, 255, 255, 0.9);
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 9px;
        font-weight: 800;
        color: #1e293b;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        text-align: center;
        white-space: nowrap;
        pointer-events: none;
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    /* Styling Filter Button */
    .btn-filter {
        background-color: #fff0f2;
        color: #1e293b;
        border: none;
        transition: all 0.2s;
    }

    .btn-filter.active {
        background-color: #1e293b;
        color: #ffffff;
    }

    .btn-filter:hover:not(.active) {
        background-color: #ffe4e8;
    }

    /* Styling List Card Desa */
    .village-card {
        border: 1px solid #f1f5f9;
        border-radius: 1rem;
        padding: 1rem 1.25rem;
        margin-bottom: 0.8rem;
        cursor: pointer;
        transition: all 0.2s ease;
        background-color: #ffffff;
    }

    .village-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
        transform: translateY(-2px);
    }

    .village-card .target-icon {
        color: #cbd5e1;
        font-size: 1.5rem;
        transition: color 0.2s;
    }

    .village-card:hover .target-icon {
        color: var(--medical-blue);
    }

    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 6px;
    }

    .status-text {
        font-size: 0.75rem;
        font-weight: 800;
        letter-spacing: 0.5px;
        color: #64748b;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Initial CountUp Animations
        const options = {
            duration: 2,
            useEasing: true,
            useGrouping: true
        };
        new countUp.CountUp('count-total', <?= $total_pasien ?>, options).start();
        new countUp.CountUp('count-tinggi', <?= $total_tinggi ?>, options).start();
        new countUp.CountUp('count-rendah', <?= $total_rendah ?>, options).start();

        // 2. Map Initialization
        var map = L.map('map', {
            zoomSnap: 0.1,
            attributionControl: false
        }).setView([-7.240, 112.550], 12.8);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png').addTo(map);

        // Data Base Desa
        let villagesData = [{
                id: 1,
                name: "Dadapkuning",
                lat: -7.245,
                lng: 112.520
            },
            {
                id: 2,
                name: "Lengkong",
                lat: -7.240,
                lng: 112.535
            },
            {
                id: 3,
                name: "Dooro",
                lat: -7.275,
                lng: 112.530
            },
            {
                id: 4,
                name: "Dampaan",
                lat: -7.280,
                lng: 112.545
            },
            {
                id: 5,
                name: "Ngembung",
                lat: -7.250,
                lng: 112.530
            },
            {
                id: 6,
                name: "Guranganyar",
                lat: -7.265,
                lng: 112.545
            },
            {
                id: 7,
                name: "Sukoanyar",
                lat: -7.255,
                lng: 112.540
            },
            {
                id: 8,
                name: "Morowudi",
                lat: -7.230,
                lng: 112.575
            },
            {
                id: 9,
                name: "Iker-iker Geger",
                lat: -7.240,
                lng: 112.580
            },
            {
                id: 10,
                name: "Betiting",
                lat: -7.225,
                lng: 112.550
            },
            {
                id: 11,
                name: "Cerme Kidul",
                lat: -7.235,
                lng: 112.560
            },
            {
                id: 12,
                name: "Cerme Lor",
                lat: -7.225,
                lng: 112.560
            },
            {
                id: 13,
                name: "Cagak Agung",
                lat: -7.240,
                lng: 112.550
            },
            {
                id: 14,
                name: "Ngabetan",
                lat: -7.245,
                lng: 112.565
            },
            {
                id: 15,
                name: "Kambingan",
                lat: -7.255,
                lng: 112.575
            },
            {
                id: 16,
                name: "Wedani",
                lat: -7.265,
                lng: 112.560
            },
            {
                id: 17,
                name: "Dungus",
                lat: -7.280,
                lng: 112.555
            },
            {
                id: 18,
                name: "Kandangan",
                lat: -7.295,
                lng: 112.540
            },
            {
                id: 19,
                name: "Gedangkulut",
                lat: -7.290,
                lng: 112.530
            },
            {
                id: 20,
                name: "Semampir",
                lat: -7.220,
                lng: 112.540
            },
            {
                id: 21,
                name: "Pandu",
                lat: -7.198,
                lng: 112.555
            },
            {
                id: 22,
                name: "Jono",
                lat: -7.205,
                lng: 112.565
            },
            {
                id: 23,
                name: "Tambakberas",
                lat: -7.215,
                lng: 112.570
            },
            {
                id: 24,
                name: "Padeg",
                lat: -7.210,
                lng: 112.550
            },
            {
                id: 25,
                name: "Banjarsari",
                lat: -7.195,
                lng: 112.545
            }
        ];

        const cermeBoundary = turf.polygon([
            [
                [112.535, -7.185],
                [112.550, -7.190],
                [112.565, -7.185],
                [112.570, -7.200],
                [112.585, -7.220],
                [112.580, -7.235],
                [112.590, -7.250],
                [112.580, -7.265],
                [112.575, -7.275],
                [112.560, -7.290],
                [112.550, -7.305],
                [112.535, -7.300],
                [112.525, -7.305],
                [112.520, -7.285],
                [112.510, -7.270],
                [112.515, -7.255],
                [112.510, -7.240],
                [112.520, -7.220],
                [112.530, -7.200],
                [112.535, -7.185]
            ]
        ]);

        // Fungsi Penentuan Kategori Diperbarui (Termasuk Penanganan "Belum Ada Data" dan Ambang Batas Baru)
        function getRiskLevel(prevalence, total) {
            // Jika total pasien 0 atau prevalence tidak memiliki nilai, set ke Belum Ada Data (Abu-abu)
            if (total === 0 || prevalence === null) return {
                id: 'nodata',
                label: 'BELUM ADA DATA',
                color: '#cbd5e1'
            };

            // Logika Persentase Baru
            if (prevalence >= 65) return {
                id: 'tinggi',
                label: 'TINGGI',
                color: '#ef4444'
            }; // Merah (Tinggi)
            if (prevalence >= 45) return {
                id: 'sedang',
                label: 'SEDANG',
                color: '#f59e0b'
            }; // Kuning (Sedang)
            return {
                id: 'rendah',
                label: 'RENDAH',
                color: '#10b981'
            }; // Hijau (Rendah)
        }

        let geojsonLayers = {}; // Simpan referensi layer peta

        fetch('../api/api_data.php?action=get_map_data')
            .then(res => res.json())
            .then(data => {
                let stats = {};
                data.forEach(d => {
                    let n = d.desa.toLowerCase().trim();
                    if (n === 'dohoagung') n = 'dooro';
                    if (n === 'kandanyar') n = 'kandangan';
                    stats[n] = {
                        tinggi: parseInt(d.tinggi),
                        rendah: parseInt(d.rendah),
                        total: parseInt(d.total)
                    };
                });

                // Update Array dengan Data API & Hitung Prevalence
                villagesData.forEach(v => {
                    let key = v.name.toLowerCase().trim();
                    if (stats[key] && stats[key].total > 0) {
                        v.prevalence = Math.round((stats[key].tinggi / stats[key].total) * 100);
                        v.sysTotal = stats[key].total;
                        v.sysTinggi = stats[key].tinggi;
                        v.sysRendah = stats[key].rendah;
                    } else {
                        // Tidak ada data untuk desa ini
                        v.prevalence = null;
                        v.sysTotal = 0;
                        v.sysTinggi = 0;
                        v.sysRendah = 0;
                    }
                    v.riskData = getRiskLevel(v.prevalence, v.sysTotal);
                });

                // Voronoi
                const points = turf.featureCollection(villagesData.map(v => turf.point([v.lng, v.lat])));
                const bbox = [112.50, -7.31, 112.60, -7.18];
                const voronoiPolys = turf.voronoi(points, {
                    bbox: bbox
                });

                villagesData.forEach((v, index) => {
                    const vPoly = voronoiPolys.features[index];
                    if (vPoly) {
                        v.geoJSON = turf.intersect(vPoly, cermeBoundary);

                        if (v.geoJSON) {
                            const layer = L.geoJSON(v.geoJSON, {
                                style: {
                                    color: '#ffffff',
                                    fillColor: v.riskData.color,
                                    fillOpacity: 0.4,
                                    weight: 2,
                                    dashArray: '3'
                                }
                            }).addTo(map);

                            layer.on('mouseover', function(e) {
                                e.layer.setStyle({
                                    weight: 2,
                                    fillOpacity: 0.7,
                                    color: '#1e293b',
                                    dashArray: ''
                                });
                                e.layer.bringToFront();
                            });
                            layer.on('mouseout', function(e) {
                                layer.resetStyle(e.layer);
                            });

                            // Popup Template
                            const popup = `
                            <div class="custom-popup">
                                <div class="p-4 text-white d-flex align-items-center justify-content-between" style="background-color: ${v.riskData.color};">
                                    <h6 class="fw-bold mb-0"><i class="fas fa-hospital-user me-2"></i>Desa ${v.name}</h6>
                                </div>
                                <div class="p-4 bg-white">
                                    <div class="row g-3 mb-4">
                                        <div class="col-6">
                                            <div class="p-3 rounded-4 border bg-light text-center">
                                                <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 8px;">Status Wilayah</small>
                                                <span class="fw-bold" style="color: ${v.riskData.color}; font-size: 14px;">${v.riskData.label}</span>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="p-3 rounded-4 border bg-light text-center">
                                                <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 8px;">Total Pasien</small>
                                                <span class="fw-bold text-dark" style="font-size: 14px;">${v.sysTotal}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-2 d-flex justify-content-between">
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-3 py-2">Tinggi: ${v.sysTinggi}</span>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2">Rendah: ${v.sysRendah}</span>
                                    </div>
                                </div>
                            </div>`;
                            layer.bindPopup(popup, {
                                closeButton: false
                            });
                            geojsonLayers[v.id] = layer; // Simpan referensi

                            L.marker([v.lat, v.lng], {
                                icon: L.divIcon({
                                    className: 'village-label-container',
                                    html: '<div class="village-label">' + v.name + '</div>',
                                    iconSize: [0, 0]
                                })
                            }).addTo(map);
                        }
                    }
                });

                // Init List Desa dan Fungsi Pencarian
                initListAndSearch();
            });

        // Logika List dan Pencarian
        let currentFilter = 'semua';
        let searchQuery = '';

        function initListAndSearch() {
            const listContainer = document.getElementById('villageListContainer');
            const searchInput = document.getElementById('searchDesa');
            const btnSemua = document.getElementById('filterSemua');
            const btnTinggi = document.getElementById('filterTinggi');

            function renderList() {
                listContainer.innerHTML = '';
                let filtered = villagesData.filter(v => {
                    let matchName = v.name.toLowerCase().includes(searchQuery.toLowerCase());
                    let matchFilter = currentFilter === 'semua' || (currentFilter === 'tinggi' && (v.riskData.id === 'tinggi' || v.riskData.id === 'sedang'));
                    return matchName && matchFilter;
                });

                if (filtered.length === 0) {
                    listContainer.innerHTML = '<div class="text-center py-4 text-muted small">Tidak ada desa yang sesuai.</div>';
                    return;
                }

                // Render Kartu List Desa
                filtered.forEach(v => {
                    const card = document.createElement('div');
                    card.className = 'village-card d-flex justify-content-between align-items-center';

                    // Sembunyikan simbol % jika data masih null (0 pasien)
                    const prevalenceText = v.prevalence !== null ? ` • ${v.prevalence}%` : '';

                    card.innerHTML = `
                        <div>
                            <h6 class="fw-bold mb-1" style="color: #1e293b; font-size: 0.95rem;">${v.name}</h6>
                            <div class="d-flex align-items-center">
                                <span class="status-dot" style="background-color: ${v.riskData.color};"></span>
                                <span class="status-text">${v.riskData.label}${prevalenceText}</span>
                            </div>
                        </div>
                        <i class="fas fa-crosshairs target-icon"></i>
                    `;
                    // Event klik untuk flyTo di map
                    card.addEventListener('click', () => {
                        map.flyTo([v.lat, v.lng], 14, {
                            animate: true,
                            duration: 1.5
                        });
                        setTimeout(() => {
                            if (geojsonLayers[v.id]) {
                                // Trigger klik pada layer map agar popup terbuka
                                geojsonLayers[v.id].fire('click');
                            }
                        }, 1500);
                    });
                    listContainer.appendChild(card);
                });
            }

            // Event Listeners
            searchInput.addEventListener('input', (e) => {
                searchQuery = e.target.value;
                renderList();
            });

            btnSemua.addEventListener('click', () => {
                currentFilter = 'semua';
                btnSemua.classList.add('active');
                btnTinggi.classList.remove('active');
                // Ubah gaya tombol ke desain putih untuk yg tidak aktif
                btnTinggi.style.backgroundColor = '#fff0f2';
                btnTinggi.style.color = '#1e293b';
                renderList();
            });

            btnTinggi.addEventListener('click', () => {
                currentFilter = 'tinggi';
                btnTinggi.classList.add('active');
                btnSemua.classList.remove('active');
                // Sesuaikan style khusus filter aktif 
                btnTinggi.style.backgroundColor = '#1e293b';
                btnTinggi.style.color = '#fff';
                renderList();
            });

            // Initial render
            renderList();
        }
    });
</script>

<?php include '../includes/footer.php'; ?>