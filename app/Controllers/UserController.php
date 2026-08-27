<?php

namespace App\Controllers;

use App\Audit\AuditLogger;
use App\Core\Auth;
use App\Core\Controller;
use App\Models\User;
use App\Notifications\AdminNotifier;

/**
 * UserController — AdminOnly (enforced by route middleware, not here).
 *
 * Mass-assignment: collectInput() whitelists exactly the fields a form
 * is allowed to submit. role/status are only ever set from this
 * whitelist, never from a raw $_POST merge.
 */
class UserController extends Controller
{
    private User $model;

    public function __construct()
    {
        $this->model = new User();
    }

    public function index(): void
    {
        $this->view('users.index', [
            'pageTitle' => 'User Management',
            'users' => $this->model->all('name ASC'),
            'csrf' => $this->csrfToken(),
        ]);
    }

    public function store(): void
    {
        $this->verifyCsrf();

        $name = trim((string) $this->input('name'));
        $email = trim((string) $this->input('email'));
        $password = (string) $this->input('password');
        $role = $this->input('role', 'cashier');
        $role = in_array($role, ['admin', 'manager', 'cashier'], true) ? $role : 'cashier';

        if ($name === '' || $email === '' || strlen($password) < 8) {
            $this->flash('error', 'Name, email, and a password of at least 8 characters are required.');
            $this->redirect('/users');
        }

        if ($this->model->findByEmail($email)) {
            $this->flash('error', 'That email is already registered.');
            $this->redirect('/users');
        }

        try {
            $userId = $this->model->createUser([
                'name' => $name,
                'email' => $email,
                'phone' => trim((string) $this->input('phone')) ?: null,
                'password' => $password,
                'role' => $role,
                'status' => 'active',
            ]);
        } catch (\PDOException $e) {
            $this->flash('error', 'Could not create user.');
            $this->redirect('/users');
        }

        $newUser = $this->model->find($userId);
        $creator = Auth::user();

        AuditLogger::log(
            'USER_CREATED',
            'users',
            'user',
            $userId,
            null,
            ['name' => $name, 'email' => $email, 'role' => $role]
        );

        // Notification failure must never break user creation — fire and forget.
        (new AdminNotifier())->newUserCreated($newUser, $creator ?? []);

        $this->flash('success', 'User created.');
        $this->redirect('/users');
    }

    public function update(string $id): void
    {
        $this->verifyCsrf();
        $id = (int) $id;

        $target = $this->model->find($id);
        if (!$target) {
            http_response_code(404);
            die('User not found.');
        }

        // No admin — including this one — can strip their OWN admin role
        // or deactivate their own account through this form. That would
        // either lock everyone out or let an admin quietly disable
        // themselves as cover for something else; either way it's a
        // decision that needs a *different* admin to make.
        $isSelf = $id === Auth::id();

        $role = $this->input('role', $target['role']);
        $role = in_array($role, ['admin', 'manager', 'cashier'], true) ? $role : $target['role'];
        $status = $this->input('status', $target['status']) === 'inactive' ? 'inactive' : 'active';

        if ($isSelf && ($role !== $target['role'] || $status !== $target['status'])) {
            $this->flash('error', 'You cannot change your own role or status. Ask another admin.');
            $this->redirect('/users');
        }

        $newPassword = (string) $this->input('password', '');

        $update = [
            'name' => trim((string) $this->input('name', $target['name'])) ?: $target['name'],
            'phone' => trim((string) $this->input('phone', $target['phone'] ?? '')) ?: null,
            'role' => $role,
            'status' => $status,
        ];

        $this->model->update($id, $update);

        if (strlen($newPassword) >= 8) {
            $this->model->updatePassword($id, $newPassword);
            AuditLogger::log('PASSWORD_CHANGED', 'users', 'user', $id, null, null);
        }

        if ($role !== $target['role']) {
            AuditLogger::log(
                'ROLE_CHANGED',
                'users',
                'user',
                $id,
                ['role' => $target['role']],
                ['role' => $role]
            );
            (new AdminNotifier())->roleChanged($this->model->find($id), Auth::user() ?? [], $target['role']);
        } else {
            AuditLogger::log('USER_UPDATED', 'users', 'user', $id, $target, $update);
        }

        $this->flash('success', 'User updated.');
        $this->redirect('/users');
    }
}
