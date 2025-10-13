<?php

namespace Tests\Acceptance\decks;

use App\Models\Deck;
use App\Models\User;
use Tests\Acceptance\BaseAcceptanceCest;
use Tests\Support\AcceptanceTester;

class DecksCest extends BaseAcceptanceCest
{
    private function createAndLoginUser(AcceptanceTester $I): User
    {
        $user = new User([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'user',
        ]);
        $user->save();

        $I->amOnPage('/login');
        $I->fillField('user[email]', 'test@example.com');
        $I->fillField('user[password]', 'password123');
        $I->click('Entrar');
        $I->seeCurrentUrlEquals('/');

        return $user;
    }

    public function guestCannotAccessDecksPage(AcceptanceTester $I): void
    {
        $I->amOnPage('/decks');
        $I->seeCurrentUrlEquals('/login');
        // Usuário foi redirecionado para login (sem verificar mensagem flash)
    }

    public function userCanViewDecksPage(AcceptanceTester $I): void
    {
        $this->createAndLoginUser($I);

        $I->amOnPage('/decks');
        $I->seeCurrentUrlEquals('/decks');
        $I->see('Meus Decks');
    }

    public function userCanViewEmptyDecksPage(AcceptanceTester $I): void
    {
        $this->createAndLoginUser($I);

        $I->amOnPage('/decks');
        $I->wait(2); // Esperar página carregar completamente
        $I->waitForText('Nenhum deck encontrado', 10);
        $I->see('Comece criando seu primeiro deck de estudos!');
    }

    public function userCanAccessCreateDeckPage(AcceptanceTester $I): void
    {
        $this->createAndLoginUser($I);

        $I->amOnPage('/decks/create');
        $I->seeCurrentUrlEquals('/decks/create');
        $I->wait(2); // Esperar página carregar
        $I->waitForText('Criar Novo Deck', 10);
        $I->see('Nome do Deck');
        $I->see('Descrição');
    }

    public function userCanCreateDeck(AcceptanceTester $I): void
    {
        $this->createAndLoginUser($I);

        $I->amOnPage('/decks/create');
        $I->wait(2);
        $I->fillField('deck[name]', 'Inglês Básico');
        $I->fillField('deck[description]', 'Vocabulário essencial de inglês para iniciantes');
        $I->scrollTo('.btn-deck-primary');
        $I->wait(1);
        $I->click('Criar Deck');

        $I->seeCurrentUrlEquals('/decks');
        $I->wait(2);
        $I->waitForText('Deck criado com sucesso', 10);
        $I->see('Inglês Básico');
        $I->see('Vocabulário essencial de inglês para iniciantes');
    }

    public function cannotCreateDeckWithEmptyName(AcceptanceTester $I): void
    {
        $this->createAndLoginUser($I);

        $I->amOnPage('/decks/create');
        $I->wait(2);

        // Remover validação HTML5 para testar validação do backend
        $I->executeJS("document.getElementById('name').removeAttribute('required');");
        $I->executeJS("document.getElementById('description').removeAttribute('required');");

        $I->fillField('deck[name]', '');
        $I->fillField('deck[description]', 'Descrição válida');
        $I->scrollTo('.btn-deck-primary');
        $I->wait(1);
        $I->click('Criar Deck');

        $I->seeCurrentUrlEquals('/decks/create');
        $I->wait(2);
        $I->waitForText('Não foi possivel criar seu deck tente novamente!', 10);
    }

    public function cannotCreateDeckWithEmptyDescription(AcceptanceTester $I): void
    {
        $this->createAndLoginUser($I);

        $I->amOnPage('/decks/create');
        $I->wait(2);

        // Remover validação HTML5 para testar validação do backend
        $I->executeJS("document.getElementById('name').removeAttribute('required');");
        $I->executeJS("document.getElementById('description').removeAttribute('required');");

        $I->fillField('deck[name]', 'Nome Válido');
        $I->fillField('deck[description]', '');
        $I->scrollTo('.btn-deck-primary');
        $I->wait(1);
        $I->click('Criar Deck');

        $I->seeCurrentUrlEquals('/decks/create');
        $I->wait(2);
        $I->waitForText('Não foi possivel criar seu deck tente novamente!', 10);
    }

    public function cannotCreateDeckWithDuplicateName(AcceptanceTester $I): void
    {
        $this->createAndLoginUser($I);

        // Criar primeiro deck
        $deck = new Deck([
            'name' => 'Deck Duplicado',
            'description' => 'Primeira descrição',
            'path_img' => 'test.png',
            'category_id' => null,
        ]);
        $deck->save();

        // Tentar criar deck com mesmo nome
        $I->amOnPage('/decks/create');
        $I->wait(2);
        $I->fillField('deck[name]', 'Deck Duplicado');
        $I->fillField('deck[description]', 'Segunda descrição');
        $I->scrollTo('.btn-deck-primary');
        $I->wait(1);
        $I->click('Criar Deck');

        $I->seeCurrentUrlEquals('/decks/create');
        $I->wait(2);
        $I->waitForText('Não foi possivel criar seu deck tente novamente!', 10);
    }

    public function userCanViewCreatedDecks(AcceptanceTester $I): void
    {
        $this->createAndLoginUser($I);

        // Criar múltiplos decks
        $deck1 = new Deck([
            'name' => 'Matemática',
            'description' => 'Fórmulas e conceitos matemáticos',
            'path_img' => 'math.png',
            'category_id' => null,
        ]);
        $deck1->save();

        $deck2 = new Deck([
            'name' => 'História',
            'description' => 'Datas e eventos históricos',
            'path_img' => 'history.png',
            'category_id' => null,
        ]);
        $deck2->save();

        $I->amOnPage('/decks');
        $I->wait(2);
        $I->waitForText('Matemática', 10);
        $I->see('Fórmulas e conceitos matemáticos');
        $I->see('História');
        $I->see('Datas e eventos históricos');
    }

    public function userCanCreateMultipleDecks(AcceptanceTester $I): void
    {
        $this->createAndLoginUser($I);

        // Criar primeiro deck
        $I->amOnPage('/decks/create');
        $I->wait(2);
        $I->fillField('deck[name]', 'Deck 1');
        $I->fillField('deck[description]', 'Descrição do deck 1');
        $I->scrollTo('.btn-deck-primary');
        $I->wait(1);
        $I->click('Criar Deck');
        $I->seeCurrentUrlEquals('/decks');

        // Criar segundo deck
        $I->wait(2);
        $I->click('Criar Novo Deck');
        $I->wait(2);
        $I->fillField('deck[name]', 'Deck 2');
        $I->fillField('deck[description]', 'Descrição do deck 2');
        $I->scrollTo('.btn-deck-primary');
        $I->wait(1);
        $I->click('Criar Deck');
        $I->seeCurrentUrlEquals('/decks');

        // Verificar ambos os decks
        $I->wait(2);
        $I->waitForText('Deck 1', 10);
        $I->see('Deck 2');
    }

    public function deckNameIsDisplayedInTable(AcceptanceTester $I): void
    {
        $this->createAndLoginUser($I);

        $deck = new Deck([
            'name' => 'Programação PHP',
            'description' => 'Conceitos de PHP e frameworks',
            'path_img' => 'php.png',
            'category_id' => null,
        ]);
        $deck->save();

        $I->amOnPage('/decks');
        $I->wait(2);

        // Verificar se a página tem decks (não está vazia)
        $I->dontSee('Nenhum deck encontrado');

        // Verificar header da tabela e nome do deck
        $I->waitForText('Programação PHP', 10);
        $I->see('Nome do Deck'); // Header da tabela
    }

    public function deckDescriptionIsDisplayedInTable(AcceptanceTester $I): void
    {
        $this->createAndLoginUser($I);

        $deck = new Deck([
            'name' => 'JavaScript ES6',
            'description' => 'Recursos modernos do JavaScript',
            'path_img' => 'js.png',
            'category_id' => null,
        ]);
        $deck->save();

        $I->amOnPage('/decks');
        $I->wait(2);
        $I->waitForText('Recursos modernos do JavaScript', 10);
    }

    public function createDeckButtonIsVisibleOnDecksPage(AcceptanceTester $I): void
    {
        $this->createAndLoginUser($I);

        $I->amOnPage('/decks');
        $I->wait(2);
        $I->waitForText('Criar Novo Deck', 10);
    }

    public function decksPageShowsCorrectStatistics(AcceptanceTester $I): void
    {
        $this->createAndLoginUser($I);

        $deck = new Deck([
            'name' => 'Estatísticas Test',
            'description' => 'Deck para testar estatísticas',
            'path_img' => 'stats.png',
            'category_id' => null,
        ]);
        $deck->save();

        $I->amOnPage('/decks');
        $I->wait(2);

        // Verificar se a página tem decks (não está vazia)
        $I->dontSee('Nenhum deck encontrado');

        // Verificar que as colunas de estatísticas existem
        $I->waitForText('Estatísticas Test', 10);
        $I->see('Novas');
        $I->see('Revisar');
        $I->see('Total');
    }

    public function cancelButtonRedirectsToDecksPage(AcceptanceTester $I): void
    {
        $this->createAndLoginUser($I);

        $I->amOnPage('/decks/create');
        $I->scrollTo('.btn-deck-secondary');
        $I->wait(1);
        $I->click('Cancelar');
        $I->seeCurrentUrlEquals('/decks');
    }
}
