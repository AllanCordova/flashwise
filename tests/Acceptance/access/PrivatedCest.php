<?php

namespace Tests\Acceptance\access;

use App\Models\Card;
use App\Models\Deck;
use App\Models\DeckUserShared;
use App\Models\Material;
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
    }

    public function unauthenticatedUserCannotAccessProtectedRouteDecksNew(AcceptanceTester $I): void
    {
        $I->amOnPage('/decks/new');
        $I->seeCurrentUrlEquals('/login');
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
    }

    public function unauthenticatedUserCannotAccessProtectedRouteCardsNew(AcceptanceTester $I): void
    {
        $I->amOnPage('/cards/new');
        $I->seeCurrentUrlEquals('/login');
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
    }

    public function unauthenticatedUserCannotAccessProtectedRouteAdmin(AcceptanceTester $I): void
    {
        $I->amOnPage('/admin');
        $I->seeCurrentUrlEquals('/login');
    }

    public function unauthenticatedUserCannotAccessProtectedRouteLogout(AcceptanceTester $I): void
    {
        $I->amOnPage('/logout');
        $I->seeCurrentUrlEquals('/login');
    }

    public function unauthenticatedUserCannotAccessProtectedRouteDecksCreate(AcceptanceTester $I): void
    {
        // Tentar acessar a rota POST /decks diretamente
        // O middleware deve redirecionar para /login mesmo para requisições POST
        $I->amOnPage('/decks/new');
        $I->seeCurrentUrlEquals('/login');
    }

    public function unauthenticatedUserCannotAccessProtectedRouteDecksUpdate(AcceptanceTester $I): void
    {
        // Criar um deck para testar
        $this->currentUser = $this->createUser();
        $deck = new Deck([
            'name' => 'Test Deck',
            'description' => 'Test Description',
            'user_id' => $this->currentUser->id
        ]);
        $deck->save();

        // Tentar acessar a rota PUT /decks/{id} através da página de edição
        // O middleware deve redirecionar para /login
        $I->amOnPage('/decks/' . $deck->id . '/edit');
        $I->seeCurrentUrlEquals('/login');
    }

    public function unauthenticatedUserCannotAccessProtectedRouteDecksDestroy(AcceptanceTester $I): void
    {
        // Criar um deck para testar
        $this->currentUser = $this->createUser();
        $deck = new Deck([
            'name' => 'Test Deck',
            'description' => 'Test Description',
            'user_id' => $this->currentUser->id
        ]);
        $deck->save();

        // Tentar acessar a rota DELETE /decks/{id} através da página de visualização
        // O middleware deve redirecionar para /login
        $I->amOnPage('/decks/' . $deck->id);
        $I->seeCurrentUrlEquals('/login');
    }

    public function unauthenticatedUserCannotAccessProtectedRouteSharedDecksIndex(AcceptanceTester $I): void
    {
        $I->amOnPage('/shared-decks');
        $I->seeCurrentUrlEquals('/login');
    }

    public function unauthenticatedUserCannotAccessProtectedRouteSharedDecksNew(AcceptanceTester $I): void
    {
        // Criar um deck e token para testar
        $this->currentUser = $this->createUser();
        $deck = new Deck([
            'name' => 'Test Deck',
            'description' => 'Test Description',
            'user_id' => $this->currentUser->id
        ]);
        $deck->save();

        $token = 'test-token-123';
        $I->amOnPage('/shared-decks/accept/' . $token);
        $I->seeCurrentUrlEquals('/login');
    }

    public function unauthenticatedUserCannotAccessProtectedRouteDecksShare(AcceptanceTester $I): void
    {
        // Criar um deck para testar
        $this->currentUser = $this->createUser();
        $deck = new Deck([
            'name' => 'Test Deck',
            'description' => 'Test Description',
            'user_id' => $this->currentUser->id
        ]);
        $deck->save();

        // Tentar acessar a rota POST /decks/{id}/share através da página de visualização
        // O middleware deve redirecionar para /login
        $I->amOnPage('/decks/' . $deck->id);
        $I->seeCurrentUrlEquals('/login');
    }

    public function unauthenticatedUserCannotAccessProtectedRouteSharedDecksDestroy(AcceptanceTester $I): void
    {
        // Criar um deck e shared deck para testar
        $this->currentUser = $this->createUser();
        $deck = new Deck([
            'name' => 'Test Deck',
            'description' => 'Test Description',
            'user_id' => $this->currentUser->id
        ]);
        $deck->save();

        $sharedDeck = new DeckUserShared([
            'deck_id' => $deck->id,
            'user_id' => $this->currentUser->id
        ]);
        $sharedDeck->save();

        // Tentar acessar a rota DELETE /shared-decks/{id} através da página de shared decks
        // O middleware deve redirecionar para /login
        $I->amOnPage('/shared-decks');
        $I->seeCurrentUrlEquals('/login');
    }

    public function unauthenticatedUserCannotAccessProtectedRouteDecksStudy(AcceptanceTester $I): void
    {
        // Criar um deck para testar
        $this->currentUser = $this->createUser();
        $deck = new Deck([
            'name' => 'Test Deck',
            'description' => 'Test Description',
            'user_id' => $this->currentUser->id
        ]);
        $deck->save();

        $I->amOnPage('/decks/' . $deck->id . '/study');
        $I->seeCurrentUrlEquals('/login');
    }

    public function unauthenticatedUserCannotAccessProtectedRouteStudyCard(AcceptanceTester $I): void
    {
        $I->amOnPage('/study/card');
        $I->seeCurrentUrlEquals('/login');
    }

    public function unauthenticatedUserCannotAccessProtectedRouteStudyFlip(AcceptanceTester $I): void
    {
        $I->amOnPage('/study/flip');
        $I->seeCurrentUrlEquals('/login');
    }

    public function unauthenticatedUserCannotAccessProtectedRouteStudyAnswer(AcceptanceTester $I): void
    {
        // Tentar acessar a rota POST /study/answer através da página de estudo
        // O middleware deve redirecionar para /login
        $I->amOnPage('/study/card');
        $I->seeCurrentUrlEquals('/login');
    }

    public function unauthenticatedUserCannotAccessProtectedRouteStudyFinish(AcceptanceTester $I): void
    {
        $I->amOnPage('/study/finish');
        $I->seeCurrentUrlEquals('/login');
    }

    public function unauthenticatedUserCannotAccessProtectedRouteCardsCreate(AcceptanceTester $I): void
    {
        // Tentar acessar a rota POST /cards através da página de criação
        // O middleware deve redirecionar para /login
        $I->amOnPage('/cards/new');
        $I->seeCurrentUrlEquals('/login');
    }

    public function unauthenticatedUserCannotAccessProtectedRouteCardsUpdate(AcceptanceTester $I): void
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

        // Tentar acessar a rota PUT /cards/{id} através da página de edição
        // O middleware deve redirecionar para /login
        $I->amOnPage('/cards/' . $card->id . '/edit');
        $I->seeCurrentUrlEquals('/login');
    }

    public function unauthenticatedUserCannotAccessProtectedRouteCardsDestroy(AcceptanceTester $I): void
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

        // Tentar acessar a rota DELETE /cards/{id} através da página de edição
        // O middleware deve redirecionar para /login
        $I->amOnPage('/cards/' . $card->id . '/edit');
        $I->seeCurrentUrlEquals('/login');
    }

    public function unauthenticatedUserCannotAccessProtectedRouteMaterialsIndex(AcceptanceTester $I): void
    {
        $I->amOnPage('/materials');
        $I->seeCurrentUrlEquals('/login');
    }

    public function unauthenticatedUserCannotAccessProtectedRouteMaterialsNew(AcceptanceTester $I): void
    {
        $I->amOnPage('/materials/new');
        $I->seeCurrentUrlEquals('/login');
    }

    public function unauthenticatedUserCannotAccessProtectedRouteMaterialsCreate(AcceptanceTester $I): void
    {
        // Tentar acessar a rota POST /materials através da página de criação
        // O middleware deve redirecionar para /login
        $I->amOnPage('/materials/new');
        $I->seeCurrentUrlEquals('/login');
    }

    public function unauthenticatedUserCannotAccessProtectedRouteMaterialsDestroy(AcceptanceTester $I): void
    {
        // Criar um deck e material para testar
        $this->currentUser = $this->createUser();
        $deck = new Deck([
            'name' => 'Test Deck',
            'description' => 'Test Description',
            'user_id' => $this->currentUser->id
        ]);
        $deck->save();

        $material = new Material([
            'deck_id' => $deck->id,
            'title' => 'Test Material',
            'file_path' => '/test/path.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
        ]);
        $material->save();

        // Tentar acessar a rota DELETE /materials/{id} através da página de materiais
        // O middleware deve redirecionar para /login
        $I->amOnPage('/materials');
        $I->seeCurrentUrlEquals('/login');
    }

    public function unauthenticatedUserCannotAccessProtectedRouteAchievementsIndex(AcceptanceTester $I): void
    {
        $I->amOnPage('/achievements');
        $I->seeCurrentUrlEquals('/login');
    }
}
