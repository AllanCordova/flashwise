<?php

namespace Lib\Authentication;

use App\Models\User;

class Auth
{
    public static function login(User $user): void
    {
        $_SESSION['user']['id'] = $user->id;
    }

    public static function user(): ?User
    {
        if (!isset($_SESSION['user']['id'])) {
            return null;
        }

        $id = $_SESSION['user']['id'];
        return User::findById($id);
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']['id']) && self::user() !== null;
    }

    public static function logout(): void
    {
        unset($_SESSION['user']['id']);
    }
}
