<?php
namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

class RoleMiddleware {
    private $requiredRole;

    public function __construct($requiredRole) {
        $this->requiredRole = $requiredRole;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== $this->requiredRole) {
            $response = new Response();
            return $response->withHeader('Location', '/')->withStatus(302);
        }
        
        return $handler->handle($request);
    }
}