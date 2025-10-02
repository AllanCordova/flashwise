<?php

namespace App\Controllers;

use Core\Http\Controllers\Controller;

class AuthController extends Controller
{
    public function index(): void
    {
        $this->render('form/login');
    }

    public function login(): void
    {
        $email = $_POST['email'];
        echo "Tentando logar com o email: " . htmlspecialchars($email);
    }
}
