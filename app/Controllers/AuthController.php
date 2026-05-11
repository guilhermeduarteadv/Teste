<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Session;
use Core\Logger;
use App\Services\AuthService;
use App\Helpers\SecurityHelper;

class AuthController extends Controller
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function showLogin(): void
    {
        if (Session::isLoggedIn()) {
            $this->redirect('/dashboard');
        }
        $expired = isset($_GET['expired']) && $_GET['expired'] === '1';
        $this->render('auth/login', ['expired' => $expired, 'pageTitle' => 'Login'], 'auth');
    }

    public function login(): void
    {
        $this->validateCsrf();

        $email    = trim($this->input('email', ''));
        $password = $_POST['password'] ?? '';
        $ip       = SecurityHelper::getClientIp();

        if (empty($email) || empty($password)) {
            Session::flash('error', 'E-mail e senha são obrigatórios.');
            $this->redirect('/login');
        }

        $result = $this->authService->attempt($email, $password, $ip);

        if ($result['success']) {
            Session::flash('success', 'Bem-vindo ao JurisControl!');
            $this->redirect('/dashboard');
        } else {
            Session::flash('error', $result['message']);
            $this->redirect('/login');
        }
    }

    public function logout(): void
    {
        $user = Session::get('user');
        if ($user) {
            $this->authService->logout((int)$user['id'], $user['email']);
        }
        Session::flash('success', 'Você saiu do sistema com segurança.');
        $this->redirect('/login');
    }

    public function showForgotPassword(): void
    {
        if (Session::isLoggedIn()) {
            $this->redirect('/dashboard');
        }
        $this->render('auth/forgot-password', ['pageTitle' => 'Recuperar Senha'], 'auth');
    }

    public function forgotPassword(): void
    {
        $this->validateCsrf();
        $email = trim($this->input('email', ''));

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Informe um e-mail válido.');
            $this->redirect('/forgot-password');
        }

        $result = $this->authService->generatePasswordResetToken($email);
        // In production: send email with token link
        // For now, log the token (development only)
        if (isset($result['token'])) {
            Logger::info("Password reset token for {$email}: " . $result['token']);
        }

        Session::flash('success', $result['message']);
        $this->redirect('/forgot-password');
    }

    public function showResetPassword(string $token): void
    {
        $this->render('auth/reset-password', ['token' => htmlspecialchars($token), 'pageTitle' => 'Redefinir Senha'], 'auth');
    }

    public function resetPassword(): void
    {
        $this->validateCsrf();
        $token    = $this->input('token', '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['password_confirmation'] ?? '';

        if (empty($token) || empty($password)) {
            Session::flash('error', 'Token e senha são obrigatórios.');
            $this->redirect('/login');
        }

        if ($password !== $confirm) {
            Session::flash('error', 'As senhas não coincidem.');
            $this->redirect('/reset-password/' . $token);
        }

        $result = $this->authService->resetPassword($token, $password);
        if ($result['success']) {
            Session::flash('success', $result['message']);
            $this->redirect('/login');
        } else {
            Session::flash('error', $result['message']);
            $this->redirect('/reset-password/' . $token);
        }
    }
}
