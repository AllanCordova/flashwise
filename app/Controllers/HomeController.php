<?php

namespace App\Controllers;

use Core\Http\Controllers\Controller;
use Lib\Authentication\Auth;

class HomeController extends Controller
{
    public function index(): void
    {
        $user = Auth::user();
        
        $this->render('home/index', [
            'user' => $user
        ]);
    }
}
