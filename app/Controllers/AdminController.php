<?php

namespace App\Controllers;

use Core\Http\Controllers\Controller;
use Lib\Authentication\Auth;
use Lib\FlashMessage;

class AdminController extends Controller
{
    public function index(): void
    {
        if (!Auth::check()) {
            FlashMessage::danger('Você precisa estar logado para acessar esta página.');
            $this->redirectTo('/login');
            return;
        }

        $user = Auth::user();

        if (!$user || !$user->isAdmin()) {
            FlashMessage::danger('Acesso negado! Apenas administradores podem acessar esta área.');
            $this->redirectTo('/');
            return;
        }

        $this->render('admin/index', [
            'user' => $user,
        ]);
    }
}
