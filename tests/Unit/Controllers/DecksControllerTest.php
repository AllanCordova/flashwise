<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Controllers\DecksController;
use App\Models\User;
use Lib\Authentication\Auth;
use Lib\FlashMessage;
use Tests\Unit\Controllers\ControllerTestCase;

class DecksControllerTest extends ControllerTestCase
{
    private User $user;

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
    }

    public function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    public function test_index_should_redirect_guest_to_login(): void
    {
        $output = $this->get('index', DecksController::class);
        $messages = FlashMessage::get();

        $this->assertStringContainsString('Location: /login', $output);
        $this->assertArrayHasKey('danger', $messages);
        $this->assertEquals('Você precisa estar logado para acessar esta página.', $messages['danger']);
        $this->assertFalse(Auth::check());
    }

    public function test_index_should_render_view_for_logged_in_user(): void
    {
        Auth::login($this->user);
        $this->assertTrue(Auth::check());

        $output = $this->get('index', DecksController::class);

        $this->assertStringNotContainsString('Location:', $output);
        $this->assertEmpty(FlashMessage::get());
    }
}
