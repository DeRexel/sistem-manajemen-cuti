<?php
namespace App\Models;

use App\Database\Database;
use PDO;

class Pejabat {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM pejabat WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getAll() {
        $stmt = $this->db->prepare("SELECT * FROM pejabat ORDER BY nama");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO pejabat (nip, nama, jabatan, unit_kerja, level_approval)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['nip'], $data['nama'], $data['jabatan'], 
            $data['unit_kerja'], $data['level_approval']
        ]);
    }
}