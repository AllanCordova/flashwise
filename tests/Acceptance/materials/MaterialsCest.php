<?php

namespace Tests\Acceptance\materials;

use App\Models\Deck;
use App\Models\Material;
use App\Models\User;
use Core\Constants\Constants;
use PHPUnit\Framework\Assert;
use Tests\Acceptance\BaseAcceptanceCest;
use Tests\Support\AcceptanceTester;

class MaterialsCest extends BaseAcceptanceCest
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
            'description' => 'Deck for testing materials',
            'user_id' => $this->currentUser->id
        ]);
        $deck->save();

        return $deck;
    }

    private function createMaterial(): Material
    {
        $material = new Material([
            'deck_id' => $this->currentDeck->id,
            'title' => 'test_valid',
            'file_path' => '/assets/uploads/materials/' . $this->currentDeck->id . '/material_test.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
        ]);
        $material->save();

        // Criar o arquivo físico no filesystem
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
        file_put_contents($absolutePath, '%PDF-1.4 test content');
        chmod($absolutePath, 0666);
        umask($oldUmask);

        return $material;
    }

    private function getTestDataPath(string $filename): string
    {
        return Constants::rootPath()->join('tests/Support/Data/' . $filename);
    }

    // ------------ upload ------------
    public function tryToUploadMaterialSuccessfully(AcceptanceTester $I): void
    {
        $this->currentUser = $this->createUser();
        $this->loginHelper->login($this->currentUser->email, 'password123');
        $I->wait(2);

        $this->currentDeck = $this->createDeck();

        $I->amOnPage('/materials/new?deck_id=' . $this->currentDeck->id);

        // Codeception procura arquivos na pasta tests/Support/Data, então usamos apenas o nome do arquivo
        $I->attachFile('#material_file', 'test_valid.pdf');

        $I->wait(1);
        $I->scrollTo('button[type="submit"]');
        $I->waitForElementClickable('button[type="submit"]', 5);
        $I->click('button[type="submit"]');

        $I->wait(2);
        $I->see('Material adicionado com sucesso!');
        $I->seeCurrentUrlMatches('~^/materials\?deck_id=' . $this->currentDeck->id . '.*$~');
    }

    public function tryToUploadMaterialWithInvalidFormat(AcceptanceTester $I): void
    {
        $this->currentUser = $this->createUser();
        $this->loginHelper->login($this->currentUser->email, 'password123');
        $I->wait(2);

        $this->currentDeck = $this->createDeck();

        $I->amOnPage('/materials/new?deck_id=' . $this->currentDeck->id);

        // Codeception procura arquivos na pasta tests/Support/Data
        $I->attachFile('#material_file', 'test_invalid.exe');

        $I->wait(1);
        $I->scrollTo('button[type="submit"]');
        $I->waitForElementClickable('button[type="submit"]', 5);
        $I->click('button[type="submit"]');

        $I->wait(2);
        $I->see('Não foi possível fazer upload do material');
        $I->see('é um tipo de arquivo não permitido');
    }

    public function tryToUploadMaterialWithExceededSize(AcceptanceTester $I): void
    {
        $this->currentUser = $this->createUser();
        $this->loginHelper->login($this->currentUser->email, 'password123');
        $I->wait(2);

        $this->currentDeck = $this->createDeck();

        // Criar arquivo grande dinamicamente (maior que 20MB)
        $largeFilePath = $this->getTestDataPath('test_large.pdf');
        $largeFileDir = dirname($largeFilePath);
        if (!is_dir($largeFileDir)) {
            mkdir($largeFileDir, 0777, true);
        }

        // Criar arquivo de 20MB + 1KB (20971520 + 1024 bytes) para exceder o limite de validação
        // mas ainda estar dentro do limite do PHP (25MB)
        // Isso garante que o PHP aceite o upload e a validação detecte o tamanho excedido
        $fp = fopen($largeFilePath, 'w');
        if ($fp) {
            // Escrever em chunks para evitar problemas de memória
            $chunkSize = 1024 * 1024; // 1MB por vez
            $maxSize = 20971520; // 20MB (limite de validação)
            $totalBytes = $maxSize + 1024; // 20MB + 1KB = 20972544 bytes
            $written = 0;
            while ($written < $totalBytes) {
                $toWrite = min($chunkSize, $totalBytes - $written);
                fwrite($fp, str_repeat('A', $toWrite));
                $written += $toWrite;
            }
            fclose($fp);
        }

        $I->amOnPage('/materials/new?deck_id=' . $this->currentDeck->id);

        // Para arquivo criado dinamicamente, usar apenas o nome do arquivo
        // Codeception vai procurar na pasta data
        $I->attachFile('#material_file', 'test_large.pdf');

        $I->wait(1);
        $I->scrollTo('button[type="submit"]');
        $I->waitForElementClickable('button[type="submit"]', 5);
        $I->click('button[type="submit"]');

        $I->wait(2);
        $I->see('Não foi possível fazer upload do material');
        $I->see('é muito grande');

        // Limpar arquivo de teste
        if (file_exists($largeFilePath)) {
            unlink($largeFilePath);
        }
    }

    // ------------ delete ------------
    public function tryToDeleteMaterialAndRemoveFromFilesystem(AcceptanceTester $I): void
    {
        $this->currentUser = $this->createUser();
        $this->loginHelper->login($this->currentUser->email, 'password123');
        $I->wait(2);

        $this->currentDeck = $this->createDeck();
        $material = $this->createMaterial();

        // Verificar que o arquivo existe antes da exclusão
        $absolutePath = Constants::rootPath()->join('public' . $material->file_path);
        Assert::assertTrue(file_exists($absolutePath), 'Arquivo deve existir antes da exclusão');

        $I->amOnPage('/materials?deck_id=' . $this->currentDeck->id);
        $I->wait(1);

        $I->executeJS("window.confirm = function(){ return true; }");

        // Encontrar e clicar no botão de exclusão do material
        $I->click('form[action*="materials/' . $material->id . '"] button.btn-danger');

        $I->wait(2);
        $I->see('Material excluído com sucesso!');
        $I->seeCurrentUrlMatches('~^/materials\?deck_id=' . $this->currentDeck->id . '.*$~');

        // Verificar que o arquivo foi removido do filesystem
        Assert::assertFalse(file_exists($absolutePath), 'Arquivo deve ser removido do filesystem após exclusão');
    }

    // ------------ view ------------
    public function tryToViewUploadedMaterials(AcceptanceTester $I): void
    {
        $this->currentUser = $this->createUser();
        $this->loginHelper->login($this->currentUser->email, 'password123');
        $I->wait(2);

        $this->currentDeck = $this->createDeck();
        $material = $this->createMaterial();

        $I->amOnPage('/materials?deck_id=' . $this->currentDeck->id);
        $I->wait(1);

        // Verificar se o material está sendo renderizado
        $I->see($material->title, '.material-title');
        $I->seeElement('.material-card');

        // Verificar se o link de visualização está presente
        $I->seeElement('a[href="' . $material->getFileUrl() . '"]');

        // Verificar se o tamanho formatado está sendo exibido
        $I->see($material->getFormattedSize(), '.material-size');
    }

    public function testEmptyStateWhenNoMaterialsExist(AcceptanceTester $I): void
    {
        $this->currentUser = $this->createUser();
        $this->loginHelper->login($this->currentUser->email, 'password123');
        $I->wait(2);

        $this->currentDeck = $this->createDeck();

        $I->amOnPage('/materials?deck_id=' . $this->currentDeck->id);
        $I->wait(1);

        $I->see('Nenhum material encontrado');
        $I->dontSeeElement('.material-card');
    }
}
