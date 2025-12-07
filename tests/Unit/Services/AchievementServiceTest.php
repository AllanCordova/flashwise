<?php

namespace Tests\Unit\Services;

use App\Models\Achievement;
use App\Models\User;
use App\Services\AchievementService;
use Core\Constants\Constants;
use Tests\TestCase;

class AchievementServiceTest extends TestCase
{
    private ?User $testUser = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->testUser = new User([
            'name' => 'Test User',
            'email' => 'testservice@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'user',
        ]);
        $this->testUser->save();

        // Garantir que a imagem padrão existe para os testes
        $this->ensureDefaultImageExists();
    }

    public function tearDown(): void
    {
        // Limpar conquistas criadas durante os testes
        $achievements = Achievement::where(['user_id' => $this->testUser->id]);
        foreach ($achievements as $achievement) {
            $achievement->destroy();
        }
        parent::tearDown();
    }

    private function ensureDefaultImageExists(): void
    {
        $imagePath = '/assets/images/defaults/avatar.png';
        $absoluteImagePath = Constants::rootPath()->join('public' . $imagePath);
        $dir = dirname($absoluteImagePath);

        if (!is_dir($dir)) {
            $oldUmask = umask(0);
            mkdir($dir, 0777, true);
            umask($oldUmask);
        }

        if (!file_exists($absoluteImagePath)) {
            // Criar uma imagem PNG simples para teste (1x1 pixel transparente)
            $oldUmask = umask(0);
            file_put_contents(
                $absoluteImagePath,
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==')
            );
            chmod($absoluteImagePath, 0666);
            umask($oldUmask);
        }
    }

    public function test_createIfNotExists_should_create_achievement_with_valid_data(): void
    {
        $title = 'Primeiro Deck';
        $imagePath = '/assets/images/defaults/avatar.png';

        $achievement = AchievementService::createIfNotExists($this->testUser, $title, $imagePath);

        $this->assertNotNull($achievement);
        $this->assertInstanceOf(Achievement::class, $achievement);
        $this->assertGreaterThan(0, $achievement->id);
        $this->assertEquals($title, $achievement->title);
        $this->assertEquals($this->testUser->id, $achievement->user_id);
        $this->assertEquals($imagePath, $achievement->file_path);
        $this->assertNotEmpty($achievement->file_size);
        $this->assertNotEmpty($achievement->mime_type);
    }

    public function test_createIfNotExists_should_return_null_if_achievement_already_exists(): void
    {
        $title = 'Conquista Duplicada';
        $imagePath = '/assets/images/defaults/avatar.png';

        // Criar primeira conquista
        $firstAchievement = AchievementService::createIfNotExists($this->testUser, $title, $imagePath);
        $this->assertNotNull($firstAchievement);

        // Tentar criar novamente com mesmo título
        $secondAchievement = AchievementService::createIfNotExists($this->testUser, $title, $imagePath);

        $this->assertNull($secondAchievement, 'Não deve criar conquista duplicada');

        // Verificar que apenas uma conquista existe
        $achievements = Achievement::where([
            'user_id' => $this->testUser->id,
            'title' => $title
        ]);
        $this->assertCount(1, $achievements);
    }

    public function test_createIfNotExists_should_use_default_image_when_specified_image_not_exists(): void
    {
        $title = 'Conquista com Imagem Padrão';
        $nonExistentImagePath = '/assets/images/nonexistent/image.png';

        $achievement = AchievementService::createIfNotExists($this->testUser, $title, $nonExistentImagePath);

        $this->assertNotNull($achievement);
        $this->assertEquals('/assets/images/defaults/avatar.png', $achievement->file_path);
    }

    public function test_createIfNotExists_should_return_null_when_both_images_not_exist(): void
    {
        // Remover imagem padrão temporariamente
        $defaultImagePath = Constants::rootPath()->join('public/assets/images/defaults/avatar.png');
        $backupExists = file_exists($defaultImagePath);
        $backupContent = $backupExists ? file_get_contents($defaultImagePath) : null;

        if ($backupExists) {
            unlink($defaultImagePath);
        }

        $title = 'Conquista Sem Imagem';
        $nonExistentImagePath = '/assets/images/nonexistent/image.png';

        $achievement = AchievementService::createIfNotExists($this->testUser, $title, $nonExistentImagePath);

        $this->assertNull($achievement, 'Deve retornar null quando nenhuma imagem existe');

        // Restaurar imagem padrão se existia
        if ($backupExists && $backupContent !== null) {
            file_put_contents($defaultImagePath, $backupContent);
        } else {
            // Recriar imagem padrão para outros testes
            $this->ensureDefaultImageExists();
        }
    }

    public function test_createIfNotExists_should_allow_different_users_have_same_title(): void
    {
        $otherUser = new User([
            'name' => 'Other User',
            'email' => 'otheruser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'user',
        ]);
        $otherUser->save();

        $title = 'Conquista Compartilhada';
        $imagePath = '/assets/images/defaults/avatar.png';

        // Criar conquista para primeiro usuário
        $achievement1 = AchievementService::createIfNotExists($this->testUser, $title, $imagePath);
        $this->assertNotNull($achievement1);

        // Criar conquista com mesmo título para outro usuário
        $achievement2 = AchievementService::createIfNotExists($otherUser, $title, $imagePath);
        $this->assertNotNull($achievement2, 'Usuários diferentes podem ter conquistas com mesmo título');

        $this->assertNotEquals($achievement1->id, $achievement2->id);
        $this->assertEquals($this->testUser->id, $achievement1->user_id);
        $this->assertEquals($otherUser->id, $achievement2->user_id);

        // Limpar
        $achievement2->destroy();
        $otherUser->destroy();
    }

    public function test_checkFirstDeckAchievement_should_create_first_deck_achievement(): void
    {
        $achievement = AchievementService::checkFirstDeckAchievement($this->testUser);

        $this->assertNotNull($achievement);
        $this->assertEquals('Primeiro Deck', $achievement->title);
        $this->assertEquals($this->testUser->id, $achievement->user_id);
    }

    public function test_checkFirstDeckAchievement_should_not_create_duplicate(): void
    {
        // Criar primeira vez
        $firstAchievement = AchievementService::checkFirstDeckAchievement($this->testUser);
        $this->assertNotNull($firstAchievement);

        // Tentar criar novamente
        $secondAchievement = AchievementService::checkFirstDeckAchievement($this->testUser);
        $this->assertNull($secondAchievement, 'Não deve criar conquista duplicada');
    }
}
