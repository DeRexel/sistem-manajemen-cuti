<?php
namespace App\Services;

use App\Models\Cuti;
use App\Models\Employee;

class CutiValidationService {
    private $cutiModel;
    private $employeeModel;

    public function __construct() {
        $this->cutiModel = new Cuti();
        $this->employeeModel = new Employee();
    }

    public function validateCutiRequest($employeeId, $jenisCuti, $lamaHari, $tanggalMulai, $tanggalSelesai, $alasan = '') {
        $errors = [];
        $employee = $this->employeeModel->findById($employeeId);
        
        // Basic validations
        $errors = array_merge($errors, $this->validateBasicRules($tanggalMulai, $tanggalSelesai, $lamaHari));
        
        // Date conflict validation
        $errors = array_merge($errors, $this->validateDateConflicts($employeeId, $tanggalMulai, $tanggalSelesai));
        
        // Specific leave type validations
        switch ($jenisCuti) {
            case 'tahunan':
                $errors = array_merge($errors, $this->validateCutiTahunan($employee, $lamaHari, $tanggalMulai));
                break;
            case 'besar':
                $errors = array_merge($errors, $this->validateCutiBesar($employee, $lamaHari));
                break;
            case 'sakit':
                $errors = array_merge($errors, $this->validateCutiSakit($lamaHari));
                break;
            case 'melahirkan':
                $errors = array_merge($errors, $this->validateCutiMelahirkan($employee, $lamaHari));
                break;
            case 'alasan_penting':
                $errors = array_merge($errors, $this->validateCutiAlasanPenting($lamaHari, $alasan));
                break;
            case 'diluar_tanggungan':
                $errors = array_merge($errors, $this->validateCutiDiluarTanggungan($employee, $lamaHari));
                break;
        }
        
        return $errors;
    }

    private function validateBasicRules($tanggalMulai, $tanggalSelesai, $lamaHari) {
        $errors = [];
        $today = date('Y-m-d');
        
        // Cannot apply for past dates
        if ($tanggalMulai < $today) {
            $errors[] = 'Tidak dapat mengajukan cuti untuk tanggal yang sudah lewat';
        }
        
        // Start date cannot be after end date
        if ($tanggalMulai > $tanggalSelesai) {
            $errors[] = 'Tanggal mulai tidak boleh lebih dari tanggal selesai';
        }
        
        // Minimum 1 day
        if ($lamaHari < 1) {
            $errors[] = 'Lama cuti minimal 1 hari';
        }
        
        return $errors;
    }

    private function validateDateConflicts($employeeId, $tanggalMulai, $tanggalSelesai) {
        $errors = [];
        
        // Check for overlapping approved leave
        $conflictingLeave = $this->cutiModel->getConflictingLeave($employeeId, $tanggalMulai, $tanggalSelesai);
        
        if (!empty($conflictingLeave)) {
            $errors[] = 'Terdapat cuti yang sudah disetujui pada periode tersebut';
        }
        
        return $errors;
    }

    private function validateCutiTahunan($employee, $lamaHari, $tanggalMulai) {
        $errors = [];
        
        // Check if employee has worked at least 1 year
        if ($employee['masa_kerja_tahun'] < 1) {
            $errors[] = 'Cuti tahunan hanya dapat diambil setelah bekerja minimal 1 tahun';
        }
        
        // Check if using current year quota for next year
        $currentYear = date('Y');
        $leaveYear = date('Y', strtotime($tanggalMulai));
        
        if ($leaveYear > $currentYear) {
            // Only allow using N-1 and N-2 quota for next year
            $availableForNextYear = $employee['sisa_cuti_n1'] + $employee['sisa_cuti_n2'];
            if ($lamaHari > $availableForNextYear) {
                $errors[] = "Cuti untuk tahun {$leaveYear} hanya dapat menggunakan sisa kuota tahun sebelumnya (N-1: {$employee['sisa_cuti_n1']}, N-2: {$employee['sisa_cuti_n2']}). Kuota tahun ini hanya berlaku untuk tahun {$currentYear}";
            }
        } else {
            // Check quota availability for current year
            $totalAvailable = $employee['sisa_cuti_n'] + $employee['sisa_cuti_n1'] + $employee['sisa_cuti_n2'];
            if ($lamaHari > $totalAvailable) {
                $errors[] = "Kuota cuti tidak mencukupi. Tersedia: {$totalAvailable} hari";
            }
        }
        
        // Maximum 24 days including carry-over
        $maxDays = 24;
        if ($lamaHari > $maxDays) {
            $errors[] = "Cuti tahunan maksimal {$maxDays} hari termasuk sisa tahun sebelumnya";
        }
        
        // Check if already took CB this year
        $cbThisYear = $this->cutiModel->getCutiBesarThisYear($employee['id']);
        if ($cbThisYear) {
            $errors[] = 'Tidak dapat mengambil cuti tahunan karena sudah mengambil cuti besar tahun ini';
        }
        
        return $errors;
    }

