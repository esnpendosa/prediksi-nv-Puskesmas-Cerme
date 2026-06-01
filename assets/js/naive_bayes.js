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

                // Call the unified Python API to get the exact correct predictions
                const response = await fetch('../api/predict_python.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id_atribut: idAtribut })
                });
                const apiRes = await response.json();
                
                if (!apiRes.success) {
                    throw new Error(apiRes.message || "Gagal menghitung prediksi.");
                }

                await renderNaiveBayesResult(apiRes.result, currentPasienData);

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
                    text: "Error saat mengambil dataset: " + err.message,
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

    // Fungsi Render Hasil Komputasi dari Python Backend
    async function renderNaiveBayesResult(apiResult, dataUji) {
        logikaNB.innerHTML = '';
        
        const priorStep = apiResult.details.find(d => d.step === "Prior");
        const catStep = apiResult.details.find(d => d.step === "Likelihood Kategorikal");
        const numStep = apiResult.details.find(d => d.step === "Likelihood Numerik");

        const jenisKelaminLengkap = dataUji.jenis_kelamin === 'laki-laki' ? 'Laki-laki' : 'Perempuan';

        // --- FASE 1: Identifikasi ---
        const f1 = appendLog(`<div class="ai-log-section p-3 mb-3 bg-white rounded-3 shadow-sm border animate-slide-up">
            <h6 class="fw-bold text-primary mb-3"><i class="fas fa-search me-2"></i>Tahap 1: Ekstraksi Fitur Klinis Pasien</h6>
            <div class="row g-2 small text-dark">
                <div class="col-sm-6"><span class="text-muted">Nama/NIK:</span> <strong>${dataUji.nama} (${dataUji.nik})</strong></div>
                <div class="col-sm-6"><span class="text-muted">Umur / L/P:</span> <strong>${apiResult.details[2].items[0].val} Tahun / ${jenisKelaminLengkap}</strong></div>
                <div class="col-sm-6"><span class="text-muted">Tensi (Sys/Dia):</span> <strong class="text-danger">${apiResult.details[2].items[1].val}/${apiResult.details[2].items[2].val} mmHg</strong></div>
                <div class="col-sm-6"><span class="text-muted">IMT (Fisik):</span> <strong>${apiResult.details[2].items[3].val}</strong></div>
            </div>
            <div class="progress mt-3" style="height: 4px;"><div class="progress-bar progress-bar-striped progress-bar-animated w-100"></div></div>
        </div>`);
        await sleep(600);
        f1.querySelector('.progress').style.display = 'none';

        // --- FASE 2: Prior ---
        const pTinggi = priorStep.data.tinggi.p;
        const pRendah = priorStep.data.rendah.p;

        appendLog(`<div class="ai-log-section p-3 mb-3 bg-white rounded-3 shadow-sm border animate-slide-up">
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
        await sleep(600);

        // --- FASE 3: Likelihood Estimation ---
        appendLog(`<div class="ai-log-section p-3 mb-3 bg-white rounded-3 shadow-sm border animate-slide-up" id="section-f3">
            <h6 class="fw-bold text-primary mb-2"><i class="fas fa-microscope me-2"></i>Tahap 3: Kalkulasi Likelihood Probabilitas (P(X|C))</h6>
            <p class="small text-muted mb-2">Menggunakan data real dari Python Engine dengan Gaussian PDF (Numerik) & Laplace Smoothing (Kategorik).</p>
        </div>`);
        await sleep(400);

        let rowHtml = "";

        // Render Categorical
        catStep.items.forEach(item => {
            rowHtml += `<tr>
                <td class="text-muted small text-capitalize py-2">${item.attr} <span class="badge bg-light text-dark border ms-1">${item.val}</span></td>
                <td class="text-danger small fw-bold">${item.tinggi.p.toFixed(3)}</td>
                <td class="text-success small fw-bold">${item.rendah.p.toFixed(3)}</td>
            </tr>`;
        });

        // Render Numerical
        numStep.items.forEach(item => {
            const displayT = item.tinggi.p < 0.0001 ? item.tinggi.p.toExponential(6).replace('e', 'E') : item.tinggi.p.toFixed(8);
            const displayR = item.rendah.p < 0.0001 ? item.rendah.p.toExponential(6).replace('e', 'E') : item.rendah.p.toFixed(8);
            rowHtml += `<tr>
                <td class="text-muted small text-capitalize py-2">${item.attr} <span class="badge bg-light text-dark border ms-1">${item.val}</span></td>
                <td class="text-danger small fw-bold">${displayT}</td>
                <td class="text-success small fw-bold">${displayR}</td>
            </tr>`;
        });

        appendLog(`<div class="table-responsive bg-light rounded-3 border mb-3 animate-slide-up">
            <table class="table table-borderless table-hover table-sm mb-0">
                <thead class="border-bottom small text-secondary"><tr><th class="ps-3 text-start">Atribut / Fitur <span class="fw-normal">(Data Pasien)</span></th><th>P(Fitur | Tinggi)</th><th class="pe-3">P(Fitur | Rendah)</th></tr></thead>
                <tbody class="align-middle">${rowHtml}</tbody>
            </table>
        </div>`);
        await sleep(800);

        // --- FASE 4: MAP (Maximum A Posteriori) ---
        // Menghitung kembali probabilitas gabungan unnormalized sperti Excel P(X, C) = P(C) * P(X1|C) * ...
        // Nilai ini dihitung langsung dari logaritma agar presisi
        const rawTinggi = Math.exp(apiResult.log_tinggi);
        const rawRendah = Math.exp(apiResult.log_rendah);

        appendLog(`
            <div class="p-3 bg-primary alert-info white bg-opacity-10 rounded-3 border border-primary border-opacity-25 animate-slide-up">
                <h6 class="fw-bold text-primary mb-3"><i class="fas fa-balance-scale me-2"></i>Tahap 4: Akumulasi Skor Posterior (Logarithmic)</h6>
                <div class="d-flex justify-content-between mb-2 pb-2 border-bottom border-primary border-opacity-10">
                    <span class="text-danger fw-bold small">Log-Skor Risiko TINGGI</span>
                    <span class="text-danger font-monospace fw-bold">${rawTinggi.toExponential(5).replace('e', 'E')}</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-success fw-bold small">Log-Skor Risiko RENDAH</span>
                    <span class="text-success font-monospace fw-bold">${rawRendah.toExponential(5).replace('e', 'E')}</span>
                </div>
                <div class="mt-2 text-center small text-muted"><i class="fas fa-info-circle me-1"></i>Nilai Log tertinggi menentukan klasifikasi akhir.</div>
            </div>`);
        await sleep(800);

        // Reset classes
        if (cardDiagnosa) cardDiagnosa.classList.remove('border-danger', 'border-success', 'shadow-danger', 'shadow-success');
        panelDiagnosa.classList.remove('bg-danger', 'bg-success', 'bg-opacity-10', 'bounce-in-animation');
        void panelDiagnosa.offsetWidth; // Trigger reflow

        finalPrediction = apiResult.prediction;
        
        if (finalPrediction === 'Tinggi') {
            cardDiagnosa.classList.add('border-danger');
            panelDiagnosa.classList.add('bg-danger', 'bg-opacity-10', 'bounce-in-animation');
            hasilAkhirTeks.innerHTML = `<span class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i>RISIKO TINGGI</span>`;
            hasilAkhirTeks.nextElementSibling.innerText = `Diagnosis Sistem (${apiResult.confidence_tinggi}%): Probabilitas lebih mengarah pada kategori penderita Hipertensi.`;
        } else {
            finalPrediction = 'Rendah';
            cardDiagnosa.classList.add('border-success');
            panelDiagnosa.classList.add('bg-success', 'bg-opacity-10', 'bounce-in-animation');
            hasilAkhirTeks.innerHTML = `<span class="text-success"><i class="fas fa-check-circle me-2"></i>RISIKO RENDAH</span>`;
            hasilAkhirTeks.nextElementSibling.innerText = `Diagnosis Sistem (${apiResult.confidence_rendah}%): Pasien masuk dalam kategori aman (Risiko Rendah Hipertensi).`;
        }

        setTimeout(() => {
            panelDiagnosa.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 300);
    }
});