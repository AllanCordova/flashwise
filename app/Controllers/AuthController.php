<?php

namespace App\Controllers;

use Core\Http\Controllers\Controller;
use App\Models\User;
use Lib\Authentication\Auth;
use Lib\FlashMessage;
use Core\Http\Request;

class AuthController extends Controller
{
    public function new(): void
    {

        if (Auth::check()) {
            $this->redirectTo('/');
        }

        $user = new User();

        $this->render('auth/login', compact('user'));
    }

    public function create(Request $request): void
    {
        $params = $request->getParam('user');
        $user = User::findByEmail($params['email']);

        if ($user && $user->authenticate($params['password'])) {
            Auth::login($user);
            FlashMessage::success('Login realizado com sucesso! Bem-vindo(a), ' . htmlspecialchars($user->name) . '!');

            $this->redirectTo('/');
        } else {
            FlashMessage::danger('E-mail ou senha inválidos. Por favor, tente novamente.');

            $this->render('/auth/login', compact('user'));
        }
    }

    public function destroy(): void
    {
        Auth::logout();

        FlashMessage::success('Você foi desconectado com segurança.');
        $this->redirectTo('/login');
    }
}
