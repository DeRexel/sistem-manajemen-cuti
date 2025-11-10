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
        // Check if signature_method column exists
        try {
            $stmt = $this->db->prepare("
                INSERT INTO cuti (employee_id, jenis_cuti, alasan, tanggal_mulai, tanggal_selesai, 
                                lama_hari, alamat_cuti, telp_cuti, tanggal_pengajuan, pejabat_id, signature_method)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['employee_id'], $data['jenis_cuti'], $data['alasan'],
                $data['tanggal_mulai'], $data['tanggal_selesai'], $data['lama_hari'],
                $data['alamat_cuti'], $data['telp_cuti'], $data['tanggal_pengajuan'], 
                $data['pejabat_id'], $data['signature_method'] ?? 'manual'
            ]);
        } catch (\PDOException $e) {
            // Fallback for old database schema
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
        }
        return $this->db->lastInsertId();
    }

    public function findById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, e.nama as employee_nama, e.nip as employee_nip, 
                       e.jabatan as employee_jabatan, e.unit_kerja as employee_unit,
                       e.masa_kerja_tahun, e.sisa_cuti_n2, e.sisa_cuti_n1, e.sisa_cuti_n,
                       e.digital_signature_path, e.use_digital_signature,
                       p.nama as pejabat_nama, p.jabatan as pejabat_jabatan, p.nip as pejabat_nip
                FROM cuti c
                JOIN employees e ON c.employee_id = e.id
                LEFT JOIN pejabat p ON c.pejabat_id = p.id
                WHERE c.id = ?
            ");
        } catch (\PDOException $e) {
            // Fallback for old database schema
            $stmt = $this->db->prepare("
                SELECT c.*, e.nama as employee_nama, e.nip as employee_nip, 
                       e.jabatan as employee_jabatan, e.unit_kerja as employee_unit,
                       e.masa_kerja_tahun, e.sisa_cuti_n2, e.sisa_cuti_n1, e.sisa_cuti_n,
                       p.nama as pejabat_nama, p.jabatan as pejabat_jabatan, p.nip as pejabat_nip
                FROM cuti c
                JOIN employees e ON c.employee_id = e.id
                LEFT JOIN pejabat p ON c.pejabat_id = p.id
                WHERE c.id = ?
            ");
        }
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

    public function updateStatus($id, $status, $approvedBy = null, $statusMessage = null) {
        try {
            // Always use basic update to ensure compatibility
            $stmt = $this->db->prepare("UPDATE cuti SET status = ? WHERE id = ?");
            $result = $stmt->execute([$status, $id]);
            
            // Try to update additional fields if they exist
            if ($statusMessage) {
                try {
                    $stmt2 = $this->db->prepare("UPDATE cuti SET status_message = ? WHERE id = ?");
                    $stmt2->execute([$statusMessage, $id]);
                } catch (\PDOException $e) {
                    // Ignore if column doesn't exist
                }
            }
            
            return $result;
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function updateStatusMessage($id, $statusMessage) {
        try {
            $stmt = $this->db->prepare("UPDATE cuti SET status_message = ? WHERE id = ?");
            return $stmt->execute([$statusMessage, $id]);
        } catch (\PDOException $e) {
            // Ignore if column doesn't exist
            return true;
        }
    }

    public function updateCancelInfo($id, $alasan, $cancelledBy) {
        try {
            if ($cancelledBy === 'admin') {
                $stmt = $this->db->prepare("UPDATE cuti SET alasan_admin = ?, cancelled_by = ? WHERE id = ?");
            } else {
                $stmt = $this->db->prepare("UPDATE cuti SET alasan_atasan = ?, cancelled_by = ? WHERE id = ?");
            }
            return $stmt->execute([$alasan, $cancelledBy, $id]);
        } catch (\PDOException $e) {
            // Fallback: update alasan_cancel for old schema
            $stmt = $this->db->prepare("UPDATE cuti SET alasan_cancel = ? WHERE id = ?");
            return $stmt->execute([$alasan, $id]);
        }
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

    public function getByEmployeeIdAndYearRange($employeeId, $yearStart, $yearEnd) {
        $stmt = $this->db->prepare("
            SELECT c.*, p.nama as pejabat_nama 
            FROM cuti c
            LEFT JOIN pejabat p ON c.pejabat_id = p.id
            WHERE c.employee_id = ? 
            AND YEAR(c.tanggal_pengajuan) BETWEEN ? AND ?
            ORDER BY c.tanggal_pengajuan DESC
        ");
        $stmt->execute([$employeeId, $yearStart, $yearEnd]);
        return $stmt->fetchAll();
    }

    public function getAllWithFilters($yearStart = null, $yearEnd = null, $employeeId = null, $status = null) {
        $sql = "
            SELECT c.*, e.nama as employee_nama, e.nip as employee_nip, p.nama as pejabat_nama 
            FROM cuti c
            JOIN employees e ON c.employee_id = e.id
            LEFT JOIN pejabat p ON c.pejabat_id = p.id
            WHERE 1=1
        ";
        $params = [];
        
        if ($yearStart && $yearEnd) {
            $sql .= " AND YEAR(c.tanggal_pengajuan) BETWEEN ? AND ?";
            $params[] = $yearStart;
            $params[] = $yearEnd;
        }
        
        if ($employeeId) {
            $sql .= " AND c.employee_id = ?";
            $params[] = $employeeId;
        }
        
        if ($status) {
            $sql .= " AND c.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY c.tanggal_pengajuan DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}