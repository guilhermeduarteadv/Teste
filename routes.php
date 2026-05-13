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
use App\Controllers\TimesheetController;
use App\Controllers\PublicationController;
use App\Controllers\TimelineController;
use App\Controllers\StatsController;
use App\Controllers\TribunalConnectionController;
use App\Controllers\DiagnosticController;
use App\Controllers\BackupController;
use App\Controllers\TemplateController;
use App\Controllers\AdministrativeProcedureController;
use App\Controllers\LegalConsultancyController;
use App\Controllers\OfficeStatsController;
use App\Controllers\MaintenanceController;
use App\Controllers\OfficeModuleController;
use App\Controllers\SystemCheckController;
use App\Controllers\NotificationController;
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
$router->get('/api/dashboard/stats', [DashboardController::class, 'apiStats'], [AuthMiddleware::class]);
$router->get('/api/dashboard/charts', [DashboardController::class, 'apiCharts'], [AuthMiddleware::class]);
$router->get('/api/dashboard/alerts', [DashboardController::class, 'apiAlerts'], [AuthMiddleware::class]);
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
$router->get('/api/clients/search', [CaseController::class, 'searchClients'], [AuthMiddleware::class]);
$router->get('/api/cases/by-client', [CaseController::class, 'searchCasesByClient'], [AuthMiddleware::class]);
$router->post('/cases/store', [CaseController::class, 'store'], [AuthMiddleware::class]);
$router->get('/cases/{id}', [CaseController::class, 'show'], [AuthMiddleware::class]);
$router->get('/cases/{id}/edit', [CaseController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/cases/{id}/update', [CaseController::class, 'update'], [AuthMiddleware::class]);
$router->post('/cases/{id}/delete', [CaseController::class, 'delete'], [AuthMiddleware::class]);
$router->post('/cases/{id}/sync', [CaseController::class, 'syncCnj'], [AuthMiddleware::class]);
$router->post('/cases/{id}/movements/add', [CaseController::class, 'addMovement'], [AuthMiddleware::class]);
$router->post('/cases/{id}/deadlines/add', [CaseController::class, 'addDeadline'], [AuthMiddleware::class]);
$router->post('/cases/{id}/hearings/add', [CaseController::class, 'addHearing'], [AuthMiddleware::class]);
$router->post('/cases/{id}/contacts/add', [CaseController::class, 'addContact'], [AuthMiddleware::class]);
$router->post('/cases/{id}/witnesses/add', [CaseController::class, 'addWitness'], [AuthMiddleware::class]);



// Processos administrativos / Prefeitura
$router->get('/administrative-procedures', [AdministrativeProcedureController::class, 'index'], [AuthMiddleware::class]);
$router->get('/administrative-procedures/create', [AdministrativeProcedureController::class, 'create'], [AuthMiddleware::class]);
$router->post('/administrative-procedures/store', [AdministrativeProcedureController::class, 'store'], [AuthMiddleware::class]);
$router->get('/administrative-procedures/{id}', [AdministrativeProcedureController::class, 'show'], [AuthMiddleware::class]);
$router->get('/administrative-procedures/{id}/edit', [AdministrativeProcedureController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/administrative-procedures/{id}/update', [AdministrativeProcedureController::class, 'update'], [AuthMiddleware::class]);
$router->post('/administrative-procedures/{id}/delete', [AdministrativeProcedureController::class, 'delete'], [AuthMiddleware::class]);

// Consultoria jurídica
$router->get('/consultancies', [LegalConsultancyController::class, 'index'], [AuthMiddleware::class]);
$router->get('/consultancies/create', [LegalConsultancyController::class, 'create'], [AuthMiddleware::class]);
$router->post('/consultancies/store', [LegalConsultancyController::class, 'store'], [AuthMiddleware::class]);
$router->get('/consultancies/{id}', [LegalConsultancyController::class, 'show'], [AuthMiddleware::class]);
$router->get('/consultancies/{id}/edit', [LegalConsultancyController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/consultancies/{id}/update', [LegalConsultancyController::class, 'update'], [AuthMiddleware::class]);
$router->post('/consultancies/{id}/delete', [LegalConsultancyController::class, 'delete'], [AuthMiddleware::class]);

// Estatísticas completas do escritório
$router->get('/stats', [OfficeStatsController::class, 'index'], [AuthMiddleware::class]);
$router->get('/api/office-stats', [OfficeStatsController::class, 'api'], [AuthMiddleware::class]);

// Financial
$router->get('/financial', [FinancialController::class, 'index'], [AuthMiddleware::class]);
$router->get('/financial/create', [FinancialController::class, 'create'], [AuthMiddleware::class]);
$router->post('/financial/store', [FinancialController::class, 'store'], [AuthMiddleware::class]);
$router->get('/financial/{id}/edit', [FinancialController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/financial/{id}/update', [FinancialController::class, 'update'], [AuthMiddleware::class]);
$router->post('/financial/{id}/delete', [FinancialController::class, 'delete'], [AuthMiddleware::class]);
$router->post('/financial/{id}/pay', [FinancialController::class, 'markAsPaid'], [AuthMiddleware::class]);
$router->get('/financial/{id}/receipt', [FinancialController::class, 'receipt'], [AuthMiddleware::class]);

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

// Timesheets
$router->get('/timesheets', [TimesheetController::class, 'index'], [AuthMiddleware::class]);
$router->post('/timesheets/store', [TimesheetController::class, 'store'], [AuthMiddleware::class]);
$router->post('/timesheets/{id}/delete', [TimesheetController::class, 'delete'], [AuthMiddleware::class]);


// Tribunal connections
$router->get('/tribunals', [TribunalConnectionController::class, 'index'], [AuthMiddleware::class]);
$router->post('/tribunals/store', [TribunalConnectionController::class, 'store'], [AuthMiddleware::class]);
$router->post('/tribunals/{id}/test', [TribunalConnectionController::class, 'test'], [AuthMiddleware::class]);
$router->post('/tribunals/{id}/delete', [TribunalConnectionController::class, 'delete'], [AuthMiddleware::class]);

// Publications
$router->get('/publications', [PublicationController::class, 'index'], [AuthMiddleware::class]);
$router->post('/publications/import', [PublicationController::class, 'importManual'], [AuthMiddleware::class]);
$router->post('/publications/read-dje', [PublicationController::class, 'readDje'], [AuthMiddleware::class]);
$router->post('/publications/read-text', [PublicationController::class, 'readText'], [AuthMiddleware::class]);

// Calendar
$router->get('/calendar', [CalendarController::class, 'index'], [AuthMiddleware::class]);
$router->get('/calendar/events', [CalendarController::class, 'events'], [AuthMiddleware::class]);
$router->get('/calendar/debug', [CalendarController::class, 'debug'], [AuthMiddleware::class]);


// Diagnóstico, backups e modelos
$router->get('/diagnostics', [DiagnosticController::class, 'index'], [AuthMiddleware::class]);
$router->get('/backups', [BackupController::class, 'index'], [AuthMiddleware::class]);
$router->post('/backups/create', [BackupController::class, 'create'], [AuthMiddleware::class]);
$router->get('/backups/{id}/download', [BackupController::class, 'download'], [AuthMiddleware::class]);
$router->get('/templates', [TemplateController::class, 'index'], [AuthMiddleware::class]);
$router->post('/templates/store', [TemplateController::class, 'store'], [AuthMiddleware::class]);
$router->post('/templates/{id}/delete', [TemplateController::class, 'delete'], [AuthMiddleware::class]);

// Reports
$router->get('/reports', [ReportController::class, 'index'], [AuthMiddleware::class]);
$router->get('/reports/cases', [ReportController::class, 'cases'], [AuthMiddleware::class]);
$router->get('/reports/financial', [ReportController::class, 'financial'], [AuthMiddleware::class]);
$router->get('/reports/clients', [ReportController::class, 'clients'], [AuthMiddleware::class]);
$router->get('/reports/tasks', [ReportController::class, 'tasks'], [AuthMiddleware::class]);
$router->get('/reports/export/pdf', [ReportController::class, 'exportPdf'], [AuthMiddleware::class]);
$router->get('/reports/export/excel', [ReportController::class, 'exportExcel'], [AuthMiddleware::class]);

// System Check
$router->get('/admin/system-check', [SystemCheckController::class, 'index'], [AuthMiddleware::class]);
$router->post('/admin/system-check/run', [SystemCheckController::class, 'run'], [AuthMiddleware::class]);
$router->post('/admin/system-check/repair', [SystemCheckController::class, 'repair'], [AuthMiddleware::class]);
$router->get('/admin/system-check/export', [SystemCheckController::class, 'export'], [AuthMiddleware::class]);

// Notifications
$router->get('/notifications', [NotificationController::class, 'index'], [AuthMiddleware::class]);
$router->get('/api/notifications/unread', [NotificationController::class, 'unread'], [AuthMiddleware::class]);
$router->post('/notifications/{id}/read', [NotificationController::class, 'markRead'], [AuthMiddleware::class]);
$router->post('/notifications/read-all', [NotificationController::class, 'readAll'], [AuthMiddleware::class]);

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
$router->get('/admin/tribunals', [AdminController::class, 'tribunals'], [AuthMiddleware::class]);
$router->post('/admin/tribunals/update', [AdminController::class, 'updateTribunals'], [AuthMiddleware::class]);
$router->post('/admin/settings/update', [AdminController::class, 'updateSettings'], [AuthMiddleware::class]);

// Portal do cliente
$router->get('/portal', [PortalController::class, 'index']);
$router->get('/portal/login', [PortalController::class, 'showLogin']);
$router->post('/portal/login', [PortalController::class, 'login']);
$router->get('/portal/logout', [PortalController::class, 'logout']);
$router->get('/portal/cases/{id}/timeline', [PortalController::class, 'caseTimeline']);
$router->get('/portal/cases', [PortalController::class, 'cases']);
$router->get('/portal/financial', [PortalController::class, 'financial']);
$router->get('/portal/documents', [PortalController::class, 'documents']);
$router->get('/portal/requests', [PortalController::class, 'clientRequestsPortal']);
$router->post('/portal/documents/upload', [PortalController::class, 'uploadDocument']);
$router->get('/portal/documents/{id}/download', [PortalController::class, 'downloadDocument']);

// API (CNJ sync)
$router->post('/api/cnj/sync', [ApiController::class, 'syncCnj'], [AuthMiddleware::class]);
$router->post('/api/processes/sync-registered', [ApiController::class, 'syncRegisteredProcesses'], [AuthMiddleware::class]);
$router->get('/api/cnj/status', [ApiController::class, 'status'], [AuthMiddleware::class]);
$router->post('/api/deadlines/calculate', [ApiController::class, 'calculateDeadline'], [AuthMiddleware::class]);


// v36 Timeline processual e estatísticas
$router->get('/cases/{id}/timeline', [TimelineController::class, 'caseTimeline'], [AuthMiddleware::class]);
$router->post('/cases/{id}/timeline/rebuild', [TimelineController::class, 'rebuild'], [AuthMiddleware::class]);
$router->post('/timeline/{id}/important', [TimelineController::class, 'markImportant'], [AuthMiddleware::class]);
$router->get('/api/cases/{id}/timeline', [TimelineController::class, 'apiCaseTimeline'], [AuthMiddleware::class]);
$router->get('/api/stats/dashboard', [StatsController::class, 'dashboard'], [AuthMiddleware::class]);
$router->get('/publications', [PublicationController::class, 'index'], [AuthMiddleware::class]);


// V52 - Manutenção e módulos avançados
$router->get('/maintenance/diagnostics', [MaintenanceController::class, 'diagnostics'], [AuthMiddleware::class]);
$router->get('/maintenance/migrations', [MaintenanceController::class, 'migrations'], [AuthMiddleware::class]);
$router->post('/maintenance/migrations/run', [MaintenanceController::class, 'runMigrations'], [AuthMiddleware::class]);
$router->get('/maintenance/error-logs', [MaintenanceController::class, 'errorLogs'], [AuthMiddleware::class]);

$router->get('/contracts', [OfficeModuleController::class, 'contracts'], [AuthMiddleware::class]);
$router->get('/contracts/create', [OfficeModuleController::class, 'contractCreate'], [AuthMiddleware::class]);
$router->post('/contracts/store', [OfficeModuleController::class, 'contractStore'], [AuthMiddleware::class]);

$router->get('/payables', [OfficeModuleController::class, 'payables'], [AuthMiddleware::class]);
$router->post('/payables', [OfficeModuleController::class, 'payableStore'], [AuthMiddleware::class]);

$router->get('/dre', [OfficeModuleController::class, 'dre'], [AuthMiddleware::class]);

$router->get('/leads', [OfficeModuleController::class, 'leads'], [AuthMiddleware::class]);
$router->post('/leads', [OfficeModuleController::class, 'leadStore'], [AuthMiddleware::class]);

$router->get('/checklists', [OfficeModuleController::class, 'checklists'], [AuthMiddleware::class]);
$router->post('/checklists', [OfficeModuleController::class, 'checklistStore'], [AuthMiddleware::class]);

$router->get('/client-requests', [OfficeModuleController::class, 'clientRequests'], [AuthMiddleware::class]);
$router->post('/client-requests', [OfficeModuleController::class, 'clientRequestStore'], [AuthMiddleware::class]);
