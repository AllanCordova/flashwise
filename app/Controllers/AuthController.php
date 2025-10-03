<?php

namespace App\Controllers;

use Core\Http\Controllers\Controller;
use App\Models\User;
use Lib\Authentication\Auth;
use Lib\FlashMessage;
use Core\Http\Request;

class AuthController extends Controller
{
    public function new()
    {
        $this->render('form/login');
    }

    public function create(Request $request)
    {
        $params = $request->getParam('user');
        $user = User::findByEmail($params['email']);

        if ($user && $user->authenticate($params['password'])) {
            Auth::login($user);
            FlashMessage::success('Login realizado com sucesso! Bem-vindo(a), ' . htmlspecialchars($user->name) . '!');

            return $this->redirectTo('/');
        } else {
            FlashMessage::danger('E-mail ou senha inválidos. Por favor, tente novamente.');

            return $this->redirectTo('/login');
        }
    }

    public function destroy()
    {
        Auth::logout();

        FlashMessage::success('Você foi desconectado com segurança.');
        return $this->redirectTo('/login');
    }
}
