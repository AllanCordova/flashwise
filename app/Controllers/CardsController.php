<?php

namespace App\Controllers;

use App\Models\Card;
use App\Models\Deck;
use Core\Http\Controllers\Controller;
use Lib\Authentication\Auth;
use Lib\FlashMessage;
use Core\Http\Request;

class CardsController extends Controller
{
    public function createview(): void
    {
        $user = Auth::user();
        $decks = Deck::all();

        // Check if user has at least one deck
        if (empty($decks)) {
            // Create default deck
            $defaultDeck = new Deck([
                'name' => 'Padrão',
                'description' => 'Este é um deck padrão',
                'path_img' => null,
                'category_id' => null,
            ]);

            if ($defaultDeck->save()) {
                $decks = [$defaultDeck];
            }
        }

        $this->render('form/card', [
            'user' => $user,
            'decks' => $decks
        ]);
    }

    public function create(Request $request): void
    {
        $params = $request->getParam('card');

        $cardData = [
            'front' => trim($params['front'] ?? ''),
            'back' => trim($params['back'] ?? ''),
            'deck_id' => $params['deck_id'] ?? null,
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
            $this->redirectTo('/cards/create');
        }
    }

    public function edit(Request $request): void
    {
        $user = Auth::user();
        $id = $request->getParam('id');

        $card = Card::findById($id);

        if (!$card) {
            FlashMessage::danger('Card não encontrado');
            $this->redirectTo('/decks');
            return;
        }

        $decks = Deck::all();

        $this->render('form/card-edit', [
            'user' => $user,
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

        $deckId = $card->deck_id;

        $card->front = trim($params['front'] ?? '');
        $card->back = trim($params['back'] ?? '');
        $card->deck_id = $params['deck_id'] ?? $card->deck_id;

        if ($card->save()) {
            FlashMessage::success('Card atualizado com sucesso');
            $this->redirectTo('/decks/' . $deckId . '/edit');
        } else {
            FlashMessage::danger('Não foi possível atualizar o card. Preencha todos os campos!');
            $this->redirectTo('/cards/' . $id . '/edit');
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

        $deckId = $card->deck_id;

        if ($card->destroy()) {
            FlashMessage::success('Card removido com sucesso!');
        } else {
            FlashMessage::danger('Ocorreu um erro ao remover o card.');
        }

        $this->redirectTo('/decks/' . $deckId . '/edit');
    }
}
