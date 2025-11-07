<?php
namespace App\Models;

use App\Database\Database;
use PDO;

class Cuti {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO cuti (employee_id, jenis_cuti, alasan, tanggal_mulai, tanggal_selesai, 
                            lama_hari, alamat_cuti, telp_cuti, tanggal_pengajuan, pejabat_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['employee_id'], $data['jenis_cuti'], $data['alasan'],
            $data['tanggal_mulai'], $data['tanggal_selesai'], $data['lama_hari'],
            $data['alamat_cuti'], $data['telp_cuti'], $data['tanggal_pengajuan'], $data['pejabat_id']
        ]);
        return $this->db->lastInsertId();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("
            SELECT c.*, e.nama as employee_nama, e.nip as employee_nip, 
                   e.jabatan as employee_jabatan, e.unit_kerja as employee_unit,
                   e.masa_kerja_tahun, e.sisa_cuti_n2, e.sisa_cuti_n1, e.sisa_cuti_n,
                   p.nama as pejabat_nama, p.jabatan as pejabat_jabatan
            FROM cuti c
            JOIN employees e ON c.employee_id = e.id
            LEFT JOIN pejabat p ON c.pejabat_id = p.id
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getByEmployeeId($employeeId) {
        $stmt = $this->db->prepare("
            SELECT c.*, p.nama as pejabat_nama 
            FROM cuti c
            LEFT JOIN pejabat p ON c.pejabat_id = p.id
            WHERE c.employee_id = ? 
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$employeeId]);
        return $stmt->fetchAll();
    }

    public function getPendingList() {
        $stmt = $this->db->prepare("
            SELECT c.*, e.nama as employee_nama, e.nip as employee_nip
            FROM cuti c
            JOIN employees e ON c.employee_id = e.id
            WHERE c.status = 'pending'
            ORDER BY c.created_at ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getProsesList() {
        $stmt = $this->db->prepare("
            SELECT c.*, e.nama as employee_nama, e.nip as employee_nip
            FROM cuti c
            JOIN employees e ON c.employee_id = e.id
            WHERE c.status = 'proses'
            ORDER BY c.created_at ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function updateStatus($id, $status, $approvedBy = null) {
        $sql = "UPDATE cuti SET status = ?";
        $params = [$status];
        
        if ($approvedBy) {
            $sql .= ", approved_by = ?, approved_at = NOW()";
            $params[] = $approvedBy;
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $id;
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function updateFormPath($id, $formPath) {
        $stmt = $this->db->prepare("UPDATE cuti SET form_path = ? WHERE id = ?");
        return $stmt->execute([$formPath, $id]);
    }

    public function updateSignedEmployeePath($id, $path) {
        $stmt = $this->db->prepare("UPDATE cuti SET form_signed_employee_path = ? WHERE id = ?");
        return $stmt->execute([$path, $id]);
    }

    public function updateSignedAtasanPath($id, $path) {
        $stmt = $this->db->prepare("UPDATE cuti SET form_signed_atasan_path = ? WHERE id = ?");
        return $stmt->execute([$path, $id]);
    }

    public function updatePersetujuanAtasan($id, $persetujuan, $catatan = null) {
        $stmt = $this->db->prepare("
            UPDATE cuti SET persetujuan_atasan = ?, catatan_atasan = ? WHERE id = ?
        ");
        return $stmt->execute([$persetujuan, $catatan, $id]);
    }

    public function getStatistics() {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'proses' THEN 1 ELSE 0 END) as proses,
                SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai,
                SUM(CASE WHEN status = 'cancel' THEN 1 ELSE 0 END) as cancel
            FROM cuti
        ");
        $stmt->execute();
        return $stmt->fetch();
    }
}