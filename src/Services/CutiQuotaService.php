<?php
namespace App\Services;

use App\Models\Employee;
use App\Models\Cuti;

class CutiQuotaService {
    private $employeeModel;
    private $cutiModel;
    private $db;

    public function __construct() {
        $this->employeeModel = new Employee();
        $this->cutiModel = new Cuti();
        $this->db = \App\Database\Database::getInstance()->getConnection();
    }

    /**
     * Validasi pengajuan cuti berdasarkan aturan PNS
     */
    public function validateCutiRequest($employeeId, $jenisCuti, $lamaHari, $tanggalMulai) {
        $employee = $this->employeeModel->findById($employeeId);
        $errors = [];

        // Validasi berdasarkan jenis cuti
        switch ($jenisCuti) {
            case 'tahunan':
                $errors = array_merge($errors, $this->validateCutiTahunan($employee, $lamaHari));
                break;
            case 'besar':
                $errors = array_merge($errors, $this->validateCutiBesar($employee, $lamaHari));
                break;
            case 'sakit':
                $errors = array_merge($errors, $this->validateCutiSakit($employee, $lamaHari));
                break;
            case 'melahirkan':
                $errors = array_merge($errors, $this->validateCutiMelahirkan($employee, $lamaHari));
                break;
            case 'alasan_penting':
                $errors = array_merge($errors, $this->validateCutiAlasanPenting($employee, $lamaHari));
                break;
            case 'diluar_tanggungan':
                $errors = array_merge($errors, $this->validateCutiDiluarTanggungan($employee, $lamaHari));
                break;
        }

        return $errors;
    }

    private function validateCutiTahunan($employee, $lamaHari) {
        $errors = [];
        
        // Minimal 1 tahun kerja
        if ($employee['masa_kerja_tahun'] < 1) {
            $errors[] = 'Cuti tahunan hanya dapat diambil setelah bekerja minimal 1 tahun';
        }

        // Minimal 1 hari kerja
        if ($lamaHari < 1) {
            $errors[] = 'Cuti tahunan minimal 1 hari kerja';
        }

        // Cek kuota tersedia
        $totalSisa = $employee['sisa_cuti_n2'] + $employee['sisa_cuti_n1'] + $employee['sisa_cuti_n'];
        if ($lamaHari > $totalSisa) {
            $errors[] = "Kuota cuti tidak mencukupi. Tersedia: {$totalSisa} hari, diminta: {$lamaHari} hari";
        }

        return $errors;
    }

    private function validateCutiBesar($employee, $lamaHari) {
        $errors = [];
        
        // Minimal 5 tahun kerja (kecuali untuk haji)
        if ($employee['masa_kerja_tahun'] < 5) {
            $errors[] = 'Cuti besar hanya dapat diambil setelah bekerja minimal 5 tahun (kecuali untuk ibadah haji)';
        }

        // Maksimal 3 bulan
        if ($lamaHari > 90) {
            $errors[] = 'Cuti besar maksimal 3 bulan (90 hari)';
        }

        return $errors;
    }

    private function validateCutiSakit($employee, $lamaHari) {
        $errors = [];
        
        // Maksimal 1 tahun
        if ($lamaHari > 365) {
            $errors[] = 'Cuti sakit maksimal 1 tahun (365 hari)';
        }

        return $errors;
    }

    private function validateCutiMelahirkan($employee, $lamaHari) {
        $errors = [];
        
        // Hanya untuk perempuan (asumsi dari nama atau data lain)
        // Maksimal 3 bulan
        if ($lamaHari > 90) {
            $errors[] = 'Cuti melahirkan maksimal 3 bulan (90 hari)';
        }

        return $errors;
    }

    private function validateCutiAlasanPenting($employee, $lamaHari) {
        $errors = [];
        
        // Maksimal 1 bulan
        if ($lamaHari > 30) {
            $errors[] = 'Cuti karena alasan penting maksimal 1 bulan (30 hari)';
        }

        return $errors;
    }

    private function validateCutiDiluarTanggungan($employee, $lamaHari) {
        $errors = [];
        
        // Minimal 5 tahun kerja
        if ($employee['masa_kerja_tahun'] < 5) {
            $errors[] = 'Cuti di luar tanggungan negara hanya dapat diambil setelah bekerja minimal 5 tahun';
        }

        // Maksimal 3 tahun
        if ($lamaHari > 1095) {
            $errors[] = 'Cuti di luar tanggungan negara maksimal 3 tahun (1095 hari)';
        }

        return $errors;
    }

    /**
     * Cek berkas tambahan yang diperlukan
     */
    public function getRequiredDocuments($jenisCuti, $alasan = '') {
        $required = [];
        
        switch ($jenisCuti) {
            case 'sakit':
                if (strpos(strtolower($alasan), 'rawat inap') !== false) {
                    $required[] = 'Surat keterangan rawat inap dari Unit Pelayanan Kesehatan';
                } else {
                    $required[] = 'Surat keterangan dokter';
                }
                break;
                
            case 'melahirkan':
                $required[] = 'Surat keterangan dokter/bidan';
                break;
                
            case 'alasan_penting':
                $alasanLower = strtolower($alasan);
                if (strpos($alasanLower, 'sakit keras') !== false) {
                    $required[] = 'Surat keterangan rawat inap dari Unit Pelayanan Kesehatan';
                } elseif (strpos($alasanLower, 'meninggal') !== false) {
                    $required[] = 'Surat keterangan kematian dari Desa/RT';
                } elseif (strpos($alasanLower, 'perkawinan') !== false || strpos($alasanLower, 'nikah') !== false) {
                    $required[] = 'Keterangan/undangan pernikahan';
                } elseif (strpos($alasanLower, 'melahirkan') !== false || strpos($alasanLower, 'caesar') !== false) {
                    $required[] = 'Surat keterangan rawat inap dari Unit Pelayanan Kesehatan';
                } elseif (strpos($alasanLower, 'kebakaran') !== false || strpos($alasanLower, 'bencana') !== false) {
                    $required[] = 'Surat keterangan dari Desa/RT';
                }
                break;
                
            case 'diluar_tanggungan':
                $alasanLower = strtolower($alasan);
                if (strpos($alasanLower, 'tugas negara') !== false || strpos($alasanLower, 'tugas belajar') !== false) {
                    $required[] = 'Surat penugasan atau surat perintah tugas negara/tugas belajar';
                } elseif (strpos($alasanLower, 'bekerja') !== false) {
                    $required[] = 'Surat keputusan atau surat penugasan/pengangkatan dalam jabatan';
                } elseif (strpos($alasanLower, 'keturunan') !== false) {
                    $required[] = 'Surat keterangan dokter spesialis';
                } elseif (strpos($alasanLower, 'berkebutuhan khusus') !== false || strpos($alasanLower, 'perawatan khusus') !== false) {
                    $required[] = 'Surat keterangan dokter spesialis';
                } elseif (strpos($alasanLower, 'orang tua') !== false || strpos($alasanLower, 'mertua') !== false) {
                    $required[] = 'Surat keterangan dokter';
                }
                break;
        }
        
        return $required;
    }

    /**
     * Hitung penggunaan kuota cuti tahunan
     */
    public function calculateQuotaUsage($employeeId, $lamaHari) {
        $employee = $this->employeeModel->findById($employeeId);
        
        $breakdown = [
            'n2' => 0,
            'n1' => 0, 
            'n' => 0
        ];
        
        $remaining = $lamaHari;
        
        // Gunakan sisa N-2 terlebih dahulu
        if ($remaining > 0 && $employee['sisa_cuti_n2'] > 0) {
            $used = min($remaining, $employee['sisa_cuti_n2']);
            $breakdown['n2'] = $used;
            $remaining -= $used;
        }
        
        // Kemudian gunakan sisa N-1
        if ($remaining > 0 && $employee['sisa_cuti_n1'] > 0) {
            $used = min($remaining, $employee['sisa_cuti_n1']);
            $breakdown['n1'] = $used;
            $remaining -= $used;
        }
        
        // Terakhir gunakan kuota tahun ini
        if ($remaining > 0 && $employee['sisa_cuti_n'] > 0) {
            $used = min($remaining, $employee['sisa_cuti_n']);
            $breakdown['n'] = $used;
            $remaining -= $used;
        }
        
        return [
            'breakdown' => $breakdown,
            'total_used' => $lamaHari - $remaining,
            'insufficient' => $remaining > 0
        ];
    }

    /**
     * Kurangi kuota cuti setelah pengajuan disetujui
     */
    public function deductQuota($employeeId, $lamaHari, $jenisCuti) {
        if ($jenisCuti !== 'tahunan') {
            return true; // Hanya cuti tahunan yang mengurangi kuota
        }

        $quotaUsage = $this->calculateQuotaUsage($employeeId, $lamaHari);
        
        if ($quotaUsage['insufficient']) {
            return false;
        }

        $employee = $this->employeeModel->findById($employeeId);
        
        // Update sisa kuota
        $newN2 = $employee['sisa_cuti_n2'] - $quotaUsage['breakdown']['n2'];
        $newN1 = $employee['sisa_cuti_n1'] - $quotaUsage['breakdown']['n1'];
        $newN = $employee['sisa_cuti_n'] - $quotaUsage['breakdown']['n'];
        
        $stmt = $this->db->prepare("
            UPDATE employees 
            SET sisa_cuti_n2 = ?, sisa_cuti_n1 = ?, sisa_cuti_n = ?
            WHERE id = ?
        ");
        
        return $stmt->execute([$newN2, $newN1, $newN, $employeeId]);
    }

    /**
     * Update kuota tahunan (dijalankan setiap awal tahun)
     */
    public function updateAnnualQuota($employeeId = null) {
        $currentYear = date('Y');
        
        if ($employeeId) {
            $employees = [$this->employeeModel->findById($employeeId)];
        } else {
            $employees = $this->employeeModel->getAll();
        }
        
        foreach ($employees as $employee) {
            if ($employee['last_quota_update'] == $currentYear) {
                continue; // Sudah diupdate tahun ini
            }
            
            // Hitung sisa cuti yang bisa dibawa (maksimal 6 hari)
            $sisaCutiBisa = min($employee['sisa_cuti_n'], 6);
            
            // Update kuota
            $stmt = $this->db->prepare("
                UPDATE employees 
                SET sisa_cuti_n2 = sisa_cuti_n1,
                    sisa_cuti_n1 = ?,
                    sisa_cuti_n = kuota_cuti_tahunan,
                    last_quota_update = ?
                WHERE id = ?
            ");
            
            $stmt->execute([$sisaCutiBisa, $currentYear, $employee['id']]);
            
            // Insert ke history
            $stmt = $this->db->prepare("
                INSERT INTO cuti_quota_history 
                (employee_id, tahun, kuota_awal, sisa_kuota, sisa_dari_tahun_sebelumnya)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                kuota_awal = VALUES(kuota_awal),
                sisa_kuota = VALUES(sisa_kuota),
                sisa_dari_tahun_sebelumnya = VALUES(sisa_dari_tahun_sebelumnya)
            ");
            
            $stmt->execute([
                $employee['id'], 
                $currentYear, 
                $employee['kuota_cuti_tahunan'],
                $employee['kuota_cuti_tahunan'],
                $sisaCutiBisa
            ]);
        }
    }

    /**
     * Generate keterangan kuota untuk formulir
     */
    public function generateQuotaDescription($employeeId, $lamaHari) {
        $quotaUsage = $this->calculateQuotaUsage($employeeId, $lamaHari);
        $breakdown = $quotaUsage['breakdown'];
        
        $descriptions = [];
        
        if ($breakdown['n2'] > 0) {
            $descriptions[] = "Sisa cuti N-2: {$breakdown['n2']} hari";
        }
        
        if ($breakdown['n1'] > 0) {
            $descriptions[] = "Sisa cuti N-1: {$breakdown['n1']} hari";
        }
        
        if ($breakdown['n'] > 0) {
            $descriptions[] = "Cuti tahun ini: {$breakdown['n']} hari";
        }
        
        return implode(', ', $descriptions);
    }
}