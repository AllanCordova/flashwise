<?php<?php



namespace App\Controllers;namespace App\Controllers;



use Core\Http\Controllers\Controller;use Core\Http\Controllers\Controller;

use Lib\Authentication\Auth;use Lib\Authentication\Auth;

use Lib\FlashMessage;

class AdminController extends Controller

class AdminController extends Controller{

{    public function dashboard(): void

    public function index()    {

    {        $user = Auth::user();

        if (!Auth::check()) {        

            FlashMessage::danger('Você precisa estar logado para acessar esta página.');        if (!$user || !$user->isAdmin()) {

            return $this->redirectTo('/login');            $this->redirectTo(route('auth.login'));

        }        }



        $user = Auth::user();        $this->render('admin/dashboard', [

                    'user' => $user

        if (!$user->isAdmin()) {        ]);

            FlashMessage::danger('Acesso negado! Apenas administradores podem acessar esta área.');    }

            return $this->redirectTo('/');

        }    private function redirectTo(string $location): void

    {

        $this->render('admin/index', [        header('Location: ' . $location);

            'user' => $user        exit;

        ]);    }

    }}

}
