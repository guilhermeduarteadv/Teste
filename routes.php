<?php
use Core\Router;
use App\Controllers\InstallController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\ClientController;
use App\Controllers\CaseController;
use App\Controllers\FinancialController;
use App\Controllers\DocumentController;
use App\Controllers\TaskController;
use App\Controllers\CalendarController;
use App\Controllers\ReportController;
use App\Controllers\AdminController;
use App\Controllers\PortalController;
use App\Controllers\ApiController;
use App\Middleware\AuthMiddleware;
use App\Middleware\PermissionMiddleware;

// Install routes
$router->get('/install', [InstallController::class, 'index']);
$router->post('/install/run', [InstallController::class, 'run']);
$router->post('/install/test-db', [InstallController::class, 'testDb']);

// Auth routes
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);
$router->get('/forgot-password', [AuthController::class, 'showForgotPassword']);
$router->post('/forgot-password', [AuthController::class, 'forgotPassword']);
$router->get('/reset-password/{token}', [AuthController::class, 'showResetPassword']);
$router->post('/reset-password', [AuthController::class, 'resetPassword']);

// Dashboard
$router->get('/', [DashboardController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard', [DashboardController::class, 'index'], [AuthMiddleware::class]);

// Clients
$router->get('/clients', [ClientController::class, 'index'], [AuthMiddleware::class]);
$router->get('/clients/create', [ClientController::class, 'create'], [AuthMiddleware::class]);
$router->post('/clients/store', [ClientController::class, 'store'], [AuthMiddleware::class]);
$router->get('/clients/{id}', [ClientController::class, 'show'], [AuthMiddleware::class]);
$router->get('/clients/{id}/edit', [ClientController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/clients/{id}/update', [ClientController::class, 'update'], [AuthMiddleware::class]);
$router->post('/clients/{id}/delete', [ClientController::class, 'delete'], [AuthMiddleware::class]);
$router->get('/clients/{id}/portal-access', [ClientController::class, 'togglePortalAccess'], [AuthMiddleware::class]);
$router->get('/api/cep/{cep}', [ClientController::class, 'searchCep']);

// Cases
$router->get('/cases', [CaseController::class, 'index'], [AuthMiddleware::class]);
$router->get('/cases/create', [CaseController::class, 'create'], [AuthMiddleware::class]);
$router->post('/cases/store', [CaseController::class, 'store'], [AuthMiddleware::class]);
$router->get('/cases/{id}', [CaseController::class, 'show'], [AuthMiddleware::class]);
$router->get('/cases/{id}/edit', [CaseController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/cases/{id}/update', [CaseController::class, 'update'], [AuthMiddleware::class]);
$router->post('/cases/{id}/delete', [CaseController::class, 'delete'], [AuthMiddleware::class]);
$router->post('/cases/{id}/sync', [CaseController::class, 'syncCnj'], [AuthMiddleware::class]);
$router->post('/cases/{id}/movements/add', [CaseController::class, 'addMovement'], [AuthMiddleware::class]);
$router->post('/cases/{id}/deadlines/add', [CaseController::class, 'addDeadline'], [AuthMiddleware::class]);
$router->post('/cases/{id}/hearings/add', [CaseController::class, 'addHearing'], [AuthMiddleware::class]);

// Financial
$router->get('/financial', [FinancialController::class, 'index'], [AuthMiddleware::class]);
$router->get('/financial/create', [FinancialController::class, 'create'], [AuthMiddleware::class]);
$router->post('/financial/store', [FinancialController::class, 'store'], [AuthMiddleware::class]);
$router->get('/financial/{id}/edit', [FinancialController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/financial/{id}/update', [FinancialController::class, 'update'], [AuthMiddleware::class]);
$router->post('/financial/{id}/delete', [FinancialController::class, 'delete'], [AuthMiddleware::class]);
$router->post('/financial/{id}/pay', [FinancialController::class, 'markAsPaid'], [AuthMiddleware::class]);

// Documents
$router->get('/documents', [DocumentController::class, 'index'], [AuthMiddleware::class]);
$router->post('/documents/upload', [DocumentController::class, 'upload'], [AuthMiddleware::class]);
$router->get('/documents/{id}/download', [DocumentController::class, 'download'], [AuthMiddleware::class]);
$router->get('/documents/{id}/preview', [DocumentController::class, 'preview'], [AuthMiddleware::class]);
$router->post('/documents/{id}/delete', [DocumentController::class, 'delete'], [AuthMiddleware::class]);

// Tasks
$router->get('/tasks', [TaskController::class, 'index'], [AuthMiddleware::class]);
$router->get('/tasks/create', [TaskController::class, 'create'], [AuthMiddleware::class]);
$router->post('/tasks/store', [TaskController::class, 'store'], [AuthMiddleware::class]);
$router->get('/tasks/{id}/edit', [TaskController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/tasks/{id}/update', [TaskController::class, 'update'], [AuthMiddleware::class]);
$router->post('/tasks/{id}/delete', [TaskController::class, 'delete'], [AuthMiddleware::class]);
$router->post('/tasks/{id}/complete', [TaskController::class, 'complete'], [AuthMiddleware::class]);

// Calendar
$router->get('/calendar', [CalendarController::class, 'index'], [AuthMiddleware::class]);
$router->get('/calendar/events', [CalendarController::class, 'events'], [AuthMiddleware::class]);

// Reports
$router->get('/reports', [ReportController::class, 'index'], [AuthMiddleware::class]);
$router->get('/reports/cases', [ReportController::class, 'cases'], [AuthMiddleware::class]);
$router->get('/reports/financial', [ReportController::class, 'financial'], [AuthMiddleware::class]);
$router->get('/reports/clients', [ReportController::class, 'clients'], [AuthMiddleware::class]);
$router->get('/reports/tasks', [ReportController::class, 'tasks'], [AuthMiddleware::class]);
$router->get('/reports/export/pdf', [ReportController::class, 'exportPdf'], [AuthMiddleware::class]);
$router->get('/reports/export/excel', [ReportController::class, 'exportExcel'], [AuthMiddleware::class]);

// Admin
$router->get('/admin', [AdminController::class, 'index'], [AuthMiddleware::class]);
$router->get('/admin/users', [AdminController::class, 'users'], [AuthMiddleware::class]);
$router->get('/admin/users/create', [AdminController::class, 'createUser'], [AuthMiddleware::class]);
$router->post('/admin/users/store', [AdminController::class, 'storeUser'], [AuthMiddleware::class]);
$router->get('/admin/users/{id}/edit', [AdminController::class, 'editUser'], [AuthMiddleware::class]);
$router->post('/admin/users/{id}/update', [AdminController::class, 'updateUser'], [AuthMiddleware::class]);
$router->post('/admin/users/{id}/delete', [AdminController::class, 'deleteUser'], [AuthMiddleware::class]);
$router->get('/admin/logs', [AdminController::class, 'logs'], [AuthMiddleware::class]);
$router->get('/admin/settings', [AdminController::class, 'settings'], [AuthMiddleware::class]);
$router->post('/admin/settings/update', [AdminController::class, 'updateSettings'], [AuthMiddleware::class]);

// Portal do cliente
$router->get('/portal', [PortalController::class, 'index']);
$router->get('/portal/login', [PortalController::class, 'showLogin']);
$router->post('/portal/login', [PortalController::class, 'login']);
$router->get('/portal/logout', [PortalController::class, 'logout']);
$router->get('/portal/cases', [PortalController::class, 'cases']);
$router->get('/portal/financial', [PortalController::class, 'financial']);
$router->get('/portal/documents', [PortalController::class, 'documents']);
$router->get('/portal/documents/{id}/download', [PortalController::class, 'downloadDocument']);

// API (CNJ sync)
$router->post('/api/cnj/sync', [ApiController::class, 'syncCnj'], [AuthMiddleware::class]);
$router->get('/api/cnj/status', [ApiController::class, 'status'], [AuthMiddleware::class]);
$router->post('/api/deadlines/calculate', [ApiController::class, 'calculateDeadline'], [AuthMiddleware::class]);
