<?php

namespace App\Controllers;

use Core\Http\Controllers\Controller;
use Core\Http\Request;

class AchievementsController extends Controller
{
    public function index(Request $request): void
    {
        $user = $this->currentUser();
        $achievements = $user->achievements()->all();

        // Retorna JSON apenas se a requisição aceitar JSON (requisições AJAX)
        if ($request->acceptJson()) {
            $this->renderJson('achievements/index', [
                'achievements' => $achievements
            ]);
        } else {
            // Retorna HTML para acesso direto no navegador
            $this->render('achievements/index', [
                'achievements' => $achievements
            ]);
        }
    }
}
