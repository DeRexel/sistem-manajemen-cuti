<?php
namespace App\Controllers;

use App\Models\Cuti;
use App\Models\Employee;
use App\Models\Pejabat;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class AdminController {
    private $cutiModel;
    private $employeeModel;
    private $pejabatModel;

    public function __construct() {
        $this->cutiModel = new Cuti();
        $this->employeeModel = new Employee();
        $this->pejabatModel = new Pejabat();
    }

    private function getTwig(Request $request): Twig {
        return $request->getAttribute('twig');
    }

    public function pendingList(Request $request, Response $response) {
        $pendingList = $this->cutiModel->getPendingList();
        
        return $this->getTwig($request)->render($response, 'admin/cuti-approval.twig', [
            'cuti_list' => $pendingList,
            'status' => 'pending',
            'current_role' => $_SESSION['role'],
            'current_user' => $_SESSION['username']
        ]);
    }

    public function prosesList(Request $request, Response $response) {
        $prosesList = $this->cutiModel->getProsesList();
        
        return $this->getTwig($request)->render($response, 'admin/cuti-approval.twig', [
            'cuti_list' => $prosesList,
            'status' => 'proses',
            'current_role' => $_SESSION['role'],
            'current_user' => $_SESSION['username']
        ]);
    }

    public function updateStatus(Request $request, Response $response, $args) {
        $response = $response->withHeader('Content-Type', 'application/json');
        $cutiId = $args['id'];
        $data = $request->getParsedBody();
        $newStatus = $data['status'];
        
        // Basic status update
        $this->cutiModel->updateStatus($cutiId, $newStatus);
        
        // Update status message based on new status
        if ($newStatus === 'proses') {
            $this->cutiModel->updateStatusMessage($cutiId, 'Menunggu keputusan atasan');
        } elseif ($newStatus === 'selesai') {
            $this->cutiModel->updateStatusMessage($cutiId, 'Cuti telah disetujui');
        }
        
        // Handle cancel with reason
        if ($newStatus === 'cancel') {
            if (isset($data['alasan_admin'])) {
                $this->cutiModel->updateCancelInfo($cutiId, $data['alasan_admin'], 'admin');
            } elseif (isset($data['alasan_atasan'])) {
                $this->cutiModel->updateCancelInfo($cutiId, $data['alasan_atasan'], 'atasan');
            }
        }
        
        // Handle approval decision
        if (isset($data['persetujuan_atasan'])) {
            $this->cutiModel->updatePersetujuanAtasan($cutiId, $data['persetujuan_atasan'], $data['catatan_atasan'] ?? null);
        }
        
        $response->getBody()->write('OK');
        return $response;
    }

    public function uploadAtasanSigned(Request $request, Response $response, $args) {
        $response = $response->withHeader('Content-Type', 'application/json');
        $cutiId = $args['id'];
        $uploadedFiles = $request->getUploadedFiles();
        
        if (isset($uploadedFiles['signed_form'])) {
            $uploadedFile = $uploadedFiles['signed_form'];
            
            if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
                $fileName = 'signed_atasan_' . $cutiId . '.pdf';
                $uploadedFile->moveTo(__DIR__ . '/../../public/uploads/signed_forms/' . $fileName);
                
                $this->cutiModel->updateSignedAtasanPath($cutiId, $fileName);
                
                $response->getBody()->write(json_encode(['success' => true]));
                return $response;
            }
        }
        
        $response->getBody()->write(json_encode(['success' => false]));
        return $response;
    }

    public function employeeList(Request $request, Response $response) {
        $employees = $this->employeeModel->getAll();
        $pejabatList = $this->pejabatModel->getAll();
        
        return $this->getTwig($request)->render($response, 'admin/employee-list.twig', [
            'employees' => $employees,
            'pejabat_list' => $pejabatList,
            'current_role' => $_SESSION['role'],
            'current_user' => $_SESSION['username']
        ]);
    }

    public function createEmployee(Request $request, Response $response) {
        $data = $request->getParsedBody();
        $this->employeeModel->create($data);
        
        return $response->withHeader('Location', '/admin/employees')->withStatus(302);
    }

    public function pejabatList(Request $request, Response $response) {
        $pejabatList = $this->pejabatModel->getAll();
        
        return $this->getTwig($request)->render($response, 'admin/pejabat-list.twig', [
            'pejabat_list' => $pejabatList,
            'current_role' => $_SESSION['role'],
            'current_user' => $_SESSION['username']
        ]);
    }

    public function createPejabat(Request $request, Response $response) {
        $data = $request->getParsedBody();
        $this->pejabatModel->create($data);
        
        return $response->withHeader('Location', '/admin/pejabat')->withStatus(302);
    }

    public function getDetail(Request $request, Response $response, $args) {
        $cutiId = $args['id'];
        $cuti = $this->cutiModel->findById($cutiId);
        
        if (!$cuti) {
            return $response->withJson(['success' => false]);
        }
        
        $pdfPath = $cuti['form_signed_employee_path'] ?? $cuti['form_path'] ?? null;
        
        return $response->withJson([
            'success' => true,
            'pdf_path' => $pdfPath
        ]);
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
        
        // For admin: Show atasan signed form even without decision
        if ($_SESSION['role'] === 'admin' && $cuti['form_signed_atasan_path']) {
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
        
        // Generate PDF if not exists
        $pdfService = new \App\Services\PDFService();
        $pdfContent = $pdfService->generateCutiForm($cuti);
        $response->getBody()->write($pdfContent);
        return $response->withHeader('Content-Type', 'application/pdf');
    }

    public function historyList(Request $request, Response $response) {
        $params = $request->getQueryParams();
        $yearStart = $params['year_start'] ?? null;
        $yearEnd = $params['year_end'] ?? null;
        $employeeId = $params['employee_id'] ?? null;
        $status = $params['status'] ?? null;
        
        $cutiList = $this->cutiModel->getAllWithFilters($yearStart, $yearEnd, $employeeId, $status);
        $employees = $this->employeeModel->getAll();
        
        return $this->getTwig($request)->render($response, 'admin/cuti-history.twig', [
            'cuti_list' => $cutiList,
            'employees' => $employees,
            'filters' => $params,
            'current_role' => $_SESSION['role'],
            'current_user' => $_SESSION['username']
        ]);
    }

    public function exportHistory(Request $request, Response $response) {
        $params = $request->getQueryParams();
        $yearStart = $params['year_start'] ?? date('Y');
        $yearEnd = $params['year_end'] ?? date('Y');
        $employeeId = $params['employee_id'] ?? null;
        $status = $params['status'] ?? null;
        $format = $params['format'] ?? 'pdf';
        
        $cutiList = $this->cutiModel->getAllWithFilters($yearStart, $yearEnd, $employeeId, $status);
        
        if ($format === 'excel') {
            return $this->exportHistoryToExcel($response, $cutiList, $yearStart, $yearEnd);
        } else {
            return $this->exportHistoryToPDF($response, $cutiList, $yearStart, $yearEnd);
        }
    }

    private function exportHistoryToPDF($response, $cutiList, $yearStart, $yearEnd) {
        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator('SICLE');
        $pdf->SetTitle('Laporan Riwayat Cuti ' . $yearStart . '-' . $yearEnd);
        $pdf->SetMargins(15, 15, 15);
        $pdf->AddPage();
        
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'LAPORAN RIWAYAT CUTI SEMUA PEGAWAI', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, 'Periode: ' . $yearStart . ' - ' . $yearEnd, 0, 1, 'C');
        $pdf->Ln(5);
        
        $totalHari = 0;
        $html = '<table border="1" cellpadding="2">
            <tr style="background-color:#f0f0f0;">
                <th width="15%">Pegawai</th>
                <th width="10%">NIP</th>
                <th width="15%">Jenis Cuti</th>
                <th width="15%">Periode</th>
                <th width="8%">Hari</th>
                <th width="12%">Status</th>
                <th width="15%">Pejabat</th>
                <th width="10%">Tanggal</th>
            </tr>';
        
        foreach ($cutiList as $cuti) {
            $totalHari += $cuti['lama_hari'];
            $html .= '<tr>
                <td>' . $cuti['employee_nama'] . '</td>
                <td>' . $cuti['employee_nip'] . '</td>
                <td>' . ucfirst($cuti['jenis_cuti']) . '</td>
                <td>' . date('d/m/Y', strtotime($cuti['tanggal_mulai'])) . ' - ' . date('d/m/Y', strtotime($cuti['tanggal_selesai'])) . '</td>
                <td style="text-align:center;">' . $cuti['lama_hari'] . '</td>
                <td>' . ucfirst($cuti['status']) . '</td>
                <td>' . ($cuti['pejabat_nama'] ?? '-') . '</td>
                <td>' . date('d/m/Y', strtotime($cuti['tanggal_pengajuan'])) . '</td>
            </tr>';
        }
        
        $html .= '<tr style="background-color:#f0f0f0;">
            <td colspan="4"><strong>TOTAL</strong></td>
            <td style="text-align:center;"><strong>' . $totalHari . '</strong></td>
            <td colspan="3"></td>
        </tr></table>';
        
        $pdf->writeHTML($html, true, false, true, false, '');
        
        $fileName = 'laporan_admin_cuti_' . $yearStart . '_' . $yearEnd . '.pdf';
        $pdfContent = $pdf->Output('', 'S');
        
        $response->getBody()->write($pdfContent);
        return $response
            ->withHeader('Content-Type', 'application/pdf')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }

    private function exportHistoryToExcel($response, $cutiList, $yearStart, $yearEnd) {
        $fileName = 'laporan_admin_cuti_' . $yearStart . '_' . $yearEnd . '.csv';
        
        $csv = "LAPORAN RIWAYAT CUTI SEMUA PEGAWAI PERIODE $yearStart - $yearEnd\n\n";
        $csv .= "Pegawai,NIP,Jenis Cuti,Tanggal Mulai,Tanggal Selesai,Lama Hari,Status,Pejabat,Tanggal Pengajuan\n";
        
        $totalHari = 0;
        foreach ($cutiList as $cuti) {
            $totalHari += $cuti['lama_hari'];
            $csv .= $cuti['employee_nama'] . ','
                . $cuti['employee_nip'] . ','
                . ucfirst($cuti['jenis_cuti']) . ','
                . date('d/m/Y', strtotime($cuti['tanggal_mulai'])) . ','
                . date('d/m/Y', strtotime($cuti['tanggal_selesai'])) . ','
                . $cuti['lama_hari'] . ','
                . ucfirst($cuti['status']) . ','
                . ($cuti['pejabat_nama'] ?? '-') . ','
                . date('d/m/Y', strtotime($cuti['tanggal_pengajuan'])) . "\n";
        }
        
        $csv .= "\nRINGKASAN\n";
        $csv .= "Total Hari Cuti,$totalHari\n";
        
        $response->getBody()->write($csv);
        return $response
            ->withHeader('Content-Type', 'text/csv')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }
}