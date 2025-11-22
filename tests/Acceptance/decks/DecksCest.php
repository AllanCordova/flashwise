<?php

namespace Tests\Acceptance\decks;

use App\Models\Deck;
use App\Models\Material;
use App\Models\User;
use Core\Constants\Constants;
use PHPUnit\Framework\Assert;
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

    /**
     * @return Material[]
     */
    private function createMaterialsForDeck(Deck $deck, int $count): array
    {
        $materials = [];
        for ($i = 1; $i <= $count; $i++) {
            $material = new Material([
                'deck_id' => $deck->id,
                'title' => "material_$i",
                'file_path' => '/assets/uploads/materials/' . $deck->id . '/material_' . $i . '.pdf',
                'file_size' => 1024,
                'mime_type' => 'application/pdf',
            ]);
            $material->save();

            $absolutePath = Constants::rootPath()->join('public' . $material->file_path);
            $dir = dirname($absolutePath);
            if (!is_dir($dir)) {
                // Criar diretório com permissões corretas
                $oldUmask = umask(0);
                mkdir($dir, 0777, true);
                umask($oldUmask);
            }
            // Criar arquivo com permissões corretas (mesmo comportamento do move_uploaded_file)
            $oldUmask = umask(0);
            file_put_contents($absolutePath, '%PDF-1.4 test content ' . $i);
            chmod($absolutePath, 0666);
            umask($oldUmask);

            $materials[] = $material;
        }

        return $materials;
    }

    // auxiliar functions
    private function submitEmptyFormAndExpectError(AcceptanceTester $I, string $form_id): void
    {
        $I->wait(1);
        $I->fillField('deck[name]', '');
        $I->fillField('deck[description]', '');

        $I->wait(0.5);
        $I->submitForm($form_id, []);
        $I->wait(1);
        $I->see('não pode ser vazio!');
    }

    private function submitExistFormAndExpectError(AcceptanceTester $I): void
    {
        $this->createDecks(1);

        $I->wait(1);
        $I->fillField('deck[name]', 'deck 1');
        $I->fillField('deck[description]', 'deck 1');

        $I->waitForElementClickable('button.btn-deck-primary', 5);
        $I->submitForm('#deck_create', []);
        $I->wait(1);

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
        $I->seeCurrentUrlEquals('/decks?page=1&sort=created_desc');
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
        $I->seeCurrentUrlEquals('/decks?page=1&sort=created_desc');
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
        // Verificar que "deck 1" está presente antes da exclusão
        $I->see('deck 1', '.deck-name');

        // Encontrar e clicar no botão de delete do "deck 1" especificamente
        $I->executeJS("window.confirm = function(){ return true; }");
        $rowXPath = sprintf('//tr[contains(., "%s")]', 'deck 1');
        $deleteButtonXPath = $rowXPath . '//button[contains(@class, "btn-delete")]';
        $I->click($deleteButtonXPath);

        $I->wait(2);
        $I->see('Deck excluído com sucesso!');
        $I->seeCurrentUrlEquals('/decks?page=1&sort=created_desc');

        $I->wait(1);
        $I->seeNumberOfElements('.deck-row-clickable', 2);
        // Verificar que "deck 1" não aparece mais (usar executeJS para evitar match parcial)
        $hasDeck1 = $I->executeJS("
            const deckNames = Array.from(document.querySelectorAll('.deck-name'));
            return deckNames.some(el => el.textContent.trim() === 'deck 1');
        ");
        Assert::assertFalse($hasDeck1, 'Deck 1 não deve aparecer após exclusão');
    }

    public function tryToDeleteDeckAndItsMaterialsSuccessfully(AcceptanceTester $I): void
    {
        $this->currentUser = $this->createUser();
        $this->loginHelper->login($this->currentUser->email, 'password123');
        $I->wait(2);

        $deck = new Deck([
            'name'        => 'Deck Com Materiais',
            'description' => 'Este deck será deletado',
            'user_id'     => $this->currentUser->id,
        ]);
        $deck->save();
        $deckId = $deck->id;

        $materials = $this->createMaterialsForDeck($deck, 3);

        $materialPaths = [];
        $materialIds = [];
        foreach ($materials as $material) {
            $materialIds[] = $material->id;
            $materialPaths[] = Constants::rootPath()->join('public' . $material->file_path);
        }
        $materialsDir = Constants::rootPath()->join("public/assets/uploads/materials/{$deckId}");

        Assert::assertNotNull(Deck::findById($deckId), "Falha no setup: Deck não foi criado.");

        Assert::assertNotEmpty(Material::where(['deck_id' => $deckId]), "Falha no setup: Materiais não foram criados.");
        foreach ($materialPaths as $path) {
            Assert::assertTrue(file_exists($path), "Falha no setup: Arquivo $path não foi criado.");
        }
        Assert::assertTrue(is_dir($materialsDir), "Falha no setup: Diretório $materialsDir não foi criado.");


        $I->amOnPage('/decks');
        $I->wait(1);

        $rowXPath = sprintf('//tr[contains(., "%s")]', $deck->name);
        $deleteButtonXPath = $rowXPath . '//button[contains(@class, "btn-delete")]';

        $I->see($deck->name, '.deck-name');
        $I->click($deleteButtonXPath);

        $I->acceptPopup();

        $I->wait(2);

        $I->see('Deck excluído com sucesso!');
        $I->seeCurrentUrlEquals('/decks?page=1&sort=created_desc');
        $I->dontSee($deck->name, '.deck-name');

        $deletedDeck = Deck::findById($deckId);
        /** @phpstan-ignore-next-line */
        Assert::assertNull($deletedDeck, "O Deck (ID: $deckId) não foi deletado do banco de dados.");

        $remainingMaterials = Material::where(['deck_id' => $deckId]);
        Assert::assertEmpty($remainingMaterials, "Os Materiais do Deck (ID: $deckId) não foram deletados do banco.");

        clearstatcache();
        Assert::assertFalse(is_dir($materialsDir), "O diretório $materialsDir não foi deletado do sistema.");
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
