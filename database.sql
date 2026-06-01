CREATE DATABASE IF NOT EXISTS db_hipertensi_cerme;
USE db_hipertensi_cerme;

CREATE TABLE pengguna (
    id_pengguna INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    Role ENUM('admin', 'petugas') NOT NULL,
    foto_profil VARCHAR(255) DEFAULT NULL
);

CREATE TABLE pasien (
    id_pasien INT AUTO_INCREMENT PRIMARY KEY,
    nik VARCHAR(20) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    jenis_kelamin ENUM('laki-laki', 'perempuan') NOT NULL,
    tanggal_lahir DATE NOT NULL,
    alamat TEXT NOT NULL,
    no_hp VARCHAR(15) NOT NULL
);

CREATE TABLE atribut_kesehatan (
    id_atribut INT AUTO_INCREMENT PRIMARY KEY,
    id_pasien INT NOT NULL,
    tanggal_pemeriksaan DATE NOT NULL,
    tekanan_sistolik INT NOT NULL,
    tekanan_diastolik INT NOT NULL,
    imt DECIMAL(5,2) NOT NULL,
    merokok ENUM('Ya', 'Tidak') NOT NULL,
    konsumsi_alkohol ENUM('Ya', 'Tidak') NOT NULL,
    kurang_buah_sayur ENUM('Ya', 'Tidak') NOT NULL,
    diabetes ENUM('Ya', 'Tidak') NOT NULL,
    riwayat_hipertensi ENUM('Ya', 'Tidak') NOT NULL,
    FOREIGN KEY (id_pasien) REFERENCES pasien(id_pasien) ON DELETE CASCADE
);

CREATE TABLE hasil_prediksi (
    id_prediksi INT AUTO_INCREMENT PRIMARY KEY,
    id_pasien INT NOT NULL,
    id_atribut_kesehatan INT NOT NULL,
    tanggal_prediksi DATE NOT NULL,
    hasil_prediksi ENUM('Rendah', 'Tinggi') NOT NULL,
    FOREIGN KEY (id_pasien) REFERENCES pasien(id_pasien) ON DELETE CASCADE,
    FOREIGN KEY (id_atribut_kesehatan) REFERENCES atribut_kesehatan(id_atribut) ON DELETE CASCADE
);

-- DUMMY DATA UNTUK TESTING MAPPING & NAIVE BAYES LOKAL --
INSERT INTO pengguna (nama, email, username, password, Role) VALUES 
('Admin Cerme', 'admin@cerme.go.id', 'admin', 'admin123', 'admin');

INSERT INTO pasien (nik, nama, jenis_kelamin, tanggal_lahir, alamat, no_hp) VALUES
('35251100001', 'Budi Santoso', 'laki-laki', '1978-05-12', 'Cerme Lor', '081234567890'),
('35251100002', 'Siti Aminah', 'perempuan', '1995-08-20', 'Cerme Kidul', '081234567891'),
('35251100003', 'Agus Setiawan', 'laki-laki', '1970-01-01', 'Betiting', '081234567892'),
('35251100004', 'Ani Yuliana', 'perempuan', '1985-11-30', 'Banjarsari', '081234567893'),
('35251100005', 'Joko Susilo', 'laki-laki', '1965-03-15', 'Cerme Lor', '081234567894');

INSERT INTO atribut_kesehatan (id_pasien, tanggal_pemeriksaan, tekanan_sistolik, tekanan_diastolik, imt, merokok, konsumsi_alkohol, kurang_buah_sayur, diabetes, riwayat_hipertensi) VALUES
(1, '2023-10-01', 145, 95, 26.5, 'Ya', 'Tidak', 'Ya', 'Tidak', 'Ya'),
(2, '2023-10-02', 115, 75, 22.0, 'Tidak', 'Tidak', 'Tidak', 'Tidak', 'Tidak'),
(3, '2023-10-03', 150, 100, 29.0, 'Ya', 'Ya', 'Ya', 'Ya', 'Ya'),
(4, '2023-10-04', 120, 80, 24.5, 'Tidak', 'Tidak', 'Ya', 'Tidak', 'Tidak'),
(5, '2023-10-05', 160, 105, 28.0, 'Ya', 'Tidak', 'Ya', 'Ya', 'Ya');

INSERT INTO hasil_prediksi (id_pasien, id_atribut_kesehatan, tanggal_prediksi, hasil_prediksi) VALUES
(1, 1, '2023-10-01', 'Tinggi'),
(2, 2, '2023-10-02', 'Rendah'),
(3, 3, '2023-10-03', 'Tinggi');
