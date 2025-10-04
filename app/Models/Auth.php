<?php

namespace App\Models;

use App\Models\User;

class Auth
{
    public static function attemptLogin(string $email, string $password): bool
    {
        $user = User::findByEmail($email);

        if (!$user) {
            return false;
        }

        if (!$user->authenticate($password)) {
            return false;
        }

        self::startUserSession($user);

        return true;
    }

    protected static function startUserSession(User $user): void
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['user_id'] = $user->id;
    }

    public static function logout(): void
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];

        session_destroy();
    }
}
