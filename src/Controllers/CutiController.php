<?php
namespace App\Controllers;

use App\Models\Cuti;
use App\Models\Employee;
use App\Models\Pejabat;
use App\Services\PDFService;
use App\Services\CutiQuotaService;
use App\Services\CutiValidationService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class CutiController {
    private $cutiModel;
    private $employeeModel;
    private $pejabatModel;
    private $pdfService;
    private $quotaService;
    private $validationService;

    public function __construct() {
        $this->cutiModel = new Cuti();
        $this->employeeModel = new Employee();
        $this->pejabatModel = new Pejabat();
        $this->pdfService = new PDFService();
        $this->quotaService = new CutiQuotaService();
        $this->validationService = new CutiValidationService();
    }

    private function getTwig(Request $request): Twig {
        return $request->getAttribute('twig');
    }

    public function showForm(Request $request, Response $response) {
        $employee = $this->employeeModel->findById($_SESSION['employee_id']);
        $pejabatList = $this->pejabatModel->getAll();
        
        // Update kuota tahunan jika belum diupdate tahun ini
        $this->quotaService->updateAnnualQuota($_SESSION['employee_id']);
        
        // Refresh employee data setelah update kuota
        $employee = $this->employeeModel->findById($_SESSION['employee_id']);
        
        $errorMessage = $_SESSION['error_message'] ?? null;
        unset($_SESSION['error_message']);

        return $this->getTwig($request)->render($response, 'user/cuti-form.twig', [
            'employee' => $employee,
            'pejabat_list' => $pejabatList,
            'current_role' => $_SESSION['role'],
            'current_user' => $_SESSION['username'],
            'error_message' => $errorMessage
        ]);
    }

    public function create(Request $request, Response $response) {
        $data = $request->getParsedBody();
        
        // Calculate days
        $start = new \DateTime($data['tanggal_mulai']);
        $end = new \DateTime($data['tanggal_selesai']);
        $lamaHari = $start->diff($end)->days + 1;
        
<<<<<<< HEAD
        // Set default alamat_cuti if empty
        if (empty($data['alamat_cuti'])) {
            $employee = $this->employeeModel->findById($_SESSION['employee_id']);
            $data['alamat_cuti'] = $employee['alamat'] ?? 'Alamat sesuai KTP';
        }
        
        // Validasi pengajuan cuti
        $errors = $this->validationService->validateCutiRequest(
            $_SESSION['employee_id'], 
            $data['jenis_cuti'], 
            $lamaHari, 
            $data['tanggal_mulai'],
            $data['tanggal_selesai'],
            $data['alasan'] ?? ''
        );
        
        if (!empty($errors)) {
            $_SESSION['error_message'] = implode('<br>', $errors);
            return $response->withHeader('Location', '/user/cuti/create')->withStatus(302);
        }
        
        // Cek berkas tambahan yang diperlukan
        $requiredDocs = $this->validationService->getRequiredDocuments($data['jenis_cuti'], $data['alasan'] ?? '');
        $berkasTambahanRequired = !empty($requiredDocs);
        
        // Hitung penggunaan kuota untuk cuti tahunan
        $quotaUsage = null;
        $kuotaSource = 'n';
        if ($data['jenis_cuti'] === 'tahunan') {
            $quotaUsage = $this->quotaService->calculateQuotaUsage($_SESSION['employee_id'], $lamaHari);
            if ($quotaUsage['insufficient']) {
                $_SESSION['error_message'] = 'Kuota cuti tidak mencukupi untuk pengajuan ini';
                return $response->withHeader('Location', '/user/cuti/create')->withStatus(302);
            }
            
            // Tentukan sumber kuota
            if ($quotaUsage['breakdown']['n2'] > 0 && $quotaUsage['breakdown']['n1'] > 0) {
                $kuotaSource = 'mixed';
            } elseif ($quotaUsage['breakdown']['n2'] > 0) {
                $kuotaSource = 'n2';
            } elseif ($quotaUsage['breakdown']['n1'] > 0) {
                $kuotaSource = 'n1';
            }
        }
        
        $signatureMethod = $data['signature_method'] ?? 'manual';
        
=======
>>>>>>> parent of 53b33dd (Basic Function)
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
<<<<<<< HEAD
            'pejabat_id' => $data['pejabat_id'],
            'signature_method' => $signatureMethod,
            'berkas_tambahan_required' => $berkasTambahanRequired,
            'kuota_source' => $kuotaSource,
            'kuota_breakdown' => $quotaUsage ? json_encode($quotaUsage['breakdown']) : null
=======
            'pejabat_id' => $data['pejabat_id']
>>>>>>> parent of 53b33dd (Basic Function)
        ];
        
        $cutiId = $this->cutiModel->create($cutiData);
        
<<<<<<< HEAD
        // Set required documents message
        if ($berkasTambahanRequired) {
            $_SESSION['required_docs_' . $cutiId] = $requiredDocs;
        }
        
        if ($signatureMethod === 'digital') {
            // Auto-generate PDF with digital signature and submit
            $this->generateDigitalSignedPDF($cutiId);
            $statusMsg = $berkasTambahanRequired ? 'Menunggu upload berkas tambahan' : 'Menunggu konfirmasi admin';
            $this->cutiModel->updateStatusMessage($cutiId, $statusMsg);
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
=======
        return $response->withHeader('Location', '/user/cuti/download/' . $cutiId)->withStatus(302);
>>>>>>> parent of 53b33dd (Basic Function)
    }

    public function downloadPDF(Request $request, Response $response, $args) {
        $cutiId = $args['id'];
        $cuti = $this->cutiModel->findById($cutiId);
        
        if (!$cuti) {
            return $response->withStatus(404);
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

    public function uploadSigned(Request $request, Response $response, $args) {
        $cutiId = $args['id'];
        $uploadedFiles = $request->getUploadedFiles();
        
        if (isset($uploadedFiles['signed_form'])) {
            $uploadedFile = $uploadedFiles['signed_form'];
            
            if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
                $fileName = 'signed_employee_' . $cutiId . '.pdf';
                $uploadedFile->moveTo(__DIR__ . '/../../public/uploads/signed_forms/' . $fileName);
                
                $this->cutiModel->updateSignedEmployeePath($cutiId, $fileName);
<<<<<<< HEAD
                
                // Cek apakah masih perlu berkas tambahan
                $cuti = $this->cutiModel->findById($cutiId);
                if ($cuti['berkas_tambahan_required'] && !$cuti['berkas_tambahan_path']) {
                    $this->cutiModel->updateStatusMessage($cutiId, 'Menunggu upload berkas tambahan');
                } else {
                    $this->cutiModel->updateStatusMessage($cutiId, 'Menunggu konfirmasi admin');
                }
=======
>>>>>>> parent of 53b33dd (Basic Function)
                
                return $response->withJson(['success' => true]);
            }
        }
        
        return $response->withJson(['success' => false]);
    }

    public function uploadBerkasTambahan(Request $request, Response $response, $args) {
        $response = $response->withHeader('Content-Type', 'application/json');
        $cutiId = $args['id'];
        $uploadedFiles = $request->getUploadedFiles();
        
        if (isset($uploadedFiles['berkas_tambahan'])) {
            $uploadedFile = $uploadedFiles['berkas_tambahan'];
            
            if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
                $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
                $fileName = 'berkas_tambahan_' . $cutiId . '.' . $extension;
                $uploadedFile->moveTo(__DIR__ . '/../../public/uploads/berkas_tambahan/' . $fileName);
                
                $this->cutiModel->updateBerkasTambahanPath($cutiId, $fileName);
                
                // Cek apakah sudah ada formulir yang ditandatangani
                $cuti = $this->cutiModel->findById($cutiId);
                if ($cuti['form_signed_employee_path']) {
                    $this->cutiModel->updateStatusMessage($cutiId, 'Menunggu konfirmasi admin');
                } else {
                    $this->cutiModel->updateStatusMessage($cutiId, 'Menunggu upload formulir yang sudah ditandatangani');
                }
                
                $response->getBody()->write(json_encode(['success' => true]));
                return $response;
            }
        }
        
        $response->getBody()->write(json_encode(['success' => false, 'error' => 'File upload failed']));
        return $response;
    }

    public function previewBerkas(Request $request, Response $response, $args) {
        $cutiId = $args['id'];
        $cuti = $this->cutiModel->findById($cutiId);
        
        if (!$cuti || !$cuti['berkas_tambahan_path']) {
            return $response->withStatus(404);
        }
        
        $filePath = __DIR__ . '/../../public/uploads/berkas_tambahan/' . $cuti['berkas_tambahan_path'];
        
        if (!file_exists($filePath)) {
            return $response->withStatus(404);
        }
        
        $fileContent = file_get_contents($filePath);
        $extension = pathinfo($cuti['berkas_tambahan_path'], PATHINFO_EXTENSION);
        
        if (strtolower($extension) === 'pdf') {
            $contentType = 'application/pdf';
        } else {
            $contentType = 'image/' . strtolower($extension);
        }
        
        $response->getBody()->write($fileContent);
        return $response->withHeader('Content-Type', $contentType);
    }

    public function submit(Request $request, Response $response, $args) {
        $cutiId = $args['id'];
        // Status remains 'pending' after submission
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
}