// Sidebar Toggle
document.getElementById('menu-toggle').addEventListener('click', function () {
    const wrapper = document.getElementById('wrapper');
    if (wrapper) wrapper.classList.toggle('toggled');
});

// Tutup sidebar jika overlay diklik
const sidebarOverlay = document.getElementById('sidebar-overlay');
if (sidebarOverlay) {
    sidebarOverlay.addEventListener('click', function () {
        const wrapper = document.getElementById('wrapper');
        if (wrapper) wrapper.classList.remove('toggled');
    });
}

// Inisialisasi DataTables otomatis
$(document).ready(function () {
    if ($('#tablePasien').length) {
        $('#tablePasien').DataTable();
    }

    // PDF Export Logic (Final Robust Version)
    const btnPdf = document.getElementById('btnPdf');
    if (btnPdf) {
        btnPdf.addEventListener('click', function () {
            const btn = this;
            const originalContent = btn.innerHTML;

            // Check library
            if (typeof html2pdf === 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'System Error',
                    text: 'Library PDF (html2pdf) gagal dimuat. Refresh halaman atau cek koneksi internet.'
                });
                return;
            }

            btn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status"></span> Preparing PDF...`;
            btn.disabled = true;

            const el = document.getElementById('areaLaporanPDF');
            if (!el) {
                Swal.fire('Error', 'Konten laporan tidak ditemukan!', 'error');
                btn.innerHTML = originalContent;
                btn.disabled = false;
                return;
            }

            // OPTIMIZATION: Ensure content is visible correctly for html2canvas
            const options = {
                margin: [0.5, 0.5, 0.5, 0.5], // [top, left, bottom, right]
                filename: 'Laporan_Hipertensi_Cerme.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: {
                    scale: 3, // Higher scale for better text quality
                    useCORS: true,
                    letterRendering: true,
                    scrollX: 0,
                    scrollY: 0
                },
                jsPDF: { unit: 'in', format: 'a4', orientation: 'landscape', compress: true }
            };

            // Process
            html2pdf().set(options).from(el).toPdf().get('pdf').then(function (pdf) {
                // Optional: add watermark or page numbers if needed later
            }).save().then(() => {
                btn.innerHTML = originalContent;
                btn.disabled = false;
                Swal.fire({
                    icon: 'success',
                    title: 'Selesai!',
                    text: 'File PDF telah diunduh.',
                    timer: 1500,
                    showConfirmButton: false
                });
            }).catch(err => {
                console.error("PDF Generate Error:", err);
                btn.innerHTML = originalContent;
                btn.disabled = false;
                Swal.fire('Ekspor Gagal', 'Kesalahan: ' + (err.message || "Eror tidak dikenal"), 'error');
            });
        });
    }
});
