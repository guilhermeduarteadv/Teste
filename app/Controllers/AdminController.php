<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Session;
use Core\Logger;
use App\Models\UserModel;
use App\Models\SettingModel;
use App\Models\SystemLogModel;
use App\Services\SystemLogService;
use App\Services\TribunalPreferenceService;
use App\Services\SchemaGuardService;
use App\Helpers\SecurityHelper;
use App\Helpers\ValidationHelper;

class AdminController extends Controller
{
    private $userModel;
    private $settingModel;
    private $logModel;

    public function __construct()
    {
        $this->userModel    = new UserModel();
        $this->settingModel = new SettingModel();
        $this->logModel     = new SystemLogModel();
    }

    private function requireAdmin(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            Session::flash('error', 'Acesso restrito a administradores.');
            $this->redirect('/dashboard');
        }
    }

    public function index(): void
    {
        $this->requireAdmin();
        $this->redirect('/admin/users');
    }

    public function users(): void
    {
        $this->requireAdmin();
        $page      = max(1, (int)($this->input('page', 1)));
        $paginated = $this->userModel->getAllWithRoles($page, 20);

        $this->render('admin.users', [
            'title'      => 'Usuários - JurisControl',
            'paginated'  => $paginated,
            'csrf_token' => Session::csrfToken(),
            'error'      => Session::getFlash('error'),
            'success'    => Session::getFlash('success'),
        ]);
    }

    public function createUser(): void
    {
        $this->requireAdmin();
        $roles       = $this->userModel->getRoles();
        $permissions = $this->userModel->getAllPermissions();

        $this->render('admin.user_create', [
            'title'       => 'Novo Usuário - JurisControl',
            'roles'       => $roles,
            'permissions' => $permissions,
            'csrf_token'  => Session::csrfToken(),
            'error'       => Session::getFlash('error'),
            'old'         => Session::getFlash('old', []),
        ]);
    }

    public function storeUser(): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $validator = ValidationHelper::make($_POST)
            ->required('name', 'Nome')
            ->required('email', 'E-mail')
            ->email('email', 'E-mail')
            ->required('password', 'Senha')
            ->min('password', 8, 'Senha')
            ->confirmed('password', 'Senha')
            ->required('role_id', 'Perfil');

        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            Session::flash('old', $_POST);
            $this->redirect('/admin/users/create');
            return;
        }

        $email = strtolower(trim($_POST['email']));
        $existing = $this->userModel->findByEmail($email);
        if ($existing) {
            Session::flash('error', 'Já existe um usuário cadastrado com este e-mail.');
            Session::flash('old', $_POST);
            $this->redirect('/admin/users/create');
            return;
        }

        $data = [
            'name'       => $this->input('name'),
            'email'      => $email,
            'password'   => SecurityHelper::hashPassword($_POST['password']),
            'role_id'    => (int)$_POST['role_id'],
            'cargo'      => $this->input('cargo'),
            'oab_number' => $this->input('oab_number'),
            'oab_state'  => $this->input('oab_state'),
            'phone'      => !empty($_POST['phone']) ? preg_replace('/\D/', '', $_POST['phone']) : null,
            'status'     => $this->input('status', 'active'),
        ];

        try {
            $id = $this->userModel->insert($data);

            // Set permissions
            $selectedPerms = $_POST['permissions'] ?? [];
            if (!empty($selectedPerms)) {
                $this->userModel->setPermissions($id, $selectedPerms);
            }

            SystemLogService::create('users', 'user', $id, "Usuário criado: {$data['name']}");
            Session::flash('success', 'Usuário criado com sucesso!');
            $this->redirect('/admin/users');
        } catch (\Exception $e) {
            Logger::error('User creation failed: ' . $e->getMessage());
            Session::flash('error', 'Erro ao criar usuário.');
            Session::flash('old', $_POST);
            $this->redirect('/admin/users/create');
        }
    }

    public function editUser(string $id): void
    {
        $this->requireAdmin();
        $user = $this->userModel->findByIdWithRole((int)$id);
        if (!$user) {
            Session::flash('error', 'Usuário não encontrado.');
            $this->redirect('/admin/users');
            return;
        }

        $roles           = $this->userModel->getRoles();
        $allPermissions  = $this->userModel->getAllPermissions();
        $userPermissions = $this->userModel->getUserPermissions((int)$id);

        $this->render('admin.user_edit', [
            'title'          => 'Editar Usuário - JurisControl',
            'editUser'       => $user,
            'roles'          => $roles,
            'allPermissions' => $allPermissions,
            'userPermissions'=> $userPermissions,
            'csrf_token'     => Session::csrfToken(),
            'error'          => Session::getFlash('error'),
            'success'        => Session::getFlash('success'),
        ]);
    }

    public function updateUser(string $id): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $user = $this->userModel->findById((int)$id);
        if (!$user) {
            Session::flash('error', 'Usuário não encontrado.');
            $this->redirect('/admin/users');
            return;
        }

        $validator = ValidationHelper::make($_POST)
            ->required('name', 'Nome')
            ->required('email', 'E-mail')
            ->email('email', 'E-mail');

        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            $this->redirect("/admin/users/{$id}/edit");
            return;
        }

        $data = [
            'name'       => $this->input('name'),
            'email'      => strtolower(trim($_POST['email'])),
            'role_id'    => (int)$_POST['role_id'],
            'cargo'      => $this->input('cargo'),
            'oab_number' => $this->input('oab_number'),
            'oab_state'  => $this->input('oab_state'),
            'phone'      => !empty($_POST['phone']) ? preg_replace('/\D/', '', $_POST['phone']) : null,
            'status'     => $this->input('status', 'active'),
        ];

        // Update password if provided
        if (!empty($_POST['password'])) {
            if (strlen($_POST['password']) < 8) {
                Session::flash('error', 'A nova senha deve ter no mínimo 8 caracteres.');
                $this->redirect("/admin/users/{$id}/edit");
                return;
            }
            $data['password'] = SecurityHelper::hashPassword($_POST['password']);
        }

        try {
            $this->userModel->update((int)$id, $data);

            // Update permissions
            $selectedPerms = $_POST['permissions'] ?? [];
            $this->userModel->setPermissions((int)$id, $selectedPerms);

            SystemLogService::update('users', 'user', (int)$id, $user, $data);
            Session::flash('success', 'Usuário atualizado com sucesso!');
            $this->redirect("/admin/users/{$id}/edit");
        } catch (\Exception $e) {
            Logger::error('User update failed: ' . $e->getMessage());
            Session::flash('error', 'Erro ao atualizar usuário.');
            $this->redirect("/admin/users/{$id}/edit");
        }
    }

    public function deleteUser(string $id): void
    {
        $this->requireAdmin();
        $wantsJson = (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
            || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        try {
            $this->validateCsrf();

            $userId = (int)$id;
            $currentUser = Session::get('user');

            if ($userId <= 0) {
                throw new \RuntimeException('Usuário inválido.');
            }

            if ($currentUser && $userId === (int)$currentUser['id']) {
                throw new \RuntimeException('Você não pode excluir sua própria conta.');
            }

            $user = $this->userModel->findById($userId);
            if (!$user) {
                throw new \RuntimeException('Usuário não encontrado ou já excluído.');
            }

            $deleted = $this->userModel->softDelete($userId);
            if (!$deleted) {
                throw new \RuntimeException('Não foi possível excluir o usuário.');
            }

            SystemLogService::delete('users', 'user', $userId, "Usuário excluído: {$user['name']}");

            if ($wantsJson) {
                $this->json(['success' => true, 'message' => 'Usuário excluído com sucesso.']);
            }

            Session::flash('success', 'Usuário excluído com sucesso.');
            $this->redirect('/admin/users');

        } catch (\Throwable $e) {
            Logger::error('User deletion failed: ' . $e->getMessage());

            if ($wantsJson) {
                $this->json(['success' => false, 'message' => $e->getMessage()], 400);
            }

            Session::flash('error', $e->getMessage());
            $this->redirect('/admin/users');
        }
    }

    public function logs(): void
    {
        $this->requireAdmin();
        $page    = max(1, (int)($this->input('page', 1)));
        $filters = [
            'module'  => $this->input('module', ''),
            'user_id' => $this->input('user_id', ''),
        ];
        $filters = array_filter($filters, function($v) { return $v !== '' && $v !== null; });

        $paginated = $this->logModel->getPaginated($page, 50, $filters);
        $users     = $this->userModel->findAll(['status' => 'active'], 'name ASC');

        $this->render('admin.logs', [
            'title'      => 'Logs do Sistema - JurisControl',
            'paginated'  => $paginated,
            'users'      => $users,
            'filters'    => $filters,
            'csrf_token' => Session::csrfToken(),
        ]);
    }


    public function tribunals(): void
    {
        $this->requireAdmin();
        (new SchemaGuardService())->ensureV49Schema();
        $service = new TribunalPreferenceService();

        $this->render('admin/tribunals', [
            'title' => 'Tribunais - Administração',
            'knownTribunals' => $service->allKnown(),
            'enabledTribunals' => $service->enabled(),
            'csrf_token' => Session::csrfToken(),
        ]);
    }

    public function updateTribunals(): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $codes = $_POST['tribunais'] ?? [];
        (new TribunalPreferenceService())->save($codes);

        Session::flash('success', 'Tribunais atualizados com sucesso.');
        $this->redirect('/admin/tribunals');
    }

    public function settings(): void
    {
        $this->requireAdmin();
        $settings = $this->settingModel->getAll();

        $this->render('admin.settings', [
            'title'      => 'Configurações - JurisControl',
            'settings'   => $settings,
            'csrf_token' => Session::csrfToken(),
            'error'      => Session::getFlash('error'),
            'success'    => Session::getFlash('success'),
        ]);
    }

    public function updateSettings(): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $allowed = [
            'office_name', 'office_oab', 'office_oab_state', 'office_email',
            'office_phone', 'office_address', 'cnj_sync_enabled', 'cnj_sync_interval',
            'session_timeout', 'max_login_attempts', 'login_block_minutes', 'theme_color',
        ];

        try {
            foreach ($allowed as $key) {
                if (isset($_POST[$key])) {
                    $this->settingModel->set($key, trim($_POST[$key]));
                }
            }

            // Handle logo upload
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['logo'];
                $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'svg'])) {
                    $storagePath = ROOT_PATH . '/storage';
                    $logoName = 'logo.' . $ext;
                    $destPath = $storagePath . '/' . $logoName;
                    if (move_uploaded_file($file['tmp_name'], $destPath)) {
                        $this->settingModel->set('logo_path', '/storage/' . $logoName);
                    }
                }
            }

            SystemLogService::log('update_settings', 'settings', 'Configurações do sistema atualizadas');
            Session::flash('success', 'Configurações salvas com sucesso!');
        } catch (\Exception $e) {
            Logger::error('Settings update failed: ' . $e->getMessage());
            Session::flash('error', 'Erro ao salvar configurações.');
        }

        $this->redirect('/admin/settings');
    }
}
