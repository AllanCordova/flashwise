<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Controllers\AdminController;
use App\Models\User;
use Lib\Authentication\Auth;
use Lib\FlashMessage;

class AdminControllerTest extends ControllerTestCase
{
    private User $user;
    private User $admin;

    public function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];

        $this->user = new User([
            'name' => 'Test User',
            'email' => 'user@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'user'
        ]);
        $this->user->save();

        $this->admin = new User([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => 'admin123',
            'password_confirmation' => 'admin123',
            'role' => 'admin'
        ]);
        $this->admin->save();
    }

    public function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    public function test_index_should_redirect_guest_to_login(): void
    {
        $output = $this->get('index', AdminController::class);
        $messages = FlashMessage::get();

        $this->assertStringContainsString('Location: /login', $output);
        $this->assertArrayHasKey('danger', $messages);
        $this->assertEquals('Você precisa estar logado para acessar esta página.', $messages['danger']);
        $this->assertFalse(Auth::check());
    }

    public function test_index_should_redirect_non_admin_user(): void
    {
        Auth::login($this->user);
        $this->assertTrue(Auth::check());

        $output = $this->get('index', AdminController::class);
        $messages = FlashMessage::get();

        $this->assertStringContainsString('Location: /', $output);
        $this->assertArrayHasKey('danger', $messages);
        $this->assertEquals('Acesso negado! Apenas administradores podem acessar esta área.', $messages['danger']);
    }

    public function test_index_should_render_view_for_admin_user(): void
    {
        Auth::login($this->admin);
        $this->assertTrue(Auth::check());

        $output = $this->get('index', AdminController::class);

        $this->assertStringNotContainsString('Location:', $output);
        $this->assertEmpty(FlashMessage::get());
    }
}