    private function validateCutiBesar($employee, $lamaHari) {
        $errors = [];
        
        // Must work at least 5 years (except for hajj)
        if ($employee['masa_kerja_tahun'] < 5) {
            $errors[] = 'Cuti besar hanya dapat diambil setelah bekerja minimal 5 tahun (kecuali untuk ibadah haji)';
        }
        
        // Maximum 3 months (90 days)
        if ($lamaHari > 90) {
            $errors[] = 'Cuti besar maksimal 3 bulan (90 hari)';
        }
        
        // Check if already took CT this year
        $ctThisYear = $this->cutiModel->getCutiTahunanThisYear($employee['id']);
        if ($ctThisYear && $ctThisYear['total_days'] > 0) {
            // CB should be reduced by CT already taken
            $remainingCB = 90 - $ctThisYear['total_days'];
            if ($lamaHari > $remainingCB) {
                $errors[] = "Cuti besar dikurangi cuti tahunan yang sudah diambil. Tersisa: {$remainingCB} hari";
            }
        }
        
        return $errors;
    }

    private function validateCutiSakit($lamaHari) {
        $errors = [];
        
        // Maximum 1 year (365 days)
        if ($lamaHari > 365) {
            $errors[] = 'Cuti sakit maksimal 1 tahun (365 hari)';
        }
        
        return $errors;
    }

    private function validateCutiMelahirkan($employee, $lamaHari) {
        $errors = [];
        
        // Fixed 3 months (90 days)
        if ($lamaHari != 90) {
            $errors[] = 'Cuti melahirkan adalah 3 bulan (90 hari)';
        }
        
        return $errors;
    }

    private function validateCutiAlasanPenting($lamaHari, $alasan) {
        $errors = [];
        
        // Maximum 1 month (30 days)
        if ($lamaHari > 30) {
            $errors[] = 'Cuti karena alasan penting maksimal 1 bulan (30 hari)';
        }
        
        // Check if reason requires specific documents
        $validReasons = [
            'sakit_keras_keluarga', 'meninggal_keluarga', 'perkawinan', 
            'istri_melahirkan', 'musibah', 'pemulihan_jiwa'
        ];
        
        return $errors;
    }

    private function validateCutiDiluarTanggungan($employee, $lamaHari) {
        $errors = [];
        
        // Must work at least 5 years
        if ($employee['masa_kerja_tahun'] < 5) {
            $errors[] = 'Cuti di luar tanggungan negara hanya dapat diambil setelah bekerja minimal 5 tahun';
        }
        
        // Maximum 3 years (1095 days)
        if ($lamaHari > 1095) {
            $errors[] = 'Cuti di luar tanggungan negara maksimal 3 tahun';
        }
        
        return $errors;
    }

    public function getRequiredDocuments($jenisCuti, $alasan = '') {
        $docs = [];
        
        switch ($jenisCuti) {
            case 'sakit':
                if (strpos($alasan, '1 hari') === false) {
                    $docs[] = 'Surat keterangan dokter';
                }
                break;
                
            case 'melahirkan':
                $docs[] = 'Surat keterangan rawat inap dari Unit Pelayanan Kesehatan';
                break;
                
            case 'alasan_penting':
                $docs = $this->getAlasanPentingDocs($alasan);
                break;
                
            case 'besar':
                if (strpos(strtolower($alasan), 'haji') !== false) {
                    $docs[] = 'Jadwal keberangkatan/kelompok terbang (kloter) dari instansi penyelenggara haji';
                }
                break;
                
            case 'diluar_tanggungan':
                $docs = $this->getDiluarTanggunganDocs($alasan);
                break;
        }
        
        return $docs;
    }

    private function getAlasanPentingDocs($alasan) {
        $docs = [];
        $alasanLower = strtolower($alasan);
        
        if (strpos($alasanLower, 'sakit keras') !== false) {
            $docs[] = 'Surat keterangan rawat inap dari Unit Pelayanan Kesehatan';
        }
        
        if (strpos($alasanLower, 'meninggal') !== false) {
            $docs[] = 'Surat keterangan kematian dari Desa/RT';
        }
        
        if (strpos($alasanLower, 'perkawinan') !== false || strpos($alasanLower, 'nikah') !== false) {
            $docs[] = 'Keterangan/undangan pernikahan';
        }
        
        if (strpos($alasanLower, 'melahirkan') !== false || strpos($alasanLower, 'caesar') !== false) {
            $docs[] = 'Surat keterangan rawat inap dari Unit Pelayanan Kesehatan';
        }
        
        if (strpos($alasanLower, 'kebakaran') !== false || strpos($alasanLower, 'bencana') !== false) {
            $docs[] = 'Surat keterangan dari Desa/RT';
        }
        
        return $docs;
    }

    private function getDiluarTanggunganDocs($alasan) {
        $docs = [];
        $alasanLower = strtolower($alasan);
        
        if (strpos($alasanLower, 'tugas negara') !== false || strpos($alasanLower, 'tugas belajar') !== false) {
            $docs[] = 'Surat penugasan atau surat perintah tugas negara/tugas belajar';
        }
        
        if (strpos($alasanLower, 'bekerja') !== false) {
            $docs[] = 'Surat keputusan atau surat penugasan/pengangkatan dalam jabatan';
        }
        
        if (strpos($alasanLower, 'keturunan') !== false) {
            $docs[] = 'Surat keterangan dokter spesialis';
        }
        
        if (strpos($alasanLower, 'berkebutuhan khusus') !== false || strpos($alasanLower, 'perawatan khusus') !== false) {
            $docs[] = 'Surat keterangan dokter spesialis';
        }
        
        if (strpos($alasanLower, 'orang tua') !== false || strpos($alasanLower, 'mertua') !== false) {
            $docs[] = 'Surat keterangan dokter';
        }
        
        return $docs;
    }
}