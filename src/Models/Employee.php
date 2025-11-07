<?php
namespace App\Models;

use App\Database\Database;
use PDO;

class Employee {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM employees WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function updateSisaCuti($employeeId, $jenisCuti, $lamaHari) {
        if ($jenisCuti === 'tahunan') {
            $stmt = $this->db->prepare("
                UPDATE employees 
                SET sisa_cuti_n = sisa_cuti_n - ? 
                WHERE id = ?
            ");
            $stmt->execute([$lamaHari, $employeeId]);
        }
    }

    public function getAll() {
        $stmt = $this->db->prepare("SELECT * FROM employees ORDER BY nama");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO employees (nip, nama, jabatan, unit_kerja, masa_kerja_tahun, 
                                 kuota_cuti_tahunan, sisa_cuti_n, email, phone, alamat, atasan_langsung_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['nip'], $data['nama'], $data['jabatan'], $data['unit_kerja'],
            $data['masa_kerja_tahun'], $data['kuota_cuti_tahunan'], $data['sisa_cuti_n'],
            $data['email'], $data['phone'], $data['alamat'], $data['atasan_langsung_id']
        ]);
    }
}