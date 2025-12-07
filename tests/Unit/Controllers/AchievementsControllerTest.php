<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Controllers\AchievementsController;
use App\Models\Achievement;
use App\Models\User;
use Lib\Authentication\Auth;
use Lib\FlashMessage;
use Tests\Unit\Controllers\ControllerTestCase;

class AchievementsControllerTest extends ControllerTestCase
{
    private User $user;

    public function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
        $_SERVER['HTTP_ACCEPT'] = '';

        $this->user = new User([
            'name' => 'Test User',
            'email' => 'user@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'user',
        ]);
        $this->user->save();
    }

    public function tearDown(): void
    {
        $_SESSION = [];
        unset($_SERVER['HTTP_ACCEPT']);
        parent::tearDown();
    }

    public function test_index_should_render_html_view_for_logged_user(): void
    {
        Auth::login($this->user);
        $this->assertTrue(Auth::check());

        $_SERVER['HTTP_ACCEPT'] = 'text/html';
        $output = $this->get('index', AchievementsController::class);

        $this->assertStringNotContainsString('Location:', $output);
        $this->assertEmpty(FlashMessage::get());
    }

    public function test_index_should_return_json_when_accept_json(): void
    {
        Auth::login($this->user);
        $this->assertTrue(Auth::check());

        // Criar algumas conquistas para o usuário
        $achievement1 = new Achievement([
            'user_id' => $this->user->id,
            'title' => 'Test Achievement 1',
            'file_path' => '/assets/images/defaults/avatar.png',
            'file_size' => 1024,
            'mime_type' => 'image/png',
        ]);
        $achievement1->save();

        $achievement2 = new Achievement([
            'user_id' => $this->user->id,
            'title' => 'Test Achievement 2',
            'file_path' => '/assets/images/defaults/avatar.png',
            'file_size' => 1024,
            'mime_type' => 'image/png',
        ]);
        $achievement2->save();

        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        $output = $this->get('index', AchievementsController::class);

        $this->assertStringContainsString('"success":true', $output);
        $this->assertStringContainsString('"achievements"', $output);
        $this->assertStringContainsString('Test Achievement 1', $output);
        $this->assertStringContainsString('Test Achievement 2', $output);
    }

    public function test_index_should_return_empty_achievements_array_when_user_has_no_achievements(): void
    {
        Auth::login($this->user);
        $this->assertTrue(Auth::check());

        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        $output = $this->get('index', AchievementsController::class);

        $this->assertStringContainsString('"success":true', $output);
        $this->assertStringContainsString('"achievements":[]', $output);
    }
}
