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
<<<<<<< HEAD
        $group->get('/cuti/preview/{id}', [CutiController::class, 'previewPDF']);
        $group->get('/cuti/preview-berkas/{id}', [CutiController::class, 'previewBerkas']);
=======
>>>>>>> parent of 53b33dd (Basic Function)
        $group->post('/cuti/upload-signed/{id}', [CutiController::class, 'uploadSigned']);
        $group->post('/cuti/upload-berkas/{id}', [CutiController::class, 'uploadBerkasTambahan']);
        $group->post('/cuti/submit/{id}', [CutiController::class, 'submit']);
        $group->get('/cuti/history', [CutiController::class, 'history']);
<<<<<<< HEAD
        $group->post('/cuti/upload-signature', [CutiController::class, 'uploadSignature']);
        
        // Digital Signature
        $group->post('/signature/upload', [CutiController::class, 'uploadSignature']);
        $group->post('/cuti/toggle-digital', [CutiController::class, 'toggleDigitalSignature']);
        
        // Export
        $group->get('/cuti/export', [CutiController::class, 'exportReport']);
=======
        
>>>>>>> parent of 53b33dd (Basic Function)
    })->add(AuthMiddleware::class)->add(new RoleMiddleware('user'));

    // Admin routes (Protected)
    $app->group('/admin', function (RouteCollectorProxy $group) {
        $group->get('/dashboard', [DashboardController::class, 'adminDashboard']);
        
        // Cuti Approval
        $group->get('/cuti/pending', [AdminController::class, 'pendingList']);
        $group->get('/cuti/proses', [AdminController::class, 'prosesList']);
<<<<<<< HEAD
        $group->get('/cuti/history', [AdminController::class, 'historyList']);
        $group->get('/cuti/export', [AdminController::class, 'exportHistory']);
        $group->get('/cuti/preview/{id}', [AdminController::class, 'previewPDF']);
        $group->get('/cuti/preview-berkas/{id}', [AdminController::class, 'previewBerkas']);
        $group->get('/cuti/{id}/detail', [AdminController::class, 'getDetail']);
        $group->post('/cuti/{id}/status', [AdminController::class, 'updateStatus']);
        $group->post('/cuti/{id}/upload-atasan', [AdminController::class, 'uploadAtasanSigned']);
=======
        $group->post('/cuti/update-status/{id}', [AdminController::class, 'updateStatus']);
        $group->post('/cuti/upload-atasan/{id}', [AdminController::class, 'uploadAtasanSigned']);
>>>>>>> parent of 53b33dd (Basic Function)
        
        // Employee Management
        $group->get('/employees', [AdminController::class, 'employeeList']);
        $group->post('/employees/create', [AdminController::class, 'createEmployee']);
        
        // Pejabat Management
        $group->get('/pejabat', [AdminController::class, 'pejabatList']);
        $group->post('/pejabat/create', [AdminController::class, 'createPejabat']);
        
    })->add(AuthMiddleware::class)->add(new RoleMiddleware('admin'));
};