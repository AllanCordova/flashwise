<?php

namespace Tests\Unit\Models\Achievements;

use App\Models\Achievement;
use App\Models\User;
use Tests\TestCase;

class AchievementTest extends TestCase
{
    private ?User $testUser = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->testUser = new User([
            'name' => 'Test User',
            'email' => 'testachievement@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'user',
        ]);
        $this->testUser->save();
    }

    public function test_should_create_achievement_with_valid_data(): void
    {
        $achievement = new Achievement([
            'user_id' => $this->testUser->id,
            'title' => 'Test Achievement',
            'file_path' => '/assets/images/defaults/avatar.png',
            'file_size' => 1024,
            'mime_type' => 'image/png',
        ]);

        $this->assertTrue($achievement->save());
        $this->assertNull($achievement->errors('user_id'));
        $this->assertNull($achievement->errors('title'));
        $this->assertNull($achievement->errors('file_path'));

        $this->assertGreaterThan(0, $achievement->id);
        $this->assertEquals('Test Achievement', $achievement->title);
        $this->assertEquals($this->testUser->id, $achievement->user_id);
    }

    public function test_should_not_save_with_invalid_data(): void
    {
        $achievement = new Achievement();

        $this->assertFalse($achievement->save());
        $this->assertTrue($achievement->hasErrors());
        $this->assertEquals('não pode ser vazio!', $achievement->errors('user_id'));
        $this->assertEquals('não pode ser vazio!', $achievement->errors('title'));
        $this->assertEquals('não pode ser vazio!', $achievement->errors('file_path'));
    }

    public function test_should_fail_if_user_id_is_empty(): void
    {
        $achievement = new Achievement([
            'user_id' => null,
            'title' => 'Valid Title',
            'file_path' => '/assets/images/defaults/avatar.png',
            'file_size' => 1024,
            'mime_type' => 'image/png',
        ]);

        $this->assertFalse($achievement->save());
        $this->assertTrue($achievement->hasErrors());
        $this->assertEquals('não pode ser vazio!', $achievement->errors('user_id'));
    }

    public function test_should_fail_if_title_is_empty(): void
    {
        $achievement = new Achievement([
            'user_id' => $this->testUser->id,
            'title' => '',
            'file_path' => '/assets/images/defaults/avatar.png',
            'file_size' => 1024,
            'mime_type' => 'image/png',
        ]);

        $this->assertFalse($achievement->save());
        $this->assertTrue($achievement->hasErrors());
        $this->assertEquals('não pode ser vazio!', $achievement->errors('title'));
    }

    public function test_should_fail_if_file_path_is_empty(): void
    {
        $achievement = new Achievement([
            'user_id' => $this->testUser->id,
            'title' => 'Valid Title',
            'file_path' => '',
            'file_size' => 1024,
            'mime_type' => 'image/png',
        ]);

        $this->assertFalse($achievement->save());
        $this->assertTrue($achievement->hasErrors());
        $this->assertEquals('não pode ser vazio!', $achievement->errors('file_path'));
    }

    public function test_should_fail_if_file_size_exceeds_limit(): void
    {
        $achievement = new Achievement([
            'user_id' => $this->testUser->id,
            'title' => 'Valid Title',
            'file_path' => '/assets/images/defaults/avatar.png',
            'file_size' => 20971521, // 20MB + 1 byte
            'mime_type' => 'image/png',
        ]);

        $this->assertFalse($achievement->save());
        $this->assertTrue($achievement->hasErrors());
    }

    public function test_should_fail_if_mime_type_is_not_allowed(): void
    {
        $achievement = new Achievement([
            'user_id' => $this->testUser->id,
            'title' => 'Valid Title',
            'file_path' => '/assets/images/defaults/avatar.png',
            'file_size' => 1024,
            'mime_type' => 'application/pdf', // Não permitido, apenas image/png e image/jpeg
        ]);

        $this->assertFalse($achievement->save());
        $this->assertTrue($achievement->hasErrors());
    }

    public function test_achievement_can_be_found_by_id(): void
    {
        $achievement = new Achievement([
            'user_id' => $this->testUser->id,
            'title' => 'Findable Achievement',
            'file_path' => '/assets/images/defaults/avatar.png',
            'file_size' => 1024,
            'mime_type' => 'image/png',
        ]);
        $achievement->save();

        $foundAchievement = Achievement::findById($achievement->id);

        $this->assertNotNull($foundAchievement);
        $this->assertInstanceOf(Achievement::class, $foundAchievement);
        $this->assertEquals($achievement->id, $foundAchievement->id);
        $this->assertEquals('Findable Achievement', $foundAchievement->title);
    }

    public function test_find_by_id_should_return_null_if_not_found(): void
    {
        $foundAchievement = Achievement::findById(99999);
        $this->assertNull($foundAchievement);
    }

    public function test_achievement_belongs_to_user(): void
    {
        $achievement = new Achievement([
            'user_id' => $this->testUser->id,
            'title' => 'User Achievement',
            'file_path' => '/assets/images/defaults/avatar.png',
            'file_size' => 1024,
            'mime_type' => 'image/png',
        ]);
        $achievement->save();

        $user = $achievement->user();

        $this->assertInstanceOf(\Core\Database\ActiveRecord\BelongsTo::class, $user);
        $foundUser = $user->get();
        $this->assertInstanceOf(User::class, $foundUser);
        $this->assertEquals($this->testUser->id, $foundUser->id);
    }

    public function test_achievement_can_be_found_with_where_clause(): void
    {
        $achievement = new Achievement([
            'user_id' => $this->testUser->id,
            'title' => 'Searchable Achievement',
            'file_path' => '/assets/images/defaults/avatar.png',
            'file_size' => 1024,
            'mime_type' => 'image/png',
        ]);
        $achievement->save();

        $foundAchievements = Achievement::where(['title' => 'Searchable Achievement']);

        $this->assertNotEmpty($foundAchievements);
        $this->assertEquals('Searchable Achievement', $foundAchievements[0]->title);
    }
}
