<?php

namespace Tests\Acceptance\decks;

use App\Models\Deck;
use App\Models\User;
use Tests\Acceptance\BaseAcceptanceCest;
use Tests\Support\AcceptanceTester;

class DecksCest extends BaseAcceptanceCest
{
    private ?User $currentUser = null;

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

    /**
     * @return array<int, array{name: string}>
     */
    private function createDecks(int $count): array
    {
        $decks = [];
        for ($i = 1; $i <= $count; $i++) {
            $newDeck = new Deck(['name' => "deck $i", 'description' => "deck $i", 'user_id' => $this->currentUser->id]);
            $newDeck->save();

            $decks[] = ['name' => $newDeck->name];
        }

        return $decks;
    }

    // auxiliar functions
    private function submitEmptyFormAndExpectError(AcceptanceTester $I, string $form_id): void
    {
        $I->fillField('deck[name]', '');
        $I->fillField('deck[description]', '');

        $I->submitForm($form_id, []);
        $I->see('não pode ser vazio!');
    }

    private function submitExistFormAndExpectError(AcceptanceTester $I): void
    {
        $this->createDecks(1);

        $I->fillField('deck[name]', 'deck 1');
        $I->fillField('deck[description]', 'deck 1');

        $I->waitForElementClickable('button.btn-deck-primary', 5);
        $I->submitForm('#deck_create', []);

        $I->see('já existe um registro com esse dado');
        $I->seeCurrentUrlEquals('/decks');
    }

    // ------------ create ------------
    public function tryToCreateSuccess(AcceptanceTester $I): void
    {
        $this->currentUser = $this->createUser();
        $this->loginHelper->login($this->currentUser->email, 'password123');
        $I->wait(2);

        $I->amOnPage('/decks/new');

        $I->fillField('deck[name]', 'Test');
        $I->fillField('deck[description]', 'Teste Deck');
        $I->submitForm('#deck_create', []);

        $I->see('Deck criado com sucesso');
        $I->seeCurrentUrlEquals('/decks');
    }

    public function tryToCreateWithNoDataValidation(AcceptanceTester $I): void
    {
        $this->currentUser = $this->createUser();
        $this->loginHelper->login($this->currentUser->email, 'password123');
        $I->wait(2);

        $I->amOnPage('/decks/new');
        $this->submitEmptyFormAndExpectError($I, '#deck_create');
    }

    public function tryToCreateWithDuplicateDataValidation(AcceptanceTester $I): void
    {
        $this->currentUser = $this->createUser();
        $this->loginHelper->login($this->currentUser->email, 'password123');
        $I->wait(2);

        $I->amOnPage('/decks/new');
        $this->submitExistFormAndExpectError($I);
    }

    // ------------ update ------------
    public function tryToUpdateSuccess(AcceptanceTester $I): void
    {
        $this->currentUser = $this->createUser();
        $this->loginHelper->login($this->currentUser->email, 'password123');
        $I->wait(2);

        $this->createDecks(1);
        $I->amOnPage('/decks/1/edit');

        $I->fillField('deck[name]', 'Teste');
        $I->fillField('deck[description]', 'Teste Deck');
        $I->submitForm('#deck_update', []);

        $I->see('Deck atualizado com sucesso');
        $I->seeCurrentUrlEquals('/decks');
    }

    public function tryToUpdateWithNoDataValidation(AcceptanceTester $I): void
    {
        $this->currentUser = $this->createUser();
        $this->loginHelper->login($this->currentUser->email, 'password123');
        $I->wait(2);

        $this->createDecks(1);

        $I->amOnPage('/decks/1/edit');
        $this->submitEmptyFormAndExpectError($I, '#deck_update');
    }

    // ------------ list ------------
    public function tryTolistRegistersSuccessfully(AcceptanceTester $I): void
    {
        $this->currentUser = $this->createUser();
        $this->loginHelper->login($this->currentUser->email, 'password123');

        $I->wait(2);
        $I->seeCurrentUrlEquals('/');

        $this->createDecks(3);

        codecept_debug($this->currentUser->id);

        $I->amOnPage('/decks');

        $I->wait(1);

        $I->seeNumberOfElements('.deck-row-clickable', 3);

        $I->see('deck 1', '.deck-name');
        $I->see('deck 2', '.deck-name');
        $I->see('deck 3', '.deck-name');
    }

    public function testEmptyStateWhenNoDecksExist(AcceptanceTester $I): void
    {
        $this->currentUser = $this->createUser();
        $this->loginHelper->login($this->currentUser->email, 'password123');
        $I->wait(2);

        $I->amOnPage('/decks');

        $I->dontSeeElement('tbody tr');
    }

    // ------------ delete ------------
    public function tryToDeleteDeckSuccessfully(AcceptanceTester $I): void
    {
        $this->currentUser = $this->createUser();
        $this->loginHelper->login($this->currentUser->email, 'password123');
        $I->wait(2);

        $this->createDecks(3);

        $I->amOnPage('/decks');
        $I->wait(1);

        $I->seeNumberOfElements('.deck-row-clickable', 3);

        $I->executeJS("window.confirm = function(){ return true; }");
        $I->click('.deck-actions .btn-delete');

        $I->wait(2);
        $I->see('Deck excluído com sucesso!');
        $I->seeCurrentUrlEquals('/decks');

        $I->wait(1);
        $I->seeNumberOfElements('.deck-row-clickable', 2);
        $I->dontSee('deck 1', '.deck-name');
    }

    // ------------ paginate ------------
    public function tryToPaginateDecksCorrectly(AcceptanceTester $I): void
    {
        $this->currentUser = $this->createUser();
        $this->loginHelper->login($this->currentUser->email, 'password123');
        $I->wait(2);

        $this->createDecks(11);

        codecept_debug("Criados 11 decks para testar paginação");

        $I->amOnPage('/decks');
        $I->wait(1);

        $I->seeNumberOfElements('.deck-row-clickable', 10);

        $I->see('deck 1', '.deck-name');
        $I->see('deck 10', '.deck-name');

        $I->dontSee('deck 11', '.deck-name');

        $I->seeElement('.pagination');

        $I->scrollTo('.pagination');
        $I->wait(0.5);

        $I->click('.pagination .bi-arrow-right');
        $I->wait(1);

        $I->seeNumberOfElements('.deck-row-clickable', 1);
        $I->see('deck 11', '.deck-name');

        $I->dontSeeElement(['xpath' => '//div[@class="deck-name"][text()="deck 1"]']);
        $I->dontSeeElement(['xpath' => '//div[@class="deck-name"][text()="deck 2"]']);
    }
}
