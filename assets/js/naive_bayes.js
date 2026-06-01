document.addEventListener('DOMContentLoaded', function () {
    const btnHitung = document.getElementById('btnHitung');
    const pilihPasien = document.getElementById('pilihPasien');
    const hasilContainer = document.getElementById('hasilContainer');
    const placeholderPrediksi = document.getElementById('placeholderPrediksi');
    const logikaNB = document.getElementById('logikaNB');
    const hasilAkhirTeks = document.getElementById('hasilAkhirTeks');
    const btnSimpan = document.getElementById('btnSimpan');
    const cardDiagnosa = document.getElementById('cardDiagnosa');
    const panelDiagnosa = document.getElementById('panelDiagnosa');

    let currentPasienData = null;
    let finalPrediction = null;

    if (btnHitung) {
        btnHitung.addEventListener('click', async () => {
            const idAtribut = pilihPasien.value;
            if (!idAtribut) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Perhatian',
                    text: 'Silakan pilih antrean pasien dari daftar terlebih dahulu.',
                    confirmButtonColor: '#0072FF'
                });
                return;
            }

            btnHitung.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Memproses Data...`;
            btnHitung.disabled = true;

            try {
                // Fetch data uji berdasarkan id_atribut
                const resPasien = await fetch(`../api/api_data.php?action=get_pasien_belum&id_atribut=${idAtribut}`);
                currentPasienData = await resPasien.json();

                // Fetch keseluruhan data latih Naive Bayes
                const resTraining = await fetch(`../api/api_data.php?action=get_training_data`);
                const trainingData = await resTraining.json();

                await hitungNaiveBayes(currentPasienData, trainingData);

                placeholderPrediksi.classList.remove('d-flex');
                placeholderPrediksi.style.display = 'none';
                hasilContainer.style.display = 'block';

                // Scroll smooth ke hasil
                setTimeout(() => {
                    hasilContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 100);

            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Koneksi Server Gagal',
                    text: "Error saat mengambil dataset: " + err,
                    confirmButtonColor: '#0072FF'
                });
            } finally {
                btnHitung.innerHTML = `<i class="fas fa-brain me-2"></i>Jalankan Analisis Naive Bayes`;
                btnHitung.disabled = false;
            }
        });
    }

    if (btnSimpan) {
        btnSimpan.addEventListener('click', async () => {
            if (!currentPasienData || !finalPrediction) return;

            btnSimpan.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...`;
            btnSimpan.disabled = true;

            try {
                const response = await fetch('../api/simpan_prediksi.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id_pasien: currentPasienData.id_pasien,
                        id_atribut: currentPasienData.id_atribut,
                        hasil_prediksi: finalPrediction
                    })
                });

                const result = await response.json();
                if (result.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Tersimpan!',
                        text: 'Diagnosa berhasil dimasukkan ke master data.',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = 'laporan.php?toast=saved';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Database Menolak',
                        text: result.message,
                        confirmButtonColor: '#0072FF'
                    });
                }
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error Jaringan',
                    text: err,
                    confirmButtonColor: '#0072FF'
                });
            } finally {
                btnSimpan.innerHTML = `<i class="fas fa-database me-2"></i>Simpan Diagnosa ke Database`;
                btnSimpan.disabled = false;
            }
        });
    }

    const sleep = (ms) => new Promise(res => setTimeout(res, ms));
    const appendLog = (content) => {
        const wrapper = document.createElement('div');
        wrapper.innerHTML = content.trim();
        const node = wrapper.firstElementChild;
        logikaNB.appendChild(node);
        logikaNB.scrollTop = logikaNB.scrollHeight;
        return node;
    };

    // Fungsi Matematika Naive Bayes
    async function hitungNaiveBayes(dataUji, dataLatih) {
        logikaNB.innerHTML = '';
        const totalData = dataLatih.length;

        if (totalData === 0) {
            logikaNB.innerHTML = `<div class="alert alert-danger m-3 border-0 shadow-sm"><i class="fas fa-exclamation-triangle me-2"></i><strong>Dataset Kosong!</strong> Sistem tidak memiliki data riwayat untuk dipelajari.</div>`;
            return;
        }

        // Tampilkan Jenis Kelamin secara eksplisit
        const jenisKelaminLengkap = dataUji.jenis_kelamin === 'laki-laki' ? 'Laki-laki' : 'Perempuan';

        // --- FASE 1: Identifikasi ---
        const f1 = appendLog(`<div class="ai-log-section p-3 mb-3 bg-white rounded-3 shadow-sm border">
            <h6 class="fw-bold text-primary mb-3"><i class="fas fa-search me-2"></i>Tahap 1: Ekstraksi Fitur Klinis Pasien</h6>
            <div class="row g-2 small text-dark">
                <div class="col-sm-6"><span class="text-muted">Nama/NIK:</span> <strong>${dataUji.nama} (${dataUji.nik})</strong></div>
                <div class="col-sm-6"><span class="text-muted">Umur / L/P:</span> <strong>${dataUji.umur} Tahun / ${jenisKelaminLengkap}</strong></div>
                <div class="col-sm-6"><span class="text-muted">Tensi (Sys/Dia):</span> <strong class="text-danger">${dataUji.tekanan_sistolik}/${dataUji.tekanan_diastolik} mmHg</strong></div>
                <div class="col-sm-6"><span class="text-muted">IMT (Fisik):</span> <strong>${dataUji.imt}</strong></div>
            </div>
            <div class="progress mt-3" style="height: 4px;"><div class="progress-bar progress-bar-striped progress-bar-animated w-100"></div></div>
        </div>`);
        await sleep(800);
        f1.querySelector('.progress').style.display = 'none';

        // --- FASE 2: Prior ---
        const cTinggi = dataLatih.filter(d => d.hasil_prediksi === 'Tinggi').length;
        const cRendah = dataLatih.filter(d => d.hasil_prediksi === 'Rendah').length;
        const pTinggi = cTinggi / totalData;
        const pRendah = cRendah / totalData;

        appendLog(`<div class="ai-log-section p-3 mb-3 bg-white rounded-3 shadow-sm border">
            <h6 class="fw-bold text-primary mb-3"><i class="fas fa-chart-pie me-2"></i>Tahap 2: Probabilitas Dasar (Prior Class)</h6>
            <div class="row text-center g-2">
                <div class="col-6">
                    <div class="p-2 bg-danger bg-opacity-10 rounded border border-danger border-opacity-25">
                        <small class="text-danger fw-bold d-block">Risiko Tinggi P(T)</small>
                        <span class="fs-5 fw-bold">${pTinggi.toFixed(3)}</span>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-2 bg-success bg-opacity-10 rounded border border-success border-opacity-25">
                        <small class="text-success fw-bold d-block">Risiko Rendah P(R)</small>
                        <span class="fs-5 fw-bold">${pRendah.toFixed(3)}</span>
                    </div>
                </div>
            </div>
        </div>`);
        await sleep(800);

        // --- FASE 3: Likelihood Estimation ---
        appendLog(`<div class="ai-log-section p-3 mb-3 bg-white rounded-3 shadow-sm border" id="section-f3">
            <h6 class="fw-bold text-primary mb-2"><i class="fas fa-microscope me-2"></i>Tahap 3: Kalkulasi Likelihood Probabilitas (P(X|C))</h6>
            <p class="small text-muted mb-2">Menggunakan Laplace Smoothing untuk data kategorik dan Distribusi Gaussian untuk data numerik kontinu.</p>
        </div>`);
        await sleep(600);

        function countKategorik(attr, valObj, kelas) {

    const subset = dataLatih.filter(
        d => d.hasil_prediksi === kelas
    );

    const valClean = String(valObj)
        .trim()
        .toLowerCase();

    const count = subset.filter(d =>
        String(d[attr])
            .trim()
            .toLowerCase() === valClean
    ).length;

    // SAMA PERSIS SEPERTI EXCEL COUNTIFS
    if (subset.length === 0) return 0;

    return count / subset.length;
}
        //function countKategorik(attr, valObj, kelas) {
          //  const subset = dataLatih.filter(d => d.hasil_prediksi === kelas);
            //const count = subset.filter(d => d[attr] === valObj).length;
            //const uniqueValues = new Set(dataLatih.map(d => d[attr])).size;
            //return (count + 1) / (subset.length + uniqueValues); // Laplace Smoothing
        //}

function getMeanVar(attr, kelas) {

    const subset = dataLatih.filter(function(d) {
        return d.hasil_prediksi === kelas;
    });

    const values = subset.map(function(d) {
        return parseFloat(d[attr]);
    });

    const mean =
        values.reduce((a, b) => a + b, 0) / values.length;

    // VARIANCE POPULATION (sama Excel STDEV.P)
    const variance =
        values.reduce(function(total, value) {
            return total + Math.pow(value - mean, 2);
        }, 0) / values.length;

    const stdev = Math.sqrt(variance);

    return {
        mean: mean,
        variance: variance,
        stdev: stdev
    };
}

        //function getMeanVar(attr, kelas) {
          //  const subset = dataLatih.filter(d => d.hasil_prediksi === kelas);
            //const values = subset.map(d => parseFloat(d[attr]));
            //const mean = values.reduce((a, b) => a + b, 0) / values.length;
            //const variance = values.reduce((a, b) => a + Math.pow(b - mean, 2), 0) / values.length;
            //return { mean, variance };
        //}

        function gaussianLikelihood(x, mean, variance) {

    if (variance <= 0) {
        variance = 0.000001;
    }

    const exponent =
        Math.exp(
            -Math.pow(x - mean, 2) /
            (2 * variance)
        );

    return (
        1 /
        Math.sqrt(2 * Math.PI * variance)
    ) * exponent;
}

        let logTinggi = pTinggi;
        let logRendah = pRendah;
        let rowHtml = "";

        // Hapus 'desa' dari perhitungan
        const attrsCat = ['jenis_kelamin', 'merokok', 'konsumsi_alkohol', 'kurang_buah_sayur', 'diabetes', 'riwayat_hipertensi'];
        attrsCat.forEach(attr => {
            const val = dataUji[attr];
            const pxT = countKategorik(attr, val, 'Tinggi');
            const pxR = countKategorik(attr, val, 'Rendah');

logTinggi *= (pxT || 1e-10);
logRendah *= (pxR || 1e-10);

            //logTinggi += Math.log(pxT);
            //logRendah += Math.log(pxR);

            // Format tampilan nilai (Value) untuk log
            let displayVal = val;
            if (attr === 'jenis_kelamin') {
                displayVal = val === 'laki-laki' ? 'Laki-laki' : 'Perempuan';
            }

            rowHtml += `<tr><td class="text-muted small text-capitalize py-2">${attr.replace(/_/g, ' ')} <span class="badge bg-light text-dark border ms-1">${displayVal}</span></td><td class="text-danger small fw-bold">${pxT.toFixed(3)}</td><td class="text-success small fw-bold">${pxR.toFixed(3)}</td></tr>`;
        });

        const attrsNum = ['umur', 'tekanan_sistolik', 'tekanan_diastolik', 'imt'];
        attrsNum.forEach(attr => {
            const val = parseFloat(dataUji[attr]);
            const sT = getMeanVar(attr, 'Tinggi');
            const sR = getMeanVar(attr, 'Rendah');
            const pxT = gaussianLikelihood(val, sT.mean, sT.variance);
            const pxR = gaussianLikelihood(val, sR.mean, sR.variance);

logTinggi *= (pxT || 1e-10);
logRendah *= (pxR || 1e-10);

            //logTinggi += Math.log(pxT + 1e-9); // 1e-9 untuk mencegah log(0)
            //logRendah += Math.log(pxR + 1e-9);

            rowHtml += `<tr><td class="text-muted small text-capitalize py-2">${attr.replace(/_/g, ' ')} <span class="badge bg-light text-dark border ms-1">${val}</span></td><td class="text-danger small fw-bold">${pxT.toFixed(6)}</td><td class="text-success small fw-bold">${pxR.toFixed(6)}</td></tr>`;
        });

        appendLog(`<div class="table-responsive bg-light rounded-3 border mb-3">
            <table class="table table-borderless table-hover table-sm mb-0">
                <thead class="border-bottom small text-secondary"><tr><th class="ps-3 text-start">Atribut / Fitur <span class="fw-normal">(Data Pasien)</span></th><th>P(Fitur | Tinggi)</th><th class="pe-3">P(Fitur | Rendah)</th></tr></thead>
                <tbody class="align-middle">${rowHtml}</tbody>
            </table>
        </div>`);
        await sleep(1000);

        // --- FASE 4: MAP (Maximum A Posteriori) dengan Logaritma ---
        appendLog(`
            <div class="p-3 bg-primary alert-info white bg-opacity-10 rounded-3 border border-primary border-opacity-25">
                <h6 class="fw-bold text-primary mb-3"><i class="fas fa-balance-scale me-2"></i>Tahap 4: Akumulasi Skor Posterior (Logarithmic)</h6>
                <div class="d-flex justify-content-between mb-2 pb-2 border-bottom border-primary border-opacity-10">
                    <span class="text-danger fw-bold small">Log-Skor Risiko TINGGI</span>
                    <span class="text-danger font-monospace fw-bold">${logTinggi.toExponential(5).replace('e', 'E')}</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-success fw-bold small">Log-Skor Risiko RENDAH</span>
                    <span class="text-success font-monospace fw-bold">${logRendah.toExponential(5).replace('e', 'E')}</span>
                </div>
                <div class="mt-2 text-center small text-muted"><i class="fas fa-info-circle me-1"></i>Nilai Log tertinggi menentukan klasifikasi akhir.</div>
            </div>`);
        await sleep(1000);

        // Reset classes
        if (cardDiagnosa) cardDiagnosa.classList.remove('border-danger', 'border-success', 'shadow-danger', 'shadow-success');
        panelDiagnosa.classList.remove('bg-danger', 'bg-success', 'bg-opacity-10', 'bounce-in-animation');
        void panelDiagnosa.offsetWidth; // Trigger reflow

        // Komparasi menggunakan nilai Log (Lebih stabil dari eksponensial)
        if (logTinggi > logRendah) {
            finalPrediction = 'Tinggi';
            cardDiagnosa.classList.add('border-danger');
            panelDiagnosa.classList.add('bg-danger', 'bg-opacity-10', 'bounce-in-animation');
            hasilAkhirTeks.innerHTML = `<span class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i>RISIKO TINGGI</span>`;
            hasilAkhirTeks.nextElementSibling.innerText = "Diagnosis Sistem: Probabilitas lebih mengarah pada kategori penderita Hipertensi.";
        } else {
            finalPrediction = 'Rendah';
            cardDiagnosa.classList.add('border-success');
            panelDiagnosa.classList.add('bg-success', 'bg-opacity-10', 'bounce-in-animation');
            hasilAkhirTeks.innerHTML = `<span class="text-success"><i class="fas fa-check-circle me-2"></i>RISIKO RENDAH</span>`;
            hasilAkhirTeks.nextElementSibling.innerText = "Diagnosis Sistem: Pasien masuk dalam kategori aman (Risiko Rendah Hipertensi).";
        }

        setTimeout(() => {
            panelDiagnosa.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 300);
    }
});