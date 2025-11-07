<?php
namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

class AuthMiddleware {
    public function __invoke(Request $request, RequestHandler $handler): Response {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            $response = new Response();
            return $response->withHeader('Location', '/')->withStatus(302);
        }
        
        return $handler->handle($request);
    }
}