# 🏥 Sistem Prediksi Hipertensi - Puskesmas Cerme (Naive Bayes)

Aplikasi berbasis web untuk memprediksi tingkat risiko hipertensi pasien (Risiko Rendah / Risiko Tinggi) menggunakan Algoritma **Gaussian Naive Bayes**. Aplikasi ini dikembangkan untuk mempermudah petugas medis di Puskesmas Cerme dalam melakukan skrining kesehatan pasien secara cepat, akurat, dan terstandarisasi.

Akurasi sistem ini telah direkonsiliasi secara penuh dan memiliki **100.00% kecocokan numerik (parity)** dengan model perhitungan manual pada spreadsheet referensi Excel Puskesmas Cerme.

---

## 🌟 Fitur Utama

- 🧠 **Engine Naive Bayes Presisi Tinggi**: Perhitungan kontinu menggunakan Distribusi Gaussian PDF untuk variabel numerik (Umur, IMT, Tekanan Darah Sistolik/Diastolik) dan Laplace Smoothing untuk variabel kategorikal.
- 📂 **Manajemen Data Pasien**: Pencatatan riwayat kesehatan pasien secara teratur dan dinamis.
- 📥 **Import Excel**: Mempermudah petugas untuk mengunggah data pemeriksaan pasien dalam jumlah besar secara instan menggunakan template Excel.
- 📊 **Laporan & Dashboard**: Visualisasi data statistik pemeriksaan pasien.
- 🔒 **Sistem Otentikasi**: Pembagian hak akses admin dan petugas puskesmas untuk keamanan data medis.

---

## 🛠️ Tech Stack

- **Backend**: PHP (PDO MySQL)
- **Frontend**: HTML5, Vanilla CSS, Vanilla JavaScript
- **Database**: MySQL / MariaDB
- **Machine Learning Engine**: Python 3 (Math & JSON Library)

---

## 📂 Struktur Direktori Utama

```text
├── api/                  # REST API untuk operasi CRUD dan jembatan ke Python
│   ├── predict_python.php# Menghubungkan PHP dengan skrip Naive Bayes Python
│   └── import_pasien.php # Logika import data pasien via Excel
├── assets/               # CSS, JS, logo, dan template excel
├── config/               # Konfigurasi koneksi database
├── includes/             # Layout header, footer, dan otentikasi
├── ml/                   # Model Naive Bayes Python
│   └── naive_bayes.py    # Skrip inti pemrosesan Gaussian Naive Bayes
├── views/                # Halaman antarmuka pengguna (Dashboard, Prediksi, dll)
├── database.sql          # Skema database MySQL
└── README.md             # Dokumentasi sistem
```

---

## 🚀 Cara Instalasi & Penggunaan

### 1. Prasyarat Sistem
Pastikan komputer Anda telah terinstal:
- Web Server (sangat disarankan menggunakan **Laragon** atau XAMPP).
- **Python 3.x** (telah terdaftar di PATH sistem Anda).

### 2. Kloning Repositori
```bash
git clone git@github.com:esnpendosa/prediksi-nv-Puskesmas-Cerme.git
cd prediksi-nv-Puskesmas-Cerme
```

### 3. Konfigurasi Database
1. Buat database baru bernama `db_hipertensi_cerme`.
2. Import file [database.sql](database.sql) ke dalam MySQL Anda.
3. Sesuaikan konfigurasi database Anda di [config/koneksi.php](config/koneksi.php):
   ```php
   $host = 'localhost';
   $db   = 'db_hipertensi_cerme';
   $user = 'root';
   $pass = ''; // isi password database Anda jika ada
   ```

### 4. Menjalankan Aplikasi
1. Start server Apache dan MySQL di Laragon/XAMPP Anda.
2. Buka browser dan akses alamat local Anda (misal: `http://localhost/prediksinv` atau `http://prediksinv.test`).
3. Login menggunakan akun default:
   - **Username**: `admin`
   - **Password**: `admin123`

---

## 🧪 Model Matematika Naive Bayes

Variabel numerik yang diproses menggunakan distribusi **Gaussian PDF (Normal)**:
$$P(x_i \mid y) = \frac{1}{\sqrt{2\pi\sigma_y^2}} e^{-\frac{(x_i - \mu_y)^2}{2\sigma_y^2}}$$

Dimana:
- $\mu_y$ : Rata-rata (Mean) nilai atribut untuk kelas $y$.
- $\sigma_y$ : Standar Deviasi nilai atribut untuk kelas $y$.

Variabel kategorikal diproses menggunakan **Laplace Smoothing**:
$$P(x_i \mid y) = \frac{N_{ic} + 1}{N_c + V}$$
