<?php
namespace App\Controllers;

use App\Models\Cuti;
use App\Models\Employee;
use App\Models\Pejabat;
use App\Services\PDFService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class CutiController {
    private $cutiModel;
    private $employeeModel;
    private $pejabatModel;
    private $pdfService;

    public function __construct() {
        $this->cutiModel = new Cuti();
        $this->employeeModel = new Employee();
        $this->pejabatModel = new Pejabat();
        $this->pdfService = new PDFService();
    }

    private function getTwig(Request $request): Twig {
        return $request->getAttribute('twig');
    }

    public function showForm(Request $request, Response $response) {
        $employee = $this->employeeModel->findById($_SESSION['employee_id']);
        $pejabatList = $this->pejabatModel->getAll();

        return $this->getTwig($request)->render($response, 'user/cuti-form.twig', [
            'employee' => $employee,
            'pejabat_list' => $pejabatList,
            'current_role' => $_SESSION['role'],
            'current_user' => $_SESSION['username']
        ]);
    }

    public function create(Request $request, Response $response) {
        $data = $request->getParsedBody();
        
        // Calculate days
        $start = new \DateTime($data['tanggal_mulai']);
        $end = new \DateTime($data['tanggal_selesai']);
        $lamaHari = $start->diff($end)->days + 1;
        
        $signatureMethod = $data['signature_method'] ?? 'manual';
        
        $cutiData = [
            'employee_id' => $_SESSION['employee_id'],
            'jenis_cuti' => $data['jenis_cuti'],
            'alasan' => $data['alasan'],
            'tanggal_mulai' => $data['tanggal_mulai'],
            'tanggal_selesai' => $data['tanggal_selesai'],
            'lama_hari' => $lamaHari,
            'alamat_cuti' => $data['alamat_cuti'],
            'telp_cuti' => $data['telp_cuti'],
            'tanggal_pengajuan' => date('Y-m-d'),
            'pejabat_id' => $data['pejabat_id'],
            'signature_method' => $signatureMethod
        ];
        
        $cutiId = $this->cutiModel->create($cutiData);
        
        if ($signatureMethod === 'digital') {
            // Auto-generate PDF with digital signature and submit
            $this->generateDigitalSignedPDF($cutiId);
            $this->cutiModel->updateStatusMessage($cutiId, 'Menunggu konfirmasi admin');
            return $response->withHeader('Location', '/user/cuti/history')->withStatus(302);
        } else {
            // Generate and save PDF for manual signature
            $cuti = $this->cutiModel->findById($cutiId);
            $pdfContent = $this->pdfService->generateCutiForm($cuti);
            $fileName = 'cuti_form_' . $cutiId . '.pdf';
            $filePath = __DIR__ . '/../../public/uploads/' . $fileName;
            file_put_contents($filePath, $pdfContent);
            $this->cutiModel->updateFormPath($cutiId, $fileName);
            $this->cutiModel->updateStatusMessage($cutiId, 'Menunggu upload formulir anda tanda tangani');
        }
        
        return $response->withHeader('Location', '/user/cuti/history')->withStatus(302);
    }

    public function downloadPDF(Request $request, Response $response, $args) {
        $cutiId = $args['id'];
        $cuti = $this->cutiModel->findById($cutiId);
        
        if (!$cuti) {
            return $response->withStatus(404);
        }
        
        // Priority: Download atasan signed form if decision is made
        if ($cuti['form_signed_atasan_path'] && $cuti['persetujuan_atasan']) {
            $atasanSignedPath = __DIR__ . '/../../public/uploads/signed_forms/' . $cuti['form_signed_atasan_path'];
            if (file_exists($atasanSignedPath)) {
                $pdfContent = file_get_contents($atasanSignedPath);
                $response->getBody()->write($pdfContent);
                return $response
                    ->withHeader('Content-Type', 'application/pdf')
                    ->withHeader('Content-Disposition', 'attachment; filename="' . $cuti['form_signed_atasan_path'] . '"');
            }
        }
        
        // Fallback: Download employee signed form
        if ($cuti['form_signed_employee_path']) {
            $signedPath = __DIR__ . '/../../public/uploads/signed_forms/' . $cuti['form_signed_employee_path'];
            if (file_exists($signedPath)) {
                $pdfContent = file_get_contents($signedPath);
                $response->getBody()->write($pdfContent);
                return $response
                    ->withHeader('Content-Type', 'application/pdf')
                    ->withHeader('Content-Disposition', 'attachment; filename="' . $cuti['form_signed_employee_path'] . '"');
            }
        }
        
        $pdfContent = $this->pdfService->generateCutiForm($cuti);
        
        // Save PDF
        $fileName = 'cuti_form_' . $cutiId . '.pdf';
        $filePath = __DIR__ . '/../../public/uploads/' . $fileName;
        file_put_contents($filePath, $pdfContent);
        
        // Update form path
        $this->cutiModel->updateFormPath($cutiId, $fileName);
        
        $response->getBody()->write($pdfContent);
        return $response
            ->withHeader('Content-Type', 'application/pdf')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }

    public function previewPDF(Request $request, Response $response, $args) {
        $cutiId = $args['id'];
        $cuti = $this->cutiModel->findById($cutiId);
        
        if (!$cuti) {
            return $response->withStatus(404);
        }
        
        // Priority: Show atasan signed form if decision is made
        if ($cuti['form_signed_atasan_path'] && $cuti['persetujuan_atasan']) {
            $atasanSignedPath = __DIR__ . '/../../public/uploads/signed_forms/' . $cuti['form_signed_atasan_path'];
            if (file_exists($atasanSignedPath)) {
                $pdfContent = file_get_contents($atasanSignedPath);
                $response->getBody()->write($pdfContent);
                return $response->withHeader('Content-Type', 'application/pdf');
            }
        }
        
        // Fallback: Show employee signed form
        if ($cuti['form_signed_employee_path']) {
            $signedPath = __DIR__ . '/../../public/uploads/signed_forms/' . $cuti['form_signed_employee_path'];
            if (file_exists($signedPath)) {
                $pdfContent = file_get_contents($signedPath);
                $response->getBody()->write($pdfContent);
                return $response->withHeader('Content-Type', 'application/pdf');
            }
        }
        
        $pdfContent = $this->pdfService->generateCutiForm($cuti);
        $response->getBody()->write($pdfContent);
        return $response->withHeader('Content-Type', 'application/pdf');
    }

    public function uploadSigned(Request $request, Response $response, $args) {
        $response = $response->withHeader('Content-Type', 'application/json');
        $cutiId = $args['id'];
        $uploadedFiles = $request->getUploadedFiles();
        
        if (isset($uploadedFiles['signed_form'])) {
            $uploadedFile = $uploadedFiles['signed_form'];
            
            if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
                $fileName = 'signed_employee_' . $cutiId . '.pdf';
                $uploadedFile->moveTo(__DIR__ . '/../../public/uploads/signed_forms/' . $fileName);
                
                $this->cutiModel->updateSignedEmployeePath($cutiId, $fileName);
                $this->cutiModel->updateStatusMessage($cutiId, 'Menunggu konfirmasi admin');
                
                $response->getBody()->write(json_encode(['success' => true]));
                return $response;
            }
        }
        
        $response->getBody()->write(json_encode(['success' => false]));
        return $response;
    }

    public function submit(Request $request, Response $response, $args) {
        $cutiId = $args['id'];
        $this->cutiModel->updateStatusMessage($cutiId, 'Menunggu konfirmasi admin');
        return $response->withHeader('Location', '/user/dashboard')->withStatus(302);
    }

    public function history(Request $request, Response $response) {
        $cutiList = $this->cutiModel->getByEmployeeId($_SESSION['employee_id']);
        
        return $this->getTwig($request)->render($response, 'user/cuti-history.twig', [
            'cuti_list' => $cutiList,
            'current_role' => $_SESSION['role'],
            'current_user' => $_SESSION['username']
        ]);
    }

    private function generateDigitalSignedPDF($cutiId) {
        $cuti = $this->cutiModel->findById($cutiId);
        $pdfContent = $this->pdfService->generateCutiFormWithDigitalSignature($cuti);
        
        // Save PDF
        $fileName = 'cuti_form_digital_' . $cutiId . '.pdf';
        $filePath = __DIR__ . '/../../public/uploads/signed_forms/' . $fileName;
        file_put_contents($filePath, $pdfContent);
        
        // Update paths
        $this->cutiModel->updateFormPath($cutiId, $fileName);
        $this->cutiModel->updateSignedEmployeePath($cutiId, $fileName);
    }

    public function uploadSignature(Request $request, Response $response) {
        // Set JSON header
        $response = $response->withHeader('Content-Type', 'application/json');
        
        try {
            $uploadedFiles = $request->getUploadedFiles();
            
            if (!isset($uploadedFiles['signature'])) {
                $response->getBody()->write(json_encode(['success' => false, 'error' => 'File tidak ditemukan']));
                return $response->withStatus(200);
            }
            
            $uploadedFile = $uploadedFiles['signature'];
            
            if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
                $response->getBody()->write(json_encode(['success' => false, 'error' => 'Error upload file']));
                return $response->withStatus(200);
            }
            
            $fileName = 'signature_' . $_SESSION['employee_id'] . '_' . time() . '.png';
            $tempPath = sys_get_temp_dir() . '/' . $fileName . '_temp';
            $finalPath = __DIR__ . '/../../public/uploads/employee_signatures/' . $fileName;
            
            // Move uploaded file to temp location first
            $uploadedFile->moveTo($tempPath);
            
            // Delete old signature file if exists
            $oldEmployee = $this->employeeModel->findById($_SESSION['employee_id']);
            if ($oldEmployee && !empty($oldEmployee['digital_signature_path'])) {
                $oldSignaturePath = __DIR__ . '/../../public/uploads/employee_signatures/' . $oldEmployee['digital_signature_path'];
                if (file_exists($oldSignaturePath)) {
                    unlink($oldSignaturePath);
                }
            }
            
            // Resize image to 160x80px
            if ($this->resizeSignatureImage($tempPath, $finalPath, 160, 80)) {
                // Clean up temp file
                if (file_exists($tempPath)) {
                    unlink($tempPath);
                }
                
                // Update database with new signature and auto-enable digital signature
                $this->employeeModel->updateDigitalSignature($_SESSION['employee_id'], $fileName);
                $this->employeeModel->toggleDigitalSignature($_SESSION['employee_id'], true);
                
                $response->getBody()->write(json_encode(['success' => true, 'filename' => $fileName]));
                return $response->withStatus(200);
            } else {
                // Clean up temp file on error
                if (file_exists($tempPath)) {
                    unlink($tempPath);
                }
                $response->getBody()->write(json_encode(['success' => false, 'error' => 'Gagal memproses gambar']));
                return $response->withStatus(200);
            }
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]));
            return $response->withStatus(200);
        }
    }

    private function resizeSignatureImage($sourcePath, $destPath, $width, $height) {
        try {
            if (!file_exists($sourcePath)) {
                return false;
            }
            
            $imageInfo = getimagesize($sourcePath);
            if (!$imageInfo) {
                return false;
            }
            
            $sourceWidth = $imageInfo[0];
            $sourceHeight = $imageInfo[1];
            $imageType = $imageInfo[2];
            
            // Create source image
            $sourceImage = false;
            switch ($imageType) {
                case IMAGETYPE_JPEG:
                    $sourceImage = imagecreatefromjpeg($sourcePath);
                    break;
                case IMAGETYPE_PNG:
                    $sourceImage = imagecreatefrompng($sourcePath);
                    break;
                case IMAGETYPE_GIF:
                    $sourceImage = imagecreatefromgif($sourcePath);
                    break;
                default:
                    return false;
            }
            
            if (!$sourceImage) {
                return false;
            }
            
            // Create destination image
            $destImage = imagecreatetruecolor($width, $height);
            if (!$destImage) {
                imagedestroy($sourceImage);
                return false;
            }
            
            // Preserve transparency for PNG
            if ($imageType == IMAGETYPE_PNG) {
                imagealphablending($destImage, false);
                imagesavealpha($destImage, true);
                $transparent = imagecolorallocatealpha($destImage, 255, 255, 255, 127);
                imagefill($destImage, 0, 0, $transparent);
            }
            
            // Resize image
            imagecopyresampled($destImage, $sourceImage, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);
            
            // Save as PNG
            $result = imagepng($destImage, $destPath);
            
            // Clean up
            imagedestroy($sourceImage);
            imagedestroy($destImage);
            
            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function toggleDigitalSignature(Request $request, Response $response) {
        $data = $request->getParsedBody();
        $useDigital = $data['use_digital'] === 'true';
        
        $this->employeeModel->toggleDigitalSignature($_SESSION['employee_id'], $useDigital);
        
        return $response->withJson(['success' => true]);
    }



    public function exportReport(Request $request, Response $response) {
        $params = $request->getQueryParams();
        $yearStart = $params['year_start'] ?? date('Y');
        $yearEnd = $params['year_end'] ?? date('Y');
        $format = $params['format'] ?? 'pdf';
        
        $employee = $this->employeeModel->findById($_SESSION['employee_id']);
        $cutiList = $this->cutiModel->getByEmployeeIdAndYearRange($_SESSION['employee_id'], $yearStart, $yearEnd);
        
        if ($format === 'excel') {
            return $this->exportToExcel($response, $cutiList, $employee, $yearStart, $yearEnd);
        } else {
            return $this->exportToPDF($response, $cutiList, $employee, $yearStart, $yearEnd);
        }
    }

    private function exportToPDF($response, $cutiList, $employee, $yearStart, $yearEnd) {
        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator('SICLE');
        $pdf->SetTitle('Laporan Cuti ' . $yearStart . '-' . $yearEnd);
        $pdf->SetMargins(15, 15, 15);
        $pdf->AddPage();
        
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'LAPORAN RIWAYAT CUTI', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, 'Periode: ' . $yearStart . ' - ' . $yearEnd, 0, 1, 'C');
        $pdf->Ln(5);
        
        // Employee Info
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 5, 'DATA PEGAWAI', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(30, 5, 'Nama', 0, 0, 'L');
        $pdf->Cell(5, 5, ':', 0, 0, 'C');
        $pdf->Cell(0, 5, $employee['nama'], 0, 1, 'L');
        $pdf->Cell(30, 5, 'NIP', 0, 0, 'L');
        $pdf->Cell(5, 5, ':', 0, 0, 'C');
        $pdf->Cell(0, 5, $employee['nip'], 0, 1, 'L');
        $pdf->Cell(30, 5, 'Jabatan', 0, 0, 'L');
        $pdf->Cell(5, 5, ':', 0, 0, 'C');
        $pdf->Cell(0, 5, $employee['jabatan'], 0, 1, 'L');
        $pdf->Cell(30, 5, 'Unit Kerja', 0, 0, 'L');
        $pdf->Cell(5, 5, ':', 0, 0, 'C');
        $pdf->Cell(0, 5, $employee['unit_kerja'], 0, 1, 'L');
        $pdf->Ln(5);
        
        $totalHari = 0;
        $html = '<table border="1" cellpadding="3">
            <tr style="background-color:#f0f0f0;">
                <th width="15%">Tanggal</th>
                <th width="20%">Jenis Cuti</th>
                <th width="25%">Periode</th>
                <th width="10%">Hari</th>
                <th width="15%">Status</th>
                <th width="15%">Pejabat</th>
            </tr>';
        
        foreach ($cutiList as $cuti) {
            $totalHari += $cuti['lama_hari'];
            $html .= '<tr>
                <td>' . date('d/m/Y', strtotime($cuti['tanggal_pengajuan'])) . '</td>
                <td>' . ucfirst($cuti['jenis_cuti']) . '</td>
                <td>' . date('d/m/Y', strtotime($cuti['tanggal_mulai'])) . ' - ' . date('d/m/Y', strtotime($cuti['tanggal_selesai'])) . '</td>
                <td style="text-align:center;">' . $cuti['lama_hari'] . '</td>
                <td>' . ucfirst($cuti['status']) . '</td>
                <td>' . ($cuti['pejabat_nama'] ?? '-') . '</td>
            </tr>';
        }
        
        $html .= '<tr style="background-color:#f0f0f0;">
            <td colspan="3"><strong>TOTAL</strong></td>
            <td style="text-align:center;"><strong>' . $totalHari . '</strong></td>
            <td colspan="2"></td>
        </tr></table>';
        
        $pdf->writeHTML($html, true, false, true, false, '');
        
        $fileName = 'laporan_cuti_' . $yearStart . '_' . $yearEnd . '.pdf';
        $pdfContent = $pdf->Output('', 'S');
        
        $response->getBody()->write($pdfContent);
        return $response
            ->withHeader('Content-Type', 'application/pdf')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }

    private function exportToExcel($response, $cutiList, $employee, $yearStart, $yearEnd) {
        $fileName = 'laporan_cuti_' . $yearStart . '_' . $yearEnd . '.csv';
        
        $csv = "LAPORAN RIWAYAT CUTI PERIODE $yearStart - $yearEnd\n\n";
        $csv .= "DATA PEGAWAI\n";
        $csv .= "Nama,{$employee['nama']}\n";
        $csv .= "NIP,{$employee['nip']}\n";
        $csv .= "Jabatan,{$employee['jabatan']}\n";
        $csv .= "Unit Kerja,{$employee['unit_kerja']}\n\n";
        
        $csv .= "RIWAYAT CUTI\n";
        $csv .= "Tanggal Pengajuan,Jenis Cuti,Tanggal Mulai,Tanggal Selesai,Lama Hari,Status,Pejabat\n";
        
        $totalHari = 0;
        foreach ($cutiList as $cuti) {
            $totalHari += $cuti['lama_hari'];
            $csv .= date('d/m/Y', strtotime($cuti['tanggal_pengajuan'])) . ','
                . ucfirst($cuti['jenis_cuti']) . ','
                . date('d/m/Y', strtotime($cuti['tanggal_mulai'])) . ','
                . date('d/m/Y', strtotime($cuti['tanggal_selesai'])) . ','
                . $cuti['lama_hari'] . ','
                . ucfirst($cuti['status']) . ','
                . ($cuti['pejabat_nama'] ?? '-') . "\n";
        }
        
        $csv .= "\nRINGKASAN\n";
        $csv .= "Total Hari Cuti,$totalHari\n";
        $csv .= "Sisa Cuti N-2,{$employee['sisa_cuti_n2']}\n";
        $csv .= "Sisa Cuti N-1,{$employee['sisa_cuti_n1']}\n";
        $csv .= "Sisa Cuti Tahun Ini,{$employee['sisa_cuti_n']}\n";
        
        $response->getBody()->write($csv);
        return $response
            ->withHeader('Content-Type', 'text/csv')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }
}