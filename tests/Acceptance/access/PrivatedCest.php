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

    public function unauthenticatedUserCannotAccessProtectedRouteDecksIndex(AcceptanceTester $I): void
    {
        $I->amOnPage('/decks');
        $I->seeCurrentUrlEquals('/login');
        $I->see('Faça seu Login');
    }

    public function unauthenticatedUserCannotAccessProtectedRouteDecksNew(AcceptanceTester $I): void
    {
        $I->amOnPage('/decks/new');
        $I->seeCurrentUrlEquals('/login');
        $I->see('Faça seu Login');
    }

    public function unauthenticatedUserCannotAccessProtectedRouteDecksEdit(AcceptanceTester $I): void
    {
        // Criar um deck para testar
        $this->currentUser = $this->createUser();
        $deck = new Deck([
            'name' => 'Test Deck',
            'description' => 'Test Description',
            'user_id' => $this->currentUser->id
        ]);
        $deck->save();

        $I->amOnPage('/decks/' . $deck->id . '/edit');
        $I->seeCurrentUrlEquals('/login');
        $I->see('Faça seu Login');
    }

    public function unauthenticatedUserCannotAccessProtectedRouteDecksShow(AcceptanceTester $I): void
    {
        // Criar um deck para testar
        $this->currentUser = $this->createUser();
        $deck = new Deck([
            'name' => 'Test Deck',
            'description' => 'Test Description',
            'user_id' => $this->currentUser->id
        ]);
        $deck->save();

        $I->amOnPage('/decks/' . $deck->id);
        $I->seeCurrentUrlEquals('/login');
        $I->see('Faça seu Login');
    }

    public function unauthenticatedUserCannotAccessProtectedRouteCardsNew(AcceptanceTester $I): void
    {
        $I->amOnPage('/cards/new');
        $I->seeCurrentUrlEquals('/login');
        $I->see('Faça seu Login');
    }

    public function unauthenticatedUserCannotAccessProtectedRouteCardsEdit(AcceptanceTester $I): void
    {
        // Criar um deck e um card para testar
        $this->currentUser = $this->createUser();
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
        $I->seeCurrentUrlEquals('/login');
        $I->see('Faça seu Login');
    }

    public function unauthenticatedUserCannotAccessProtectedRouteAdmin(AcceptanceTester $I): void
    {
        $I->amOnPage('/admin');
        $I->seeCurrentUrlEquals('/login');
        $I->see('Faça seu Login');
    }

    public function unauthenticatedUserCannotAccessProtectedRouteLogout(AcceptanceTester $I): void
    {
        $I->amOnPage('/logout');
        $I->seeCurrentUrlEquals('/login');
        $I->see('Faça seu Login');
    }
}
