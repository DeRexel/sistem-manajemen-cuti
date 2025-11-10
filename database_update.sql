-- Update database untuk fitur tanda tangan digital
USE sicuti_db;

-- Tambah kolom untuk tanda tangan digital di tabel employees
ALTER TABLE employees ADD COLUMN digital_signature_path VARCHAR(255) AFTER alamat;
ALTER TABLE employees ADD COLUMN use_digital_signature BOOLEAN DEFAULT FALSE AFTER digital_signature_path;

-- Tambah kolom untuk tracking metode tanda tangan di tabel cuti
ALTER TABLE cuti ADD COLUMN signature_method ENUM('digital', 'manual') DEFAULT 'manual' AFTER form_signed_atasan_path;