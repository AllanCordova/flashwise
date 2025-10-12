<?php

namespace App\Controllers;

use Core\Http\Controllers\Controller;
use App\Models\User;
use Lib\Authentication\Auth;
use Lib\FlashMessage;
use Core\Http\Request;

class RegisterController extends Controller
{
    public function new(): void
    {

        if (Auth::check()) {
            $this->redirectTo('/');
        }

        $this->render('form/register');
    }

    public function create(Request $request): void
    {
        $params = $request->getParam('user');

        $userData = [
            'name' => $params['name'],
            'email' => $params['email'],
            'password' => $params['password'],
            'password_confirmation' => $params['confirm_password'],
            'role' => 'user',
            'avatar_name' => 'default.png'
        ];

        $user = new User($userData);

        if ($user->save()) {
            FlashMessage::success('Registro completo com sucesso por favor faça login!' . htmlspecialchars($user->name) . '!');

            $this->redirectTo('/login');
        } else {
            FlashMessage::danger('Dados inválidos registre-se novamente!' . htmlspecialchars($user->name) . '!');

            $this->redirectTo('/register');
        }
    }
}
