<?php
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\CutiController;
use App\Controllers\AdminController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use Slim\Routing\RouteCollectorProxy;

return function ($app) {
    // Public routes
    $app->get('/', [AuthController::class, 'showLogin'])->setName('login');
    $app->post('/login', [AuthController::class, 'login']);
    $app->get('/logout', [AuthController::class, 'logout']);

    // User routes (Protected)
    $app->group('/user', function (RouteCollectorProxy $group) {
        $group->get('/dashboard', [DashboardController::class, 'userDashboard']);
        
        // Cuti Management
        $group->get('/cuti/create', [CutiController::class, 'showForm']);
        $group->post('/cuti/create', [CutiController::class, 'create']);
        $group->get('/cuti/download/{id}', [CutiController::class, 'downloadPDF']);
        $group->post('/cuti/upload-signed/{id}', [CutiController::class, 'uploadSigned']);
        $group->post('/cuti/submit/{id}', [CutiController::class, 'submit']);
        $group->get('/cuti/history', [CutiController::class, 'history']);
        
    })->add(AuthMiddleware::class)->add(new RoleMiddleware('user'));

    // Admin routes (Protected)
    $app->group('/admin', function (RouteCollectorProxy $group) {
        $group->get('/dashboard', [DashboardController::class, 'adminDashboard']);
        
        // Cuti Approval
        $group->get('/cuti/pending', [AdminController::class, 'pendingList']);
        $group->get('/cuti/proses', [AdminController::class, 'prosesList']);
        $group->post('/cuti/update-status/{id}', [AdminController::class, 'updateStatus']);
        $group->post('/cuti/upload-atasan/{id}', [AdminController::class, 'uploadAtasanSigned']);
        
        // Employee Management
        $group->get('/employees', [AdminController::class, 'employeeList']);
        $group->post('/employees/create', [AdminController::class, 'createEmployee']);
        
        // Pejabat Management
        $group->get('/pejabat', [AdminController::class, 'pejabatList']);
        $group->post('/pejabat/create', [AdminController::class, 'createPejabat']);
        
    })->add(AuthMiddleware::class)->add(new RoleMiddleware('admin'));
};