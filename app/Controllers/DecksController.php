<?php

namespace App\Controllers;

use App\Models\Deck;
use Codeception\Step\Retry;
use Core\Http\Controllers\Controller;
use Lib\Authentication\Auth;
use Lib\FlashMessage;
use Core\Http\Request;

class DecksController extends Controller
{
    public function index(): void
    {
        $user = Auth::user();
        $decks = Deck::all();

        $this->render('decks/index', [
            'user' => $user,
            'decks' => $decks
        ]);
    }

    public function createview(): void
    {
        $this->render('form/deck');
    }

    public function create(Request $request): void
    {
        $params = $request->getParam('deck');

        $deckData = [
            'name' => $params['name'],
            'description' => $params['description'],
            'path_img' => 'teste',
            'category_id' => null,
        ];

        $deck = new Deck($deckData);

        if ($deck->save()) {
            FlashMessage::success('Deck criado com sucesso');

            $this->redirectTo('/decks');
        } else {
            FlashMessage::danger('Não foi possivel criar seu deck tente novamente!');

            $this->redirectTo('/decks/create');
        }
    }

    public function destroy(Request $request): void
    {
        $id = $request->getParam('id');

        $deck = Deck::findById($id);

        if ($deck->destroy()) {
            FlashMessage::success('Deck excluído com sucesso!');
        } else {
            FlashMessage::danger('Ocorreu um erro ao excluir o deck.');
        }

        $this->redirectTo('/decks');
    }
}
