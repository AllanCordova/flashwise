<?php

namespace App\Controllers;

use Core\Http\Controllers\Controller;
use Lib\Authentication\Auth;
use Lib\FlashMessage;

class DecksController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            FlashMessage::danger('Você precisa estar logado para acessar esta página.');
            return $this->redirectTo('/login');
        }

        $user = Auth::user();
        
        $this->render('decks/index', [
            'user' => $user
        ]);
    }
}
