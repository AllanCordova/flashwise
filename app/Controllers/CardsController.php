<?php

namespace App\Controllers;

use App\Models\Card;
use Core\Http\Controllers\Controller;
use Lib\FlashMessage;
use Core\Http\Request;

class CardsController extends Controller
{
    public function new(): void
    {
        $decks = $this->current_user->decks()->all();
        $card = new Card();

        // Check if user has at least one deck
        if (empty($decks)) {
            FlashMessage::danger('Você precisa criar um deck antes de adicionar cards.');
            $this->redirectTo('/decks/new');
            return;
        }

        $this->render('cards/new', compact('decks', 'card'));
    }

    public function create(Request $request): void
    {
        $params = $request->getParam('card');
        $decks = $this->current_user->decks()->all();

        // Verify deck belongs to user
        $deckId = $params['deck_id'] ?? null;
        $deck = $this->current_user->decks()->findById($deckId);

        if (!$deck) {
            FlashMessage::danger('Deck inválido ou não pertence a você.');
            $card = new Card($params);
            $this->render('cards/new', compact('card', 'decks'));
            return;
        }

        $cardData = [
            'front' => trim($params['front'] ?? ''),
            'back' => trim($params['back'] ?? ''),
            'deck_id' => $deckId,
            'ease_factor' => 2.50,
            'review_interval' => 0,
            'repetitions' => 0,
            'next_review' => null,
            'card_type' => 'new',
            'last_reviewed' => null,
        ];

        $card = new Card($cardData);

        if ($card->save()) {
            FlashMessage::success('Card criado com sucesso');
            $this->redirectTo('/decks');
        } else {
            FlashMessage::danger('Não foi possível criar o card. Preencha todos os campos!');
            $this->render('cards/new', compact('card', 'decks'));
        }
    }

    public function edit(Request $request): void
    {
        $id = $request->getParam('id');

        $card = Card::findById($id);

        if (!$card) {
            FlashMessage::danger('Card não encontrado');
            $this->redirectTo('/decks');
            return;
        }

        // Verify card belongs to user through deck
        $deck = $this->current_user->decks()->findById($card->deck_id);

        if (!$deck) {
            FlashMessage::danger('Você não tem permissão para editar este card.');
            $this->redirectTo('/decks');
            return;
        }

        $decks = $this->current_user->decks()->all();

        $this->render('cards/edit', [
            'card' => $card,
            'decks' => $decks
        ]);
    }

    public function update(Request $request): void
    {
        $id = $request->getParam('id');
        $params = $request->getParam('card');

        $card = Card::findById($id);

        if (!$card) {
            FlashMessage::danger('Card não encontrado');
            $this->redirectTo('/decks');
            return;
        }

        // Verify card belongs to user through deck
        $oldDeck = $this->current_user->decks()->findById($card->deck_id);

        if (!$oldDeck) {
            FlashMessage::danger('Você não tem permissão para editar este card.');
            $this->redirectTo('/decks');
            return;
        }

        $deckId = $card->deck_id;
        $decks = $this->current_user->decks()->all();

        // Verify new deck belongs to user
        $newDeckId = $params['deck_id'] ?? $card->deck_id;
        $newDeck = $this->current_user->decks()->findById($newDeckId);

        if (!$newDeck) {
            FlashMessage::danger('Deck de destino inválido ou não pertence a você.');
            $this->render('cards/edit', compact('card', 'decks'));
            return;
        }

        $card->front = trim($params['front'] ?? '');
        $card->back = trim($params['back'] ?? '');
        $card->deck_id = $newDeckId;

        if ($card->save()) {
            FlashMessage::success('Card atualizado com sucesso');
            $this->redirectTo('/decks/' . $deckId . '/edit');
        } else {
            FlashMessage::danger('Não foi possível atualizar o card. Preencha todos os campos!');
            $this->render('cards/edit', compact('card', 'decks'));
        }
    }

    public function destroy(Request $request): void
    {
        $id = $request->getParam('id');

        $card = Card::findById($id);

        if (!$card) {
            FlashMessage::danger('Card não encontrado');
            $this->redirectTo('/decks');
            return;
        }

        // Verify card belongs to user through deck
        $deck = $this->current_user->decks()->findById($card->deck_id);

        if (!$deck) {
            FlashMessage::danger('Você não tem permissão para excluir este card.');
            $this->redirectTo('/decks');
            return;
        }

        $deckId = $card->deck_id;

        if ($card->destroy()) {
            FlashMessage::success('Card removido com sucesso!');
        } else {
            FlashMessage::danger('Ocorreu um erro ao remover o card.');
        }

        $this->redirectTo('/decks/' . $deckId . '/edit');
    }
}
