-- 🧹 Hapus database lama jika sudah ada
DROP DATABASE IF EXISTS sicuti_db;

-- 🆕 Buat database baru jika belum ada
CREATE DATABASE IF NOT EXISTS sicuti_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

-- 🔄 Gunakan database tersebut
USE sicuti_db;

CREATE TABLE pejabat (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nip VARCHAR(20) UNIQUE NOT NULL,
    nama VARCHAR(100) NOT NULL,
    jabatan VARCHAR(100) NOT NULL,
    unit_kerja VARCHAR(100),
    level_approval INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE employees (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nip VARCHAR(20) UNIQUE NOT NULL,
    nama VARCHAR(100) NOT NULL,
    jabatan VARCHAR(100),
    unit_kerja VARCHAR(100),
    masa_kerja_tahun INT DEFAULT 0,
    kuota_cuti_tahunan INT DEFAULT 12,
    sisa_cuti_n2 INT DEFAULT 0,
    sisa_cuti_n1 INT DEFAULT 0,
    sisa_cuti_n INT DEFAULT 12,
    email VARCHAR(100),
    phone VARCHAR(20),
    alamat TEXT,
    atasan_langsung_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (atasan_langsung_id) REFERENCES pejabat(id)
);

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    employee_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);

CREATE TABLE cuti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    jenis_cuti ENUM('tahunan', 'besar', 'sakit', 'melahirkan', 'alasan_penting', 'diluar_tanggungan') NOT NULL,
    alasan TEXT NOT NULL,
    tanggal_mulai DATE NOT NULL,
    tanggal_selesai DATE NOT NULL,
    lama_hari INT NOT NULL,
    alamat_cuti TEXT NOT NULL,
    telp_cuti VARCHAR(20),
    status ENUM('pending', 'proses', 'selesai', 'cancel') DEFAULT 'pending',
    alasan_cancel TEXT,
    tanggal_pengajuan DATE NOT NULL,
    form_path VARCHAR(255),
    form_signed_employee_path VARCHAR(255),
    form_signed_atasan_path VARCHAR(255),
    pejabat_id INT,
    persetujuan_atasan ENUM('disetujui', 'perubahan', 'ditangguhkan', 'tidak_disetujui'),
    catatan_atasan TEXT,
    approved_by INT,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    FOREIGN KEY (pejabat_id) REFERENCES pejabat(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
);

CREATE TABLE system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default data
INSERT INTO pejabat (nip, nama, jabatan, unit_kerja) VALUES
('196501011990031001', 'Dr. Ahmad Suryadi, M.T.', 'Dekan Fakultas Teknik', 'Fakultas Teknik');

INSERT INTO employees (nip, nama, jabatan, unit_kerja, masa_kerja_tahun, atasan_langsung_id) VALUES
('198505152010121001', 'Budi Santoso', 'Dosen', 'Fakultas Teknik', 14, 1),
('199203102015041002', 'Siti Aminah', 'Staff Administrasi', 'Fakultas Teknik', 9, 1);

INSERT INTO users (username, password, role, employee_id) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL),
('budi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 1),
('siti', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 2);

INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('kuota_cuti_tahunan', '12', 'Kuota cuti tahunan default'),
('max_sisa_cuti_n1', '6', 'Maksimal sisa cuti tahun sebelumnya yang bisa dibawa'),
('institusi_nama', 'Universitas Palangka Raya', 'Nama institusi'),
('institusi_alamat', 'Palangka Raya', 'Alamat institusi');


-- Tambah kolom untuk tanda tangan digital di tabel employees
ALTER TABLE employees ADD COLUMN digital_signature_path VARCHAR(255) AFTER alamat;
ALTER TABLE employees ADD COLUMN use_digital_signature BOOLEAN DEFAULT FALSE AFTER digital_signature_path;

-- Tambah kolom untuk tracking metode tanda tangan di tabel cuti
ALTER TABLE cuti ADD COLUMN signature_method ENUM('digital', 'manual') DEFAULT 'manual' AFTER form_signed_atasan_path;

-- Update database untuk menambahkan kolom status_message
ALTER TABLE cuti ADD COLUMN status_message VARCHAR(255) DEFAULT NULL AFTER status;

-- Update existing records dengan status message yang sesuai
UPDATE cuti SET status_message = CASE 
    WHEN status = 'pending' AND form_signed_employee_path IS NULL THEN 'Menunggu konfirmasi admin'
    WHEN status = 'pending' AND form_signed_employee_path IS NOT NULL THEN 'Menunggu upload formulir sudah ditandatangani'
    WHEN status = 'proses' THEN 'Menunggu keputusan atasan'
    WHEN status = 'selesai' THEN 'Cuti telah disetujui'
    WHEN status = 'cancel' THEN 'Cuti dibatalkan'
    ELSE 'Status tidak diketahui'
END;

-- Update database untuk menambahkan kolom alasan_admin dan alasan_atasan
ALTER TABLE cuti ADD COLUMN alasan_admin TEXT DEFAULT NULL AFTER alasan_cancel;
ALTER TABLE cuti ADD COLUMN alasan_atasan TEXT DEFAULT NULL AFTER alasan_admin;
ALTER TABLE cuti ADD COLUMN cancelled_by ENUM('admin', 'atasan') DEFAULT NULL AFTER alasan_atasan;