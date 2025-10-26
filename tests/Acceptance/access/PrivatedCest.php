<?php

namespace Tests\Acceptance\access;

use App\Models\Card;
use App\Models\Deck;
use App\Models\User;
use Tests\Acceptance\BaseAcceptanceCest;
use Tests\Support\AcceptanceTester;

class PrivatedCest extends BaseAcceptanceCest
{
    private ?User $currentUser = null;

    private function createUser(): User
    {
        $user = new User([
            'name' => 'Test User',
            'email' => 'user@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin'
        ]);
        $user->save();

        return $user;
    }

    private function doLogin(AcceptanceTester $I): void
    {
        $I->amOnPage('/login');
        $I->fillField('user[email]', 'user@test.com');
        $I->fillField('user[password]', 'password123');
        $I->click('Entrar');
        $I->wait(2);
    }

    public function authenticatedUserCanAccessProtectedRouteDecksIndex(AcceptanceTester $I): void
    {
        $this->currentUser = $this->createUser();
        $this->doLogin($I);

        $I->amOnPage('/decks');
        $I->seeCurrentUrlEquals('/decks');
        $I->waitForText('Meus Decks', 15);
    }

    public function authenticatedUserCanAccessProtectedRouteDecksNew(AcceptanceTester $I): void
    {
        $this->currentUser = $this->createUser();
        $this->doLogin($I);

        $I->amOnPage('/decks/new');
        $I->seeCurrentUrlEquals('/decks/new');
        $I->waitForText('Criar Novo Deck', 15);
    }

    public function authenticatedUserCanAccessProtectedRouteDecksEdit(AcceptanceTester $I): void
    {
        $this->currentUser = $this->createUser();
        $this->doLogin($I);

        // Criar um deck para editar
        $deck = new Deck([
            'name' => 'Test Deck',
            'description' => 'Test Description',
            'user_id' => $this->currentUser->id
        ]);
        $deck->save();

        $I->amOnPage('/decks/' . $deck->id . '/edit');
        $I->seeCurrentUrlEquals('/decks/' . $deck->id . '/edit');
        $I->waitForText('Editar Deck', 15);
    }

    public function authenticatedUserCanAccessProtectedRouteDecksShow(AcceptanceTester $I): void
    {
        $this->currentUser = $this->createUser();
        $this->doLogin($I);

        // Criar um deck para visualizar
        $deck = new Deck([
            'name' => 'Test Deck',
            'description' => 'Test Description',
            'user_id' => $this->currentUser->id
        ]);
        $deck->save();

        $I->amOnPage('/decks/' . $deck->id);
        $I->seeCurrentUrlEquals('/decks/' . $deck->id);
        $I->waitForText('Test Deck', 15);
    }

    public function authenticatedUserCanAccessProtectedRouteCardsNew(AcceptanceTester $I): void
    {
        $this->currentUser = $this->createUser();
        $this->doLogin($I);

        // Criar um deck primeiro (cards precisam de deck)
        $deck = new Deck([
            'name' => 'Test Deck',
            'description' => 'Test Description',
            'user_id' => $this->currentUser->id
        ]);
        $deck->save();

        $I->amOnPage('/cards/new');
        $I->seeCurrentUrlEquals('/cards/new');
        $I->waitForText('Criar Novo Flashcard', 15);
    }

    public function authenticatedUserCanAccessProtectedRouteCardsEdit(AcceptanceTester $I): void
    {
        $this->currentUser = $this->createUser();
        $this->doLogin($I);

        // Criar um deck e um card para editar
        $deck = new Deck([
            'name' => 'Test Deck',
            'description' => 'Test Description',
            'user_id' => $this->currentUser->id
        ]);
        $deck->save();

        $card = new Card([
            'front' => 'Test Question',
            'back' => 'Test Answer',
            'deck_id' => $deck->id,
            'ease_factor' => 2.50,
            'review_interval' => 0,
            'repetitions' => 0,
            'next_review' => null,
            'card_type' => 'new',
            'last_reviewed' => null,
        ]);
        $card->save();

        $I->amOnPage('/cards/' . $card->id . '/edit');
        $I->seeCurrentUrlEquals('/cards/' . $card->id . '/edit');
        $I->waitForText('Editar Flashcard', 15);
    }

    public function authenticatedUserCanAccessProtectedRouteAdmin(AcceptanceTester $I): void
    {
        $this->currentUser = $this->createUser();
        $this->doLogin($I);

        $I->amOnPage('/admin');
        $I->seeCurrentUrlEquals('/admin');
        $I->waitForText('Painel do Administrador', 15);
    }

    public function authenticatedUserIsRedirectedFromGuestOnlyRoutes(AcceptanceTester $I): void
    {
        $this->currentUser = $this->createUser();
        $this->doLogin($I);

        $I->amOnPage('/login');
        $I->seeCurrentUrlEquals('/');
    }
}
