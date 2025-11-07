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
            'pejabat_id' => $data['pejabat_id']
        ];
        
        $cutiId = $this->cutiModel->create($cutiData);
        
        return $response->withHeader('Location', '/user/cuti/download/' . $cutiId)->withStatus(302);
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
                
                return $response->withJson(['success' => true]);
            }
        }
        
        return $response->withJson(['success' => false]);
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