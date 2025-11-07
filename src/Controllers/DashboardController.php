<?php
namespace App\Controllers;

use App\Models\User;
use App\Models\Cuti;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class DashboardController {
    private $userModel;
    private $cutiModel;

    public function __construct() {
        $this->userModel = new User();
        $this->cutiModel = new Cuti();
    }

    private function getTwig(Request $request): Twig {
        return $request->getAttribute('twig');
    }

    public function userDashboard(Request $request, Response $response) {
        $user = $this->userModel->getUserWithEmployee($_SESSION['user_id']);
        $cutiList = $this->cutiModel->getByEmployeeId($_SESSION['employee_id']);
        
        $stats = [
            'total' => count($cutiList),
            'pending' => count(array_filter($cutiList, fn($c) => $c['status'] === 'pending')),
            'proses' => count(array_filter($cutiList, fn($c) => $c['status'] === 'proses')),
            'selesai' => count(array_filter($cutiList, fn($c) => $c['status'] === 'selesai'))
        ];

        return $this->getTwig($request)->render($response, 'user/dashboard.twig', [
            'user' => $user,
            'stats' => $stats,
            'recent_cuti' => array_slice($cutiList, 0, 5),
            'current_role' => $_SESSION['role'],
            'current_user' => $_SESSION['username']
        ]);
    }

    public function adminDashboard(Request $request, Response $response) {
        $stats = $this->cutiModel->getStatistics();
        $pendingList = $this->cutiModel->getPendingList();
        $prosesList = $this->cutiModel->getProsesList();

        return $this->getTwig($request)->render($response, 'admin/dashboard.twig', [
            'stats' => $stats,
            'pending_list' => array_slice($pendingList, 0, 5),
            'proses_list' => array_slice($prosesList, 0, 5),
            'current_role' => $_SESSION['role'],
            'current_user' => $_SESSION['username']
        ]);
    }
}