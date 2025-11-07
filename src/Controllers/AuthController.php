<?php
namespace App\Controllers;

use App\Models\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class AuthController {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    private function getTwig(Request $request): Twig {
        return $request->getAttribute('twig');
    }

    public function showLogin(Request $request, Response $response) {
        return $this->getTwig($request)->render($response, 'auth/login.twig');
    }

    public function login(Request $request, Response $response) {
        $data = $request->getParsedBody();
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        $user = $this->userModel->authenticate($username, $password);
        
        if ($user) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['employee_id'] = $user['employee_id'];
            
            $redirectUrl = $user['role'] === 'admin' ? '/admin/dashboard' : '/user/dashboard';
            return $response->withHeader('Location', $redirectUrl)->withStatus(302);
        }
        
        return $this->getTwig($request)->render($response, 'auth/login.twig', [
            'error' => 'Username atau password salah'
        ]);
    }

    public function logout(Request $request, Response $response) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
        return $response->withHeader('Location', '/')->withStatus(302);
    }
}