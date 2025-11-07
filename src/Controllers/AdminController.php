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
        $cutiId = $args['id'];
        $data = $request->getParsedBody();
        $newStatus = $data['status'];
        
        $this->cutiModel->updateStatus($cutiId, $newStatus, $_SESSION['user_id']);
        
        // If approved, update employee's leave quota
        if ($newStatus === 'selesai' && isset($data['persetujuan_atasan']) && $data['persetujuan_atasan'] === 'disetujui') {
            $cuti = $this->cutiModel->findById($cutiId);
            $this->employeeModel->updateSisaCuti($cuti['employee_id'], $cuti['jenis_cuti'], $cuti['lama_hari']);
        }
        
        // Update approval decision
        if (isset($data['persetujuan_atasan'])) {
            $this->cutiModel->updatePersetujuanAtasan($cutiId, $data['persetujuan_atasan'], $data['catatan_atasan'] ?? null);
        }
        
        return $response->withJson(['success' => true]);
    }

    public function uploadAtasanSigned(Request $request, Response $response, $args) {
        $cutiId = $args['id'];
        $uploadedFiles = $request->getUploadedFiles();
        
        if (isset($uploadedFiles['signed_form'])) {
            $uploadedFile = $uploadedFiles['signed_form'];
            
            if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
                $fileName = 'signed_atasan_' . $cutiId . '.pdf';
                $uploadedFile->moveTo(__DIR__ . '/../../public/uploads/signed_forms/' . $fileName);
                
                $this->cutiModel->updateSignedAtasanPath($cutiId, $fileName);
                
                return $response->withJson(['success' => true]);
            }
        }
        
        return $response->withJson(['success' => false]);
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
}