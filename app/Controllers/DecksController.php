<?php

namespace App\Controllers;

use Core\Http\Controllers\Controller;
use Lib\Authentication\Auth;
use Lib\FlashMessage;

class DecksController extends Controller
{
    public function index(): void
    {
        $user = Auth::user();

        $this->render('decks/index', [
            'user' => $user
        ]);
    }
}
