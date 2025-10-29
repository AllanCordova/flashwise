<?php

namespace App\Controllers;

use App\Models\Deck;
use App\Models\Card;
use Core\Http\Controllers\Controller;
use Core\Http\Request;
use Lib\FlashMessage;

class StudyController extends Controller
{
    public function start(Request $request): void
    {
        $deckId = $request->getParam('id');

        /** @var \App\Models\Deck|null $deck */
        $deck = $this->current_user->decks()->findById($deckId);

        if (!$deck) {
            FlashMessage::danger('Deck não encontrado');
            $this->redirectTo('/decks');
            return;
        }

        // Get cards for study
        $studyCards = $deck->getCardsForStudy();

        if (empty($studyCards)) {
            FlashMessage::info('Não há cards para estudar neste momento');
            $this->redirectTo('/decks/' . $deckId);
            return;
        }

        // Store card IDs in session
        $_SESSION['study_deck_id'] = $deckId;
        $_SESSION['study_cards'] = array_values(array_unique(array_map(fn($card) => $card->id, $studyCards)));
        $_SESSION['study_total_cards'] = count($_SESSION['study_cards']);
        $_SESSION['study_completed_cards'] = [];

        // Redirect to first card
        $this->redirectTo('/study/card');
    }

    public function show(): void
    {
        if (!isset($_SESSION['study_deck_id']) || !isset($_SESSION['study_cards'])) {
            FlashMessage::danger('Sessão de estudo não encontrada');
            $this->redirectTo('/decks');
            return;
        }

        $deckId = $_SESSION['study_deck_id'];
        $cardIds = $_SESSION['study_cards'];
        $totalCards = $_SESSION['study_total_cards'] ?? count($cardIds);
        $completedCards = $_SESSION['study_completed_cards'] ?? [];

        $showingAnswer = $_SESSION['study_showing_answer'] ?? false;

        if (count($completedCards) >= $totalCards) {
            if (count($cardIds) > 0) {
                $unseenCards = array_diff($cardIds, $completedCards);
                if (empty($unseenCards)) {
                    $this->finish();
                    return;
                }
            } else {
                $this->finish();
                return;
            }
        }

        if (count($cardIds) === 0) {
            $this->finish();
            return;
        }

        /** @var \App\Models\Deck|null $deck */
        $deck = $this->current_user->decks()->findById($deckId);

        if (!$deck) {
            FlashMessage::danger('Deck não encontrado');
            $this->redirectTo('/decks');
            return;
        }

        // Get current card (always use index 0 since we remove cards from queue)
        $currentIndex = 0;
        $cardId = $cardIds[$currentIndex];
        $card = Card::findById($cardId);

        if (!$card) {
            FlashMessage::danger('Card não encontrado');
            $this->redirectTo('/decks/' . $deckId);
            return;
        }

        $cardsStudied = count($completedCards);
        $currentCardNumber = $cardsStudied + 1;

        if ($totalCards > 0) {
            $progress = ($currentCardNumber / $totalCards) * 100;
        } else {
            $progress = 0;
        }

        $displayIndex = $cardsStudied;

        $suits = [
            ['symbol' => '♥', 'name' => 'hearts', 'color' => 'red'],
            ['symbol' => '♦', 'name' => 'diamonds', 'color' => 'red'],
            ['symbol' => '♣', 'name' => 'clubs', 'color' => 'black'],
            ['symbol' => '♠', 'name' => 'spades', 'color' => 'black']
        ];
        $numbers = ['A', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K'];

        $randomSuit = $suits[array_rand($suits)];
        $randomNumber = $numbers[array_rand($numbers)];

        $this->render('study/study', [
            'deck' => $deck,
            'card' => $card,
            'currentIndex' => $displayIndex,
            'totalCards' => $totalCards,
            'progress' => round($progress, 1),
            'showingAnswer' => $showingAnswer,
            'suit' => $randomSuit,
            'number' => $randomNumber
        ]);
    }

    public function flip(): void
    {
        if (!isset($_SESSION['study_deck_id']) || !isset($_SESSION['study_cards'])) {
            FlashMessage::danger('Sessão de estudo não encontrada');
            $this->redirectTo('/decks');
            return;
        }

        $_SESSION['study_showing_answer'] = true;
        $this->redirectTo('/study/card');
    }

    public function answer(Request $request): void
    {
        if (!isset($_SESSION['study_deck_id']) || !isset($_SESSION['study_cards'])) {
            FlashMessage::danger('Sessão de estudo não encontrada');
            $this->redirectTo('/decks');
            return;
        }

        $quality = $request->getParam('quality');

        if ($quality === null || !is_numeric($quality)) {
            FlashMessage::danger('Qualidade de resposta inválida');
            $this->redirectTo('/study/card');
            return;
        }

        $quality = (int)$quality;

        if ($quality < 0 || $quality > 3) {
            FlashMessage::danger('Qualidade deve estar entre 0 e 3');
            $this->redirectTo('/study/card');
            return;
        }

        $cardIds = $_SESSION['study_cards'];
        $currentIndex = 0; // Always use index 0 since we remove cards from the front

        if (count($cardIds) === 0) {
            FlashMessage::danger('Não há mais cards para estudar');
            $this->redirectTo('/decks');
            return;
        }

        $cardId = $cardIds[$currentIndex];
        $card = Card::findById($cardId);

        if (!$card) {
            FlashMessage::danger('Card não encontrado');
            $this->redirectTo('/decks');
            return;
        }

        $completedCards = $_SESSION['study_completed_cards'] ?? [];
        $totalCards = $_SESSION['study_total_cards'] ?? count($cardIds);

        $isFirstTime = !in_array($cardId, $completedCards);

        try {
            $card->processResponse($quality);
            $card->save();
        } catch (\Exception $e) {
            FlashMessage::danger('Erro ao processar resposta: ' . $e->getMessage());
            $this->redirectTo('/study/card');
            return;
        }

        if ($quality === 0) {
            if ($isFirstTime) {
                $completedCards[] = $cardId;
                $_SESSION['study_completed_cards'] = $completedCards;
            }

            array_splice($cardIds, $currentIndex, 1);
            $cardIds[] = $cardId;
            $_SESSION['study_cards'] = $cardIds;
            $_SESSION['study_showing_answer'] = false;
        } else {
            if ($isFirstTime) {
                $completedCards[] = $cardId;
                $_SESSION['study_completed_cards'] = $completedCards;
            }

            array_splice($cardIds, $currentIndex, 1);
            $_SESSION['study_cards'] = $cardIds;
            $_SESSION['study_showing_answer'] = false;
        }

        $this->redirectTo('/study/card');
    }

    public function finish(): void
    {
        $deckId = $_SESSION['study_deck_id'] ?? null;

        unset($_SESSION['study_deck_id']);
        unset($_SESSION['study_cards']);
        unset($_SESSION['study_showing_answer']);
        unset($_SESSION['study_total_cards']);
        unset($_SESSION['study_completed_cards']);

        if ($deckId) {
            FlashMessage::success('Parabéns! Você completou a sessão de estudo!');
            $this->redirectTo('/decks/' . $deckId);
        } else {
            $this->redirectTo('/decks');
        }
    }
}
