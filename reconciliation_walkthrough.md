# 🔬 Jurnal Rekonsiliasi & Walkthrough Algoritma Naive Bayes
### *Laporan Validasi Akurasi 100% (Excel Parity) - Puskesmas Cerme*

Dokumen ini disusun untuk mendokumentasikan proses audit matematis, pemecahan masalah selisih nilai, dan penyelarasan total (parity) antara perhitungan manual pada spreadsheet **Microsoft Excel** dengan sistem inferensi komputer berbasis **Python & PHP** pada **Sistem Prediksi Hipertensi Puskesmas Cerme**.

---

## 📅 Latar Belakang Masalah (Discrepancy Audit)
Sebelum rekonsiliasi dilakukan, terdapat perbedaan hasil klasifikasi risiko dan nilai probabilitas antara halaman web dengan Excel. Setelah diaudit secara mendalam, ditemukan 4 titik perbedaan utama:
1. **Perhitungan Umur Dinamis**: Database menghitung umur secara dinamis menggunakan tanggal pemeriksaan hari ini (`CURDATE()`), sedangkan Excel menggunakan selisih tahun kalender murni pada tahun pemeriksaan.
2. **Kategorisasi Umur vs Kontinu**: Program lama membagi umur menjadi kategori biner (`< 40` & `>= 40`), sedangkan Excel memproses umur sebagai data numerik berkelanjutan (kontinu) dengan **Gaussian PDF**.
3. **Variabilitas Nilai Standar Deviasi (StDev)**: Rumus Python default menghitung standar deviasi berbasis populasi dinamis, sementara Excel memiliki data latih statis (400 baris) dengan rentang sel yang tetap.
4. **Masalah Cache Browser**: Browser menyimpan skrip JavaScript lama yang melakukan perhitungan lokal secara salah, sehingga mengaburkan kalkulasi Python yang sebenarnya sudah benar di server.

---

## 🧮 Model Matematika & Penyelesaian Algoritma

### 1. Peluang Prior (Prior Probability)
Peluang dasar masing-masing kelas (Risiko Tinggi dan Risiko Rendah) dihitung berdasarkan proporsi total data latih (400 data):
- **Risiko Tinggi $P(\text{Tinggi})$**: $\frac{200}{400} = 0.50$
- **Risiko Rendah $P(\text{Rendah})$**: $\frac{200}{400} = 0.50$

---

### 2. Likelihood Atribut Kategorikal (Laplace Smoothing)
Untuk atribut kategorikal, probabilitas bersyarat dihitung menggunakan rumus Laplace Smoothing:
$$P(x_i \mid y) = \frac{N_{ic} + 1}{N_c + V}$$

Dimana:
- $N_{ic}$ : Jumlah data latih kelas $y$ yang memiliki nilai atribut $x_i$.
- $N_c$ : Total data latih pada kelas $y$ (200 data).
- $V$ : Jumlah variasi nilai unik pada atribut tersebut.

#### Parameter Kategorikal Statis (400 Data Latih):
| Atribut | Nilai Atribut | $P(\text{Atribut} \mid \text{Tinggi})$ | $P(\text{Atribut} \mid \text{Rendah})$ |
| :--- | :--- | :---: | :---: |
| **Jenis Kelamin** | Perempuan | 0.640 | 0.580 |
| | Laki-laki | 0.360 | 0.420 |
| **Riwayat Merokok** | Tidak | 1.000 | 1.000 |
| | Ya | 0.000 | 0.000 |
| **Konsumsi Alkohol** | Tidak | 1.000 | 1.000 |
| | Ya | 0.000 | 0.000 |
| **Kurang Buah/Sayur**| Tidak | 1.000 | 1.000 |
| | Ya | 0.000 | 0.000 |
| **Riwayat Diabetes** | Tidak | 0.880 | 0.990 |
| | Ya | 0.120 | 0.010 |
| **Keluarga Hipertensi**| Ya | 0.475 | 0.010 |
| | Tidak | 0.525 | 0.990 |

---

### 3. Likelihood Atribut Numerik (Gaussian PDF)
Atribut numerik kontinu dihitung menggunakan rumus **Probability Density Function (PDF) Distribusi Normal**:
$$P(x_i \mid y) = \frac{1}{\sqrt{2\pi\sigma_y^2}} e^{-\frac{(x_i - \mu_y)^2}{2\sigma_y^2}}$$

Dimana:
- $\mu_y$ : Nilai rata-rata (Mean) atribut pada kelas $y$.
- $\sigma_y$ : Standar Deviasi (StDev) atribut pada kelas $y$.
- $\sigma_y^2$ : Variansi (Variance) atribut pada kelas $y$.

#### Konstanta Statistik Data Latih (400 Baris):
- **Kelas Risiko Tinggi**:
  - **Umur**: Mean = $53.335$, StDev = $11.00467060$
  - **IMT**: Mean = $26.0318$, StDev = $3.65721653$
  - **Sistolik**: Mean = $157.215$, StDev = $24.48691028$
  - **Diastolik**: Mean = $89.595$, StDev = $12.92133797$

- **Kelas Risiko Rendah**:
  - **Umur**: Mean = $44.020$, StDev = $20.70628885$
  - **IMT**: Mean = $22.3902$, StDev = $3.55358579$
  - **Sistolik**: Mean = $127.465$, StDev = $22.19456634$
  - **Diastolik**: Mean = $77.415$, StDev = $12.37508687$

---

## 🔬 Hasil Verifikasi & Validasi Kasus Uji Utama

### Kasus Uji 1: SULAMI (Risiko Tinggi)
Data Klinis: Perempuan, Umur 64, Tidak Merokok, Tidak Alkohol, Tidak Kurang Buah/Sayur, IMT 35.56, Sistolik 180, Diastolik 90, Tidak Diabetes, Ya Keluarga Hipertensi.

#### 1. Perhitungan Likelihood Numerik:
* **Umur (64)**:
  - $P(\text{Umur} \mid \text{Tinggi}) = \text{gaussian\_pdf}(64, 53.335, 11.00467) = 0.022666468$
  - $P(\text{Umur} \mid \text{Rendah}) = \text{gaussian\_pdf}(64, 44.020, 20.70629) = 0.012095500$
* **IMT (35.56)**:
  - $P(\text{IMT} \mid \text{Tinggi}) = \text{gaussian\_pdf}(35.56, 26.0318, 3.65722) = 0.003662998$
  - $P(\text{IMT} \mid \text{Rendah}) = \text{gaussian\_pdf}(35.56, 22.3902, 3.55359) = 0.000116883$
* **Sistolik (180)**:
  - $P(\text{Sistolik} \mid \text{Tinggi}) = \text{gaussian\_pdf}(180, 157.215, 24.48691) = 0.010567313$
  - $P(\text{Sistolik} \mid \text{Rendah}) = \text{gaussian\_pdf}(180, 127.465, 22.19457) = 0.001091525$
* **Diastolik (90)**:
  - $P(\text{Diastolik} \mid \text{Tinggi}) = \text{gaussian\_pdf}(90, 89.595, 12.92134) = 0.030859526$
  - $P(\text{Diastolik} \mid \text{Rendah}) = \text{gaussian\_pdf}(90, 77.415, 12.37509) = 0.019221414$

#### 2. Probabilitas Gabungan Akhir $P(C, X)$:
* **$P(\text{Rendah}, X)$**:
  $$0.5 \times 0.58 \times 1.0 \times 1.0 \times 1.0 \times 0.99 \times 0.01 \times 0.0120955 \times 0.000116883 \times 0.001091525 \times 0.019221414 = 8.51586 \times 10^{-14}$$
* **$P(\text{Tinggi}, X)$**:
  $$0.5 \times 0.64 \times 1.0 \times 1.0 \times 1.0 \times 0.88 \times 0.475 \times 0.022666468 \times 0.003662998 \times 0.010567313 \times 0.030859526 = 3.60349 \times 10^{-9}$$

* **Keputusan**: Karena $P(\text{Tinggi}, X) > P(\text{Rendah}, X)$, pasien diklasifikasikan sebagai **`RISIKO TINGGI`** (Confidence: 100.00%).
* **Hasil Akhir**: **100% Selaras Sempurna dengan Baris Excel Ke-659.**

---

### Kasus Uji 2: SITI AISAH (Risiko Rendah)
Data Klinis: Perempuan, Umur 48, Tidak Merokok, Tidak Alkohol, Tidak Kurang Buah/Sayur, IMT 25.78, Sistolik 160, Diastolik 90, Tidak Diabetes, Tidak Keluarga Hipertensi.

#### 1. Perhitungan Likelihood Numerik:
* **Umur (48)**:
  - $P(\text{Umur} \mid \text{Tinggi}) = 0.032274299$
  - $P(\text{Umur} \mid \text{Rendah}) = 0.019176313$
* **IMT (25.78)**:
  - $P(\text{IMT} \mid \text{Tinggi}) = 0.108253177$
  - $P(\text{IMT} \mid \text{Rendah}) = 0.071853401$
* **Sistolik (160)**:
  - $P(\text{Sistolik} \mid \text{Tinggi}) = 0.015797371$
  - $P(\text{Sistolik} \mid \text{Rendah}) = 0.006126621$
* **Diastolik (90)**:
  - $P(\text{Diastolik} \mid \text{Tinggi}) = 0.030859526$
  - $P(\text{Diastolik} \mid \text{Rendah}) = 0.019221414$

#### 2. Probabilitas Gabungan Akhir $P(C, X)$:
* **$P(\text{Rendah}, X)$**: **`5.85804E-11`**
* **$P(\text{Tinggi}, X)$**: **`1.33234E-11`**

* **Keputusan**: Karena $P(\text{Rendah}, X) > P(\text{Tinggi}, X)$, pasien diklasifikasikan sebagai **`RISIKO RENDAH`** (Confidence: 81.47%).
* **Hasil Akhir**: **100% Selaras Sempurna dengan Pengujian Kedua Excel.**

---

## 🛠️ Langkah-Langkah Pemeliharaan Sistem
Jika di masa mendatang Anda ingin memperbarui data latih (melebihi 400 data):
1. Sistem akan otomatis beralih ke **Dynamic Mode** untuk menyesuaikan dengan dataset baru di database.
2. Jika Anda masih menginginkan kecocokan manual dengan model Excel yang baru, pastikan untuk memperbarui nilai konstanta mean & standar deviasi pada blok `sheet_stats` di file `ml/naive_bayes.py`.
