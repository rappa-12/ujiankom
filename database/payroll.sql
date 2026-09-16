-- ============================================
-- PAYROLL APP - Database Schema (v6, disederhanakan)
-- Import file ini di phpMyAdmin / MySQL client
--
-- CATATAN PENTING (perbaikan error "kolom tidak ditemukan" saat simpan gaji):
-- Kalau database kamu sebelumnya dibuat dari versi kode yang lebih lama,
-- strukturnya bisa jadi tidak sama persis dengan kode PHP yang sekarang
-- (misalnya ada kolom yang seharusnya sudah tidak dipakai lagi).
-- File ini SENGAJA menghapus dulu tabel-tabel lama (DROP TABLE) sebelum
-- membuatnya lagi dari nol, supaya struktur tabel di database dijamin
-- SAMA PERSIS dengan kolom yang dipakai di kode PHP.
-- Import ulang file ini setiap kali ada error terkait kolom database.
-- ============================================

CREATE DATABASE IF NOT EXISTS payroll_db;
USE payroll_db;

DROP TABLE IF EXISTS gaji;
DROP TABLE IF EXISTS periode;
DROP TABLE IF EXISTS karyawan;
DROP TABLE IF EXISTS users;

-- Tabel akun login
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

-- Tabel master data karyawan (CRUD terpisah)
CREATE TABLE IF NOT EXISTS karyawan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nik VARCHAR(30) NOT NULL UNIQUE,
    nama VARCHAR(100) NOT NULL,
    jabatan VARCHAR(50) NOT NULL
);

-- Tabel master data periode gaji (CRUD terpisah)
CREATE TABLE IF NOT EXISTS periode (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_periode VARCHAR(50) NOT NULL,
    tanggal_mulai DATE NOT NULL,
    tanggal_selesai DATE NOT NULL,
    status ENUM('aktif', 'tidak_aktif') NOT NULL DEFAULT 'aktif'
);

-- Tabel hasil hitung gaji, terhubung ke karyawan & periode
CREATE TABLE IF NOT EXISTS gaji (
    id INT AUTO_INCREMENT PRIMARY KEY,
    karyawan_id INT NOT NULL,
    periode_id INT NOT NULL,
    gaji_pokok INT NOT NULL DEFAULT 0,
    lembur INT NOT NULL DEFAULT 0,
    pinjaman INT NOT NULL DEFAULT 0,
    total_penghasilan INT NOT NULL DEFAULT 0,
    gaji_bersih INT NOT NULL DEFAULT 0,
    tanggal DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (karyawan_id) REFERENCES karyawan(id) ON DELETE CASCADE,
    FOREIGN KEY (periode_id) REFERENCES periode(id) ON DELETE CASCADE
);

-- Contoh data (opsional, boleh dihapus)
INSERT INTO karyawan (nik, nama, jabatan) VALUES
('EMP001', 'Budi Santoso', 'Staff IT'),
('EMP002', 'Andi Wijaya', 'Manager'),
('EMP003', 'Siti Rahma', 'Staff HRD');

INSERT INTO periode (nama_periode, tanggal_mulai, tanggal_selesai, status) VALUES
('September 2026', '2026-09-01', '2026-09-30', 'aktif');

INSERT INTO gaji (karyawan_id, periode_id, gaji_pokok, lembur, pinjaman, total_penghasilan, gaji_bersih, tanggal) VALUES
(1, 1, 4000000, 500000, 300000, 4500000, 4200000, CURDATE()),
(2, 1, 3800000, 0, 0, 3800000, 3800000, CURDATE()),
(3, 1, 4500000, 0, 0, 4500000, 4500000, CURDATE());

-- Catatan: akun admin dibuat lewat database/seed_admin.php (bukan lewat file ini)
-- supaya password otomatis di-hash dengan aman oleh PHP.
