<?php

namespace App\Services;

use App\Models\User;

class AuthService
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function login(string $email, string $password): bool
    {
        $user = User::where('email', $email)->where('active', 1)->first();

        if ($user && password_verify($password, $user->password_hash)) {
            $_SESSION['user_id'] = $user->id;
            $_SESSION['user_name'] = $user->name;
            $_SESSION['user_role'] = $user->role;
            return true;
        }

        return false;
    }

    public function logout(): void
    {
        session_unset();
        session_destroy();
    }

    public function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public function user(): ?User
    {
        if (!$this->isAuthenticated()) {
            return null;
        }
        return User::find($_SESSION['user_id']);
    }
}
