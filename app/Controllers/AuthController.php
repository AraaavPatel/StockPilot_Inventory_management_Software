<?php

namespace App\Controllers;

use App\Audit\AuditLogger;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\LoginThrottle;
use App\Models\User;
use App\Notifications\AdminNotifier;

class AuthController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect('/dashboard');
        }
        $this->viewOnly('auth.login', ['csrf' => $this->csrfToken()]);
    }

    public function login(): void
    {
        $this->verifyCsrf();

        $email = strtolower(trim((string) $this->input('email')));
        $password = (string) $this->input('password');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        // Same generic message whether the account is locked, the email
        // doesn't exist, or the password is wrong — never reveal which.
        if (LoginThrottle::isLocked($email, $ip)) {
            AuditLogger::log('LOGIN_FAILED', 'auth', 'user', null, null, ['email' => $email, 'reason' => 'locked_out']);
            $this->flash('error', 'Too many failed attempts. Please wait a few minutes and try again.');
            $this->redirect('/login');
        }

        $user = $this->userModel->findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            LoginThrottle::recordFailure($email, $ip);
            AuditLogger::log('LOGIN_FAILED', 'auth', 'user', $user['id'] ?? null, null, ['email' => $email]);
            if (LoginThrottle::isLocked($email, $ip)) {
                (new AdminNotifier())->suspiciousLogin($email, $ip, 'Account locked after repeated failed logins.');
            }
            $this->flash('error', 'Invalid email or password.');
            $this->redirect('/login');
        }

        if ($user['status'] !== 'active') {
            LoginThrottle::recordFailure($email, $ip);
            AuditLogger::log('LOGIN_FAILED', 'auth', 'user', $user['id'], null, ['email' => $email, 'reason' => 'inactive_account']);
            $this->flash('error', 'This account has been deactivated. Contact an admin.');
            $this->redirect('/login');
        }

        LoginThrottle::recordSuccess($email, $ip);
        Auth::login($user);
        $this->userModel->touchLastLogin($user['id']);
        AuditLogger::log('LOGIN_SUCCESS', 'auth', 'user', $user['id']);

        $intended = $_SESSION['intended_url'] ?? null;
        unset($_SESSION['intended_url']);

        header('Location: ' . ($intended ?: base_url('/dashboard')));
        exit;
    }

    public function logout(): void
    {
        AuditLogger::log('LOGOUT', 'auth', 'user', Auth::id());
        Auth::logout();
        header('Location: ' . base_url('/login'));
        exit;
    }

}
