<?php
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

$app = AppFactory::create();

// Add error middleware
$app->addErrorMiddleware(true, true, true);

// Twig setup
$twig = Twig::create(__DIR__ . '/../templates', ['cache' => false]);
$app->add(TwigMiddleware::create($app, $twig));

// Add Twig to request attributes
$app->add(function ($request, $handler) use ($twig) {
    $request = $request->withAttribute('twig', $twig);
    return $handler->handle($request);
});

// Routes
$routes = require __DIR__ . '/../config/routes.php';
$routes($app);

$app->run();