<?php

namespace App\Controllers;

use App\Models\Deck;
use Core\Http\Controllers\Controller;
use Lib\Authentication\Auth;
use Lib\FlashMessage;
use Core\Http\Request;

class DecksController extends Controller
{
    // protected string $layout = "decks";

    public function index(): void
    {
        $decks = Deck::all();

        // $this->render('decks/index', [
        //     'decks' => $decks
        // ]);

        $this->render('decks/index', compact("decks"));
    }

    public function show(Request $request): void
    {
        $user = Auth::user();
        $id = $request->getParam('id');

        $deck = Deck::findById($id);

        if (!$deck) {
            FlashMessage::danger('Deck não encontrado');
            $this->redirectTo('/decks');
            return;
        }

        $newCards = $deck->countNewCards();
        $dueCards = $deck->countDueCards();
        $totalCards = $deck->countTotalCards();

        $this->render('decks/show', [
            'user' => $user,
            'deck' => $deck,
            'newCards' => $newCards,
            'dueCards' => $dueCards,
            'totalCards' => $totalCards
        ]);
    }

    public function edit(Request $request): void
    {
        $user = Auth::user();
        $id = $request->getParam('id');

        $deck = Deck::findById($id);

        if (!$deck) {
            FlashMessage::danger('Deck não encontrado');
            $this->redirectTo('/decks');
            return;
        }

        $cards = $deck->cards;

        $this->render('decks/edit', [
            'user' => $user,
            'deck' => $deck,
            'cards' => $cards
        ]);
    }

    public function update(Request $request): void
    {
        $id = $request->getParam('id');
        $params = $request->getParam('deck');

        $deck = Deck::findById($id);

        if (!$deck) {
            FlashMessage::danger('Deck não encontrado');
            $this->redirectTo('/decks');
            return;
        }

        $deck->name = $params['name'];
        $deck->description = $params['description'];

        if ($deck->save()) {
            FlashMessage::success('Deck atualizado com sucesso');
            $this->redirectTo('/decks/' . $id . '/edit');
        } else {
            FlashMessage::danger('Não foi possível atualizar o deck. Verifique os dados!');

            $this->render('/decks/edit', compact('deck'));
        }
    }

    public function new(): void
    {

        $deck = new Deck();

        $this->render('decks/new', compact("deck"));
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

            $this->render('/decks/new', compact("deck"));
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
