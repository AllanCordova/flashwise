<?php

namespace Tests\Acceptance\shared_decks;

use App\Models\Deck;
use App\Models\DeckUserShared;
use App\Models\User;
use App\Services\ShareTokenService;
use PHPUnit\Framework\Assert;
use Tests\Acceptance\BaseAcceptanceCest;
use Tests\Support\AcceptanceTester;

class SharedDecksCest extends BaseAcceptanceCest
{
    private ?User $currentUser = null;
    private ?User $ownerUser = null;
    private ?Deck $currentDeck = null;

    // ------------ setup ------------
    private function createUser(string $name = 'Test User', string $email = 'test@example.com'): User
    {
        $user = new User([
            'name' => $name,
            'email' => $email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'user',
        ]);
        $user->save();

        return $user;
    }

    private function createDeck(User $user, string $name = 'Test Deck', string $description = 'Test Description'): Deck
    {
        $deck = new Deck([
            'name' => $name,
            'description' => $description,
            'user_id' => $user->id
        ]);
        $deck->save();

        return $deck;
    }

    private function generateShareToken(int $deckId): string
    {
        return ShareTokenService::generate($deckId);
    }

    // ------------ create (gerar código de compartilhamento) ------------
    public function tryToGenerateShareUrlWithInvalidDeck(AcceptanceTester $I): void
    {
        $this->currentUser = $this->createUser();
        $this->loginHelper->login($this->currentUser->email, 'password123');
        $I->wait(2);

        $nonExistentDeckId = 99999;
        $I->amOnPage('/decks');
        $I->wait(1);

        $I->executeJS("
            window.shareResult = null;
            fetch('/decks/" . $nonExistentDeckId . "/share', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'}
            }).then(response => response.json())
              .then(data => {
                  window.shareResult = data;
              })
              .catch(error => {
                  window.shareResult = {error: error.message};
              });
        ");
        $I->wait(2);

        $result = $I->executeJS("return window.shareResult;");
    }

    public function tryToGenerateShareUrlWithValidDeck(AcceptanceTester $I): void
    {
        $this->currentUser = $this->createUser();
        $this->loginHelper->login($this->currentUser->email, 'password123');
        $I->wait(2);

        $this->currentDeck = $this->createDeck($this->currentUser, 'Deck para Compartilhar', 'Descrição do deck');

        $I->amOnPage('/decks');
        $I->wait(1);

        $rowXPath = sprintf('//tr[contains(., "%s")]', 'Deck para Compartilhar');
        $shareButtonXPath = $rowXPath . '//button[contains(@class, "btn-share")]';

        $I->executeJS("
            window.shareResult = null;
            window.originalShareDeck = window.shareDeck;
            window.shareDeck = async function(deckId) {
                try {
                    const response = await fetch('/decks/' + deckId + '/share', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'}
                    });
                    const data = await response.json();
                    window.shareResult = data;
                    return data;
                } catch (error) {
                    window.shareResult = {error: error.message};
                    throw error;
                }
            };
        ");

        $I->click($shareButtonXPath);
        $I->wait(2);

        $result = $I->executeJS("return window.shareResult;");
        Assert::assertNotNull($result, 'Resultado não foi retornado');
        Assert::assertTrue($result['success'], 'Link de compartilhamento não foi gerado com sucesso');
        Assert::assertNotEmpty($result['shareUrl'], 'URL de compartilhamento está vazia');
        Assert::assertStringContainsString('/shared-decks/accept/', $result['shareUrl'], 'URL não contém o caminho correto');
    }

    // ------------ new (aceitar compartilhamento) ------------
    public function tryToAcceptShareWithSuccess(AcceptanceTester $I): void
    {
        $this->ownerUser = $this->createUser('Owner User', 'owner@example.com');
        $this->currentDeck = $this->createDeck($this->ownerUser, 'Deck Compartilhado', 'Deck para ser compartilhado');

        $this->currentUser = $this->createUser('Receiver User', 'receiver@example.com');
        $this->loginHelper->login($this->currentUser->email, 'password123');
        $I->wait(2);

        $token = $this->generateShareToken($this->currentDeck->id);
        $shareUrl = '/shared-decks/accept/' . $token;

        $I->amOnPage($shareUrl);
        $I->wait(2);

        $I->see('Deck compartilhado com sucesso!');
        $I->seeCurrentUrlEquals('/shared-decks');

        $sharedDeck = DeckUserShared::findBy([
            'deck_id' => $this->currentDeck->id,
            'user_id' => $this->currentUser->id
        ]);
        Assert::assertNotNull($sharedDeck, 'Compartilhamento não foi criado no banco de dados');
    }

    public function tryToAcceptShareThatWasAlreadyAccepted(AcceptanceTester $I): void
    {
        $this->ownerUser = $this->createUser('Owner User 2', 'owner2@example.com');
        $this->currentDeck = $this->createDeck($this->ownerUser, 'Deck Já Compartilhado', 'Deck já compartilhado');

        $this->currentUser = $this->createUser('Receiver User 2', 'receiver2@example.com');
        $this->loginHelper->login($this->currentUser->email, 'password123');
        $I->wait(2);

        $token = $this->generateShareToken($this->currentDeck->id);
        $shareUrl = '/shared-decks/accept/' . $token;

        $I->amOnPage($shareUrl);
        $I->wait(2);
        $I->see('Deck compartilhado com sucesso!');

        $I->amOnPage($shareUrl);
        $I->wait(2);

        $I->see('Erro ao compartilhar deck');
        $I->seeCurrentUrlEquals('/shared-decks');

        $sharedDecks = DeckUserShared::where([
            'deck_id' => $this->currentDeck->id,
            'user_id' => $this->currentUser->id
        ]);
        Assert::assertCount(1, $sharedDecks, 'Deve existir apenas um compartilhamento');
    }

    public function tryToAcceptOwnShareShouldFail(AcceptanceTester $I): void
    {
        $this->currentUser = $this->createUser('Owner User 3', 'owner3@example.com');
        $this->currentDeck = $this->createDeck($this->currentUser, 'Meu Próprio Deck', 'Deck do próprio usuário');

        $this->loginHelper->login($this->currentUser->email, 'password123');
        $I->wait(2);

        $token = $this->generateShareToken($this->currentDeck->id);
        $shareUrl = '/shared-decks/accept/' . $token;

        $I->amOnPage($shareUrl);
        $I->wait(2);

        $I->see('Você não pode compartilhar o deck com você mesmo!');
        $I->seeCurrentUrlEquals('/decks');

        $sharedDeck = DeckUserShared::findBy([
            'deck_id' => $this->currentDeck->id,
            'user_id' => $this->currentUser->id
        ]);
        Assert::assertNull($sharedDeck, 'Compartilhamento não deve ter sido criado');
    }

    public function tryToAcceptShareWithInvalidToken(AcceptanceTester $I): void
    {
        $this->currentUser = $this->createUser('Test User Invalid', 'invalid@example.com');
        $this->loginHelper->login($this->currentUser->email, 'password123');
        $I->wait(2);

        $invalidToken = 'token-invalido-123';
        $I->amOnPage('/shared-decks/accept/' . $invalidToken);
        $I->wait(2);

        $I->seeCurrentUrlEquals('/decks');
        $I->see('Link de compartilhamento expirado');
    }

    // ------------ index (view) ------------
    public function tryToListSharedDecksSuccessfully(AcceptanceTester $I): void
    {
        $this->ownerUser = $this->createUser('Owner List', 'ownerlist@example.com');

        $deck1 = $this->createDeck($this->ownerUser, 'Deck Compartilhado 1', 'Primeiro deck compartilhado');
        $deck2 = $this->createDeck($this->ownerUser, 'Deck Compartilhado 2', 'Segundo deck compartilhado');
        $deck3 = $this->createDeck($this->ownerUser, 'Deck Compartilhado 3', 'Terceiro deck compartilhado');

        $this->currentUser = $this->createUser('Receiver List', 'receiverlist@example.com');
        $this->loginHelper->login($this->currentUser->email, 'password123');
        $I->wait(2);

        $shared1 = new DeckUserShared(['deck_id' => $deck1->id, 'user_id' => $this->currentUser->id]);
        $shared1->save();
        $shared2 = new DeckUserShared(['deck_id' => $deck2->id, 'user_id' => $this->currentUser->id]);
        $shared2->save();
        $shared3 = new DeckUserShared(['deck_id' => $deck3->id, 'user_id' => $this->currentUser->id]);
        $shared3->save();

        $I->amOnPage('/shared-decks');
        $I->wait(1);

        $I->see('Deck Compartilhado 1', '.deck-name');
        $I->see('Deck Compartilhado 2', '.deck-name');
        $I->see('Deck Compartilhado 3', '.deck-name');

        $I->see('Owner List', '.badge');

        $I->seeNumberOfElements('.deck-row-clickable', 3);
    }

    public function testEmptyStateWhenNoSharedDecksExist(AcceptanceTester $I): void
    {
        $this->currentUser = $this->createUser('Empty User', 'empty@example.com');
        $this->loginHelper->login($this->currentUser->email, 'password123');
        $I->wait(2);

        $I->amOnPage('/shared-decks');
        $I->wait(1);

        $I->see('Nenhum deck compartilhado');
        $I->see('Quando alguém compartilhar um deck com você, ele aparecerá aqui!');
        $I->dontSeeElement('tbody tr');
    }

    // ------------ destroy (remover compartilhamento) ------------
    public function tryToRemoveSharedDeckSuccessfully(AcceptanceTester $I): void
    {
        $this->ownerUser = $this->createUser('Owner Remove', 'ownerremove@example.com');
        $this->currentDeck = $this->createDeck($this->ownerUser, 'Deck para Remover', 'Deck que será removido');

        $this->currentUser = $this->createUser('Receiver Remove', 'receiverremove@example.com');
        $this->loginHelper->login($this->currentUser->email, 'password123');
        $I->wait(2);

        $sharedDeck = new DeckUserShared([
            'deck_id' => $this->currentDeck->id,
            'user_id' => $this->currentUser->id
        ]);
        $sharedDeck->save();
        $sharedDeckId = $sharedDeck->id;

        Assert::assertNotNull(DeckUserShared::findById($sharedDeckId), 'Compartilhamento deve existir antes da remoção');

        $I->amOnPage('/shared-decks');
        $I->wait(1);

        $I->see('Deck para Remover', '.deck-name');


        $I->executeJS("window.confirm = function(){ return true; }");

        $rowXPath = sprintf('//tr[contains(., "%s")]', 'Deck para Remover');
        $deleteButtonXPath = $rowXPath . '//button[contains(@class, "btn-delete")]';
        $I->click($deleteButtonXPath);

        $I->wait(2);

        $I->see('Compartilhamento removido com sucesso');
        $I->seeCurrentUrlMatches('~^/shared-decks(\?page=\d+)?$~');
    }

    public function tryToRemoveNonExistentSharedDeckShouldFail(AcceptanceTester $I): void
    {
        $this->currentUser = $this->createUser('Test Remove Invalid', 'removeinvalid@example.com');
        $this->loginHelper->login($this->currentUser->email, 'password123');
        $I->wait(2);

        $this->ownerUser = $this->createUser('Owner Remove Invalid', 'ownerremoveinvalid@example.com');
        $otherDeck = $this->createDeck($this->ownerUser, 'Deck Não Compartilhado', 'Deck que não foi compartilhado');

        $I->amOnPage('/shared-decks');
        $I->wait(1);

        $I->executeJS("
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/shared-decks/" . $otherDeck->id . "';
            
            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'DELETE';
            form.appendChild(methodInput);
            
            const pageInput = document.createElement('input');
            pageInput.type = 'hidden';
            pageInput.name = 'page';
            pageInput.value = '1';
            form.appendChild(pageInput);
            
            document.body.appendChild(form);
            form.submit();
        ");
        $I->wait(2);

        $I->see('Erro ao remover compartilhamento: você não tem acesso a este deck');
        $I->seeCurrentUrlMatches('~^/shared-decks(\?page=\d+)?$~');
    }

    // ------------ paginate ------------
    public function tryToPaginateSharedDecksCorrectly(AcceptanceTester $I): void
    {
        $this->ownerUser = $this->createUser('Owner Paginate', 'ownerpaginate@example.com');

        $this->currentUser = $this->createUser('Receiver Paginate', 'receiverpaginate@example.com');
        $this->loginHelper->login($this->currentUser->email, 'password123');
        $I->wait(2);

        for ($i = 1; $i <= 11; $i++) {
            $deck = $this->createDeck($this->ownerUser, "Deck Compartilhado $i", "Descrição $i");
            $sharedDeck = new DeckUserShared([
                'deck_id' => $deck->id,
                'user_id' => $this->currentUser->id
            ]);
            $sharedDeck->save();
        }

        $I->amOnPage('/shared-decks');
        $I->wait(1);

        $I->seeNumberOfElements('.deck-row-clickable', 10);
        $I->see('Deck Compartilhado 1', '.deck-name');
        $I->see('Deck Compartilhado 10', '.deck-name');
        $I->dontSee('Deck Compartilhado 11', '.deck-name');

        $I->seeElement('.pagination');

        $I->scrollTo('.pagination');
        $I->wait(0.5);
        $I->click('.pagination .bi-arrow-right');
        $I->wait(1);

        $I->seeNumberOfElements('.deck-row-clickable', 1);
        $I->see('Deck Compartilhado 11', '.deck-name');

        $hasDeck1 = $I->executeJS("
            const deckNames = Array.from(document.querySelectorAll('.deck-name'));
            return deckNames.some(el => el.textContent.trim() === 'Deck Compartilhado 1');
        ");
        Assert::assertFalse($hasDeck1, 'Página 2 não deve conter "Deck Compartilhado 1"');
    }
}
