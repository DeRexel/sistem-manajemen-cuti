<?php
namespace App\Controllers;

use App\Models\User;
use App\Models\Employee;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class AccountController {
    private $userModel;
    private $employeeModel;

    public function __construct() {
        $this->userModel = new User();
        $this->employeeModel = new Employee();
    }

    private function getTwig(Request $request): Twig {
        return $request->getAttribute('twig');
    }

    public function showSettings(Request $request, Response $response) {
        $user = $this->userModel->findById($_SESSION['user_id']);
        $employee = null;
        
        if ($_SESSION['role'] === 'user' && $_SESSION['employee_id']) {
            $employee = $this->employeeModel->findById($_SESSION['employee_id']);
        }

        return $this->getTwig($request)->render($response, 'user/account-settings.twig', [
            'user' => $user,
            'employee' => $employee,
            'current_role' => $_SESSION['role'],
            'current_user' => $_SESSION['username']
        ]);
    }

    public function updatePassword(Request $request, Response $response) {
        $data = $request->getParsedBody();
        
        // Verify current password
        $user = $this->userModel->findById($_SESSION['user_id']);
        if (!password_verify($data['current_password'], $user['password'])) {
            return $response->withJson(['success' => false, 'message' => 'Password lama salah']);
        }
        
        // Validate new password
        if ($data['new_password'] !== $data['confirm_password']) {
            return $response->withJson(['success' => false, 'message' => 'Konfirmasi password tidak cocok']);
        }
        
        if (strlen($data['new_password']) < 6) {
            return $response->withJson(['success' => false, 'message' => 'Password minimal 6 karakter']);
        }
        
        // Update password
        $hashedPassword = password_hash($data['new_password'], PASSWORD_DEFAULT);
        $this->userModel->updatePassword($_SESSION['user_id'], $hashedPassword);
        
        return $response->withJson(['success' => true, 'message' => 'Password berhasil diubah']);
    }

    public function updateProfile(Request $request, Response $response) {
        $data = $request->getParsedBody();
        
        if ($_SESSION['role'] === 'user' && $_SESSION['employee_id']) {
            $this->employeeModel->updateProfile($_SESSION['employee_id'], [
                'email' => $data['email'],
                'phone' => $data['phone'],
                'alamat' => $data['alamat']
            ]);
        }
        
        return $response->withJson(['success' => true, 'message' => 'Profil berhasil diperbarui']);
    }
}