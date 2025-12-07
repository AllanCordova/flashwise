<?php

namespace Tests\Acceptance\achievements;

use App\Models\Achievement;
use App\Models\User;
use Core\Constants\Constants;
use PHPUnit\Framework\Assert;
use Tests\Acceptance\BaseAcceptanceCest;
use Tests\Support\AcceptanceTester;

class AchievementsCest extends BaseAcceptanceCest
{
    private ?User $currentUser = null;
    private ?User $otherUser = null;

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

    /**
     * @return Achievement[]
     */
    private function createAchievementsForUser(User $user, int $count, string $prefix = 'Conquista'): array
    {
        $achievements = [];
        $imagePath = '/assets/images/defaults/avatar.png';

        // Garantir que a imagem padrão existe
        $absoluteImagePath = Constants::rootPath()->join('public' . $imagePath);
        if (!file_exists($absoluteImagePath)) {
            $dir = dirname($absoluteImagePath);
            if (!is_dir($dir)) {
                $oldUmask = umask(0);
                mkdir($dir, 0777, true);
                umask($oldUmask);
            }
            // Criar uma imagem PNG simples para teste
            $oldUmask = umask(0);
            file_put_contents(
                $absoluteImagePath,
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==')
            );
            chmod($absoluteImagePath, 0666);
            umask($oldUmask);
        }

        for ($i = 1; $i <= $count; $i++) {
            $achievement = new Achievement([
                'user_id' => $user->id,
                'title' => "$prefix $i",
                'file_path' => $imagePath,
                'file_size' => 1024,
                'mime_type' => 'image/png',
            ]);
            $achievement->save();
            $achievements[] = $achievement;
        }

        return $achievements;
    }

    // ------------ API tests ------------
    public function tryToGetAchievementsApiReturnsData(AcceptanceTester $I): void
    {
        $this->currentUser = $this->createUser();
        $this->loginHelper->login($this->currentUser->email, 'password123');
        $I->wait(2);

        $this->createAchievementsForUser($this->currentUser, 3);

        // Fazer requisição AJAX para a API
        $I->executeJS("
            window.apiResult = null;
            fetch('/achievements', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                window.apiResult = data;
            })
            .catch(error => { 
                window.apiResult = { error: error.message }; 
            });
        ");

        $I->wait(2);
        $result = $I->executeJS("return window.apiResult;");

        Assert::assertNotNull($result, 'Resultado não foi retornado');
        Assert::assertTrue($result['success'], 'API deve retornar success: true');
        Assert::assertIsArray($result['achievements'], 'achievements deve ser um array');
        Assert::assertCount(3, $result['achievements'], 'Deve retornar 3 conquistas');

        // Verificar estrutura dos dados
        $firstAchievement = $result['achievements'][0];
        Assert::assertArrayHasKey('id', $firstAchievement, 'Achievement deve ter campo id');
        Assert::assertArrayHasKey('title', $firstAchievement, 'Achievement deve ter campo title');
        Assert::assertArrayHasKey('file_path', $firstAchievement, 'Achievement deve ter campo file_path');
        Assert::assertArrayHasKey('uploaded_at', $firstAchievement, 'Achievement deve ter campo uploaded_at');
    }

    public function tryToGetAchievementsApiReturnsEmptyWhenNoAchievements(AcceptanceTester $I): void
    {
        $this->currentUser = $this->createUser();
        $this->loginHelper->login($this->currentUser->email, 'password123');
        $I->wait(2);

        // Fazer requisição AJAX para a API sem criar conquistas
        $I->executeJS("
            window.apiEmptyResult = null;
            fetch('/achievements', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                window.apiEmptyResult = data;
            })
            .catch(error => { 
                window.apiEmptyResult = { error: error.message }; 
            });
        ");

        $I->wait(2);
        $result = $I->executeJS("return window.apiEmptyResult;");

        Assert::assertNotNull($result, 'Resultado não foi retornado');
        Assert::assertTrue($result['success'], 'API deve retornar success: true');
        Assert::assertIsArray($result['achievements'], 'achievements deve ser um array');
        Assert::assertCount(0, $result['achievements'], 'Deve retornar array vazio quando não há conquistas');
    }



    // ------------ HTML presentation tests ------------
    public function tryToViewAchievementsOnPage(AcceptanceTester $I): void
    {
        $this->currentUser = $this->createUser();
        $this->loginHelper->login($this->currentUser->email, 'password123');
        $I->wait(2);

        $achievements = $this->createAchievementsForUser($this->currentUser, 3);

        $I->amOnPage('/achievements');
        $I->wait(3); // Aguardar JavaScript carregar os dados

        // Verificar se a página HTML foi carregada
        $I->see('Minhas Conquistas', 'h2');
        $I->seeElement('#achievements-list');

        // Verificar se os dados foram renderizados pelo JavaScript
        $I->see($achievements[0]->title, '.achievement-card');
        $I->see($achievements[1]->title, '.achievement-card');
        $I->see($achievements[2]->title, '.achievement-card');

        // Verificar se as imagens estão presentes
        $I->seeElement('img.achievement-image');
    }

    public function tryToViewEmptyStateWhenNoAchievements(AcceptanceTester $I): void
    {
        $this->currentUser = $this->createUser();
        $this->loginHelper->login($this->currentUser->email, 'password123');
        $I->wait(2);

        $I->amOnPage('/achievements');
        $I->wait(3); // Aguardar JavaScript carregar os dados

        // Verificar se a página HTML foi carregada
        $I->see('Minhas Conquistas', 'h2');
        $I->seeElement('#achievements-list');

        // Verificar mensagem de estado vazio (renderizado pelo JavaScript)
        $I->see('Nenhuma conquista ainda', '#achievements-list');
        $I->dontSeeElement('.achievement-card');
    }

    // ------------ security tests ------------
    public function tryToPreventUserFromSeeingOtherUserAchievements(AcceptanceTester $I): void
    {
        // Criar dois usuários
        $this->currentUser = $this->createUser('User 1', 'user1@example.com');
        $this->otherUser = $this->createUser('User 2', 'user2@example.com');

        // Criar conquistas para o outro usuário
        $otherUserAchievements = $this->createAchievementsForUser($this->otherUser, 2);

        // Fazer login com o usuário atual
        $this->loginHelper->login($this->currentUser->email, 'password123');
        $I->wait(2);

        // Fazer requisição AJAX para a API
        $I->executeJS("
            window.apiSecurityResult = null;
            fetch('/achievements', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                window.apiSecurityResult = data;
            })
            .catch(error => { 
                window.apiSecurityResult = { error: error.message }; 
            });
        ");

        $I->wait(2);
        $result = $I->executeJS("return window.apiSecurityResult;");

        Assert::assertNotNull($result, 'Resultado não foi retornado');
        Assert::assertTrue($result['success'], 'API deve retornar success: true');
        Assert::assertIsArray($result['achievements'], 'achievements deve ser um array');

        // Verificar que o usuário atual não vê as conquistas do outro usuário
        Assert::assertCount(0, $result['achievements'], 'Usuário não deve ver conquistas de outro usuário');

        // Verificar que nenhuma das conquistas do outro usuário está presente
        foreach ($otherUserAchievements as $achievement) {
            $found = false;
            foreach ($result['achievements'] as $returnedAchievement) {
                if ($returnedAchievement['id'] === $achievement->id) {
                    $found = true;
                    break;
                }
            }
            Assert::assertFalse($found, "Conquista do outro usuário (ID: {$achievement->id}) não deve ser visível");
        }
    }

    public function tryToVerifyUserOnlySeesOwnAchievementsOnPage(AcceptanceTester $I): void
    {
        // Criar dois usuários
        $this->currentUser = $this->createUser('User 1', 'user1@example.com');
        $this->otherUser = $this->createUser('User 2', 'user2@example.com');

        // Criar conquistas para ambos os usuários com títulos únicos
        $currentUserAchievements = $this->createAchievementsForUser($this->currentUser, 2, 'Minha Conquista');
        $otherUserAchievements = $this->createAchievementsForUser($this->otherUser, 2, 'Outra Conquista');

        // Fazer login com o usuário atual
        $this->loginHelper->login($this->currentUser->email, 'password123');
        $I->wait(2);

        $I->amOnPage('/achievements');
        $I->wait(3); // Aguardar JavaScript carregar os dados

        // Verificar se apenas as conquistas do usuário atual são exibidas
        $I->see($currentUserAchievements[0]->title, '.achievement-card');
        $I->see($currentUserAchievements[1]->title, '.achievement-card');

        // Verificar que as conquistas do outro usuário NÃO são exibidas
        $I->dontSee($otherUserAchievements[0]->title, '.achievement-card');
        $I->dontSee($otherUserAchievements[1]->title, '.achievement-card');
    }
}
