<?php

namespace Tests\Acceptance\cards;

use App\Models\Card;
use App\Models\Deck;
use App\Models\User;
use Tests\Acceptance\BaseAcceptanceCest;
use Tests\Support\AcceptanceTester;

class CardsCest extends BaseAcceptanceCest
{
    private ?User $currentUser = null;
    private ?Deck $currentDeck = null;

    // ------------ setup ------------
    private function createUser(): User
    {
        $user = new User([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'user',
        ]);
        $user->save();

        return $user;
    }

    private function createDeck(): Deck
    {
        $deck = new Deck([
            'name' => 'Test Deck',
            'description' => 'Deck for testing cards',
            'user_id' => $this->currentUser->id
        ]);
        $deck->save();

        return $deck;
    }

    /**
     * @return Card[]
     */
    private function createCards(int $count): array
    {
        $cards = [];
        for ($i = 1; $i <= $count; $i++) {
            $newCard = new Card([
                'front' => "Pergunta $i",
                'back' => "Resposta $i",
                'deck_id' => $this->currentDeck->id,
                'ease_factor' => 2.50,
                'review_interval' => 0,
                'repetitions' => 0,
                'next_review' => null,
                'card_type' => 'new',
                'last_reviewed' => null,
            ]);
            $newCard->save();

            $cards[] = $newCard;
        }

        return $cards;
    }

    // auxiliar functions
    private function submitEmptyFormAndExpectError(AcceptanceTester $I, string $formId): void
    {
        $I->wait(1);
        $I->fillField('card[front]', '');
        $I->fillField('card[back]', '');

        $I->scrollTo($formId);
        $I->wait(0.5);
        $I->submitForm($formId, []);
        $I->wait(1);
        $I->see('não pode ser vazio!');
    }

    // ------------ create ------------

    public function tryToCreateSuccess(AcceptanceTester $I): void
    {
        $this->currentUser = $this->createUser();
        $this->loginHelper->login($this->currentUser->email, 'password123');
        $I->wait(2);

        $this->currentDeck = $this->createDeck();

        $I->amOnPage('/cards/new');

        $I->fillField('card[front]', 'O que é PHP?');
        $I->fillField('card[back]', 'PHP é uma linguagem de programação');

        $I->scrollTo('#card_create');
        $I->submitForm('#card_create', []);

        $I->wait(1);
        $I->see('Card criado com sucesso');
        $I->seeCurrentUrlEquals('/decks?page=1&sort=created_desc');
    }

    public function tryToCreateWithNoDataValidation(AcceptanceTester $I): void
    {
        $this->currentUser = $this->createUser();
        $this->loginHelper->login($this->currentUser->email, 'password123');
        $I->wait(2);

        $this->currentDeck = $this->createDeck();

        $I->amOnPage('/cards/new');
        $this->submitEmptyFormAndExpectError($I, '#card_create');
    }

    public function tryToCreateCardWithoutDeck(AcceptanceTester $I): void
    {
        $this->currentUser = $this->createUser();
        $this->loginHelper->login($this->currentUser->email, 'password123');
        $I->wait(2);

        $I->amOnPage('/cards/new');

        $I->wait(1);
        $I->see('Você precisa criar um deck antes de adicionar cards.');
        $I->seeCurrentUrlEquals('/decks/new');
    }

    // ------------ update ------------
    public function tryToUpdateSuccess(AcceptanceTester $I): void
    {
        $this->currentUser = $this->createUser();
        $this->loginHelper->login($this->currentUser->email, 'password123');
        $I->wait(2);

        $this->currentDeck = $this->createDeck();
        $cards = $this->createCards(1);
        $card = $cards[0];

        $I->amOnPage('/cards/' . $card->id . '/edit');

        $I->fillField('card[front]', 'Pergunta Atualizada');
        $I->fillField('card[back]', 'Resposta Atualizada');

        $I->scrollTo('#card_update');
        $I->submitForm('#card_update', []);

        $I->wait(1);
        $I->see('Card atualizado com sucesso');
        $I->seeCurrentUrlMatches('~^/decks/' . $this->currentDeck->id . '/edit(\?.*)?$~');
    }

    public function tryToUpdateWithNoDataValidation(AcceptanceTester $I): void
    {
        $this->currentUser = $this->createUser();
        $this->loginHelper->login($this->currentUser->email, 'password123');
        $I->wait(2);

        $this->currentDeck = $this->createDeck();
        $cards = $this->createCards(1);
        $card = $cards[0];

        $I->amOnPage('/cards/' . $card->id . '/edit');
        $I->wait(1);

        $I->fillField('card[front]', '');
        $I->fillField('card[back]', '');

        $I->scrollTo('#card_update');
        $I->wait(0.5);
        $I->submitForm('#card_update', []);
        $I->wait(1);
        $I->see('não pode ser vazio!');
    }

    // ------------ delete ------------
    public function tryToDeleteCardSuccessfully(AcceptanceTester $I): void
    {
        $this->currentUser = $this->createUser();
        $this->loginHelper->login($this->currentUser->email, 'password123');
        $I->wait(2);

        $this->currentDeck = $this->createDeck();
        $cards = $this->createCards(3);

        $I->amOnPage('/decks/' . $this->currentDeck->id . '/edit');
        $I->wait(1);

        $I->seeNumberOfElements('.cards-table tbody tr', 3);
        $I->see('Pergunta 1', '.card-front');
        $I->see('Pergunta 2', '.card-front');
        $I->see('Pergunta 3', '.card-front');

        $I->scrollTo('.cards-table');
        $I->wait(0.5);

        $I->executeJS("window.confirm = function(){ return true; }");
        $I->click('.cards-table tbody tr:first-child .btn-delete');

        $I->wait(2);
        $I->see('Card removido com sucesso!');
        $I->seeCurrentUrlMatches('~^/decks/' . $this->currentDeck->id . '/edit(\?.*)?$~');

        $I->wait(0.5);
        $I->seeNumberOfElements('.cards-table tbody tr', 2);
        $I->dontSee('Pergunta 1', '.card-front');
        $I->see('Pergunta 2', '.card-front');
        $I->see('Pergunta 3', '.card-front');
    }

    // ------------ move ------------
    public function tryToMoveCardToDifferentDeck(AcceptanceTester $I): void
    {
        $this->currentUser = $this->createUser();
        $this->loginHelper->login($this->currentUser->email, 'password123');
        $I->wait(2);

        $this->currentDeck = $this->createDeck();

        $secondDeck = new Deck([
            'name' => 'Second Deck',
            'description' => 'Another deck',
            'user_id' => $this->currentUser->id
        ]);
        $secondDeck->save();

        $cards = $this->createCards(1);
        $card = $cards[0];

        $I->amOnPage('/cards/' . $card->id . '/edit');

        $I->selectOption('card[deck_id]', $secondDeck->id);

        $I->scrollTo('#card_update');
        $I->submitForm('#card_update', []);

        $I->wait(1);
        $I->see('Card atualizado com sucesso');
        $I->seeCurrentUrlMatches('~^/decks/' . $this->currentDeck->id . '/edit(\?.*)?$~');
    }
}
