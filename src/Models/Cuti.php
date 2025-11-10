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
        try {
            $stmt = $this->db->prepare("
                INSERT INTO cuti (employee_id, jenis_cuti, alasan, tanggal_mulai, tanggal_selesai, 
                                lama_hari, alamat_cuti, telp_cuti, tanggal_pengajuan, pejabat_id, 
                                signature_method, berkas_tambahan_required, kuota_source, kuota_breakdown)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['employee_id'], $data['jenis_cuti'], $data['alasan'],
                $data['tanggal_mulai'], $data['tanggal_selesai'], $data['lama_hari'],
                $data['alamat_cuti'], $data['telp_cuti'], $data['tanggal_pengajuan'], 
                $data['pejabat_id'], $data['signature_method'] ?? 'manual',
                $data['berkas_tambahan_required'] ?? false,
                $data['kuota_source'] ?? 'n',
                $data['kuota_breakdown'] ?? null
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
        $stmt = $this->db->prepare("
            SELECT c.*, e.nama as employee_nama, e.nip as employee_nip, 
                   e.jabatan as employee_jabatan, e.unit_kerja as employee_unit,
                   e.masa_kerja_tahun, e.masa_kerja_bulan, e.sisa_cuti_n2, e.sisa_cuti_n1, e.sisa_cuti_n,
                   e.digital_signature_path, e.use_digital_signature,
                   p.nama as pejabat_nama, p.jabatan as pejabat_jabatan, p.nip as pejabat_nip
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

    public function updateBerkasTambahanPath($id, $path) {
        try {
            $stmt = $this->db->prepare("UPDATE cuti SET berkas_tambahan_path = ? WHERE id = ?");
            return $stmt->execute([$path, $id]);
        } catch (\PDOException $e) {
            return true; // Ignore if column doesn't exist
        }
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

    public function getConflictingLeave($employeeId, $startDate, $endDate) {
        $stmt = $this->db->prepare("
            SELECT * FROM cuti 
            WHERE employee_id = ? 
            AND status IN ('proses', 'selesai')
            AND (
                (tanggal_mulai <= ? AND tanggal_selesai >= ?) OR
                (tanggal_mulai <= ? AND tanggal_selesai >= ?) OR
                (tanggal_mulai >= ? AND tanggal_selesai <= ?)
            )
        ");
        $stmt->execute([$employeeId, $startDate, $startDate, $endDate, $endDate, $startDate, $endDate]);
        return $stmt->fetchAll();
    }

    public function getCutiBesarThisYear($employeeId) {
        $year = date('Y');
        $stmt = $this->db->prepare("
            SELECT * FROM cuti 
            WHERE employee_id = ? 
            AND jenis_cuti = 'besar'
            AND YEAR(tanggal_pengajuan) = ?
            AND status IN ('proses', 'selesai')
        ");
        $stmt->execute([$employeeId, $year]);
        return $stmt->fetch();
    }

    public function getCutiTahunanThisYear($employeeId) {
        $year = date('Y');
        $stmt = $this->db->prepare("
            SELECT SUM(lama_hari) as total_days FROM cuti 
            WHERE employee_id = ? 
            AND jenis_cuti = 'tahunan'
            AND YEAR(tanggal_pengajuan) = ?
            AND status IN ('proses', 'selesai')
        ");
        $stmt->execute([$employeeId, $year]);
        $result = $stmt->fetch();
        return ['total_days' => $result['total_days'] ?? 0];
    }
}