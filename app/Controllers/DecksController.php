<?php

namespace App\Controllers;

use App\Models\Deck;
use Core\Http\Controllers\Controller;
use Lib\FlashMessage;
use Core\Http\Request;

class DecksController extends Controller
{
    public function index(): void
    {
        $perPage = 10;

        $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $userId = $this->currentUser()->id;

        $paginator = Deck::paginate(
            page: $currentPage,
            per_page: $perPage,
            route: 'decks.index',
            conditions: ['user_id' => $userId]
        );

        $this->render('decks/index', ['paginator' => $paginator]);
    }

    public function show(Request $request): void
    {
        $id = $request->getParam('id');

        /** @var \App\Models\Deck|null $deck */
        $deck = $this->current_user->decks()->findById($id);

        if (!$deck) {
            FlashMessage::danger('Deck não encontrado');
            $this->redirectTo('/decks');
            return;
        }

        $newCards = $deck->countNewCards();
        $dueCards = $deck->countDueCards();
        $totalCards = $deck->countTotalCards();

        $this->render('decks/show', [
            'deck' => $deck,
            'newCards' => $newCards,
            'dueCards' => $dueCards,
            'totalCards' => $totalCards
        ]);
    }

    public function edit(Request $request): void
    {
        $id = $request->getParam('id');

        /** @var \App\Models\Deck|null $deck */
        $deck = $this->current_user->decks()->findById($id);

        if (!$deck) {
            FlashMessage::danger('Deck não encontrado');
            $this->redirectTo('/decks');
            return;
        }

        $cards = $deck->cards;

        $this->render('decks/edit', [
            'deck' => $deck,
            'cards' => $cards
        ]);
    }

    public function update(Request $request): void
    {
        $id = $request->getParam('id');
        $params = $request->getParam('deck');

        /** @var \App\Models\Deck|null $deck */
        $deck = $this->current_user->decks()->findById($id);

        if (!$deck) {
            FlashMessage::danger('Deck não encontrado');
            $this->redirectTo('/decks');
            return;
        }

        $deck->name = $params['name'];
        $deck->description = $params['description'];

        if ($deck->save()) {
            FlashMessage::success('Deck atualizado com sucesso');
            $this->redirectTo('/decks');
        } else {
            FlashMessage::danger('Não foi possível atualizar o deck. Verifique os dados!');
            /** @var \App\Models\Card[] $cards */
            $cards = $deck->cards;
            $this->render('decks/edit', compact('deck', 'cards'));
        }
    }

    public function new(): void
    {
        $deck = $this->current_user->decks()->new();

        $this->render('decks/new', compact('deck'));
    }

    public function create(Request $request): void
    {
        $params = $request->getParam('deck');

        $deck = $this->current_user->decks()->new($params);

        if ($deck->save()) {
            FlashMessage::success('Deck criado com sucesso');
            $this->redirectTo('/decks');
        } else {
            FlashMessage::danger('Não foi possível criar seu deck. Verifique os dados!');
            $this->render('decks/new', compact('deck'));
        }
    }

    public function destroy(Request $request): void
    {
        $id = $request->getParam('id');

        /** @var \App\Models\Deck|null $deck */
        $deck = $this->current_user->decks()->findById($id);

        if (!$deck) {
            FlashMessage::danger('Deck não encontrado');
            $this->redirectTo('/decks');
            return;
        }

        if ($deck->destroy()) {
            FlashMessage::success('Deck excluído com sucesso!');
        } else {
            FlashMessage::danger('Ocorreu um erro ao excluir o deck.');
        }

        $this->redirectTo('/decks');
    }
}
