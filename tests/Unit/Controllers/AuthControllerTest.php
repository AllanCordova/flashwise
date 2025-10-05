<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Controllers\AuthController;
use App\Models\User;
use Lib\Authentication\Auth;
use Lib\FlashMessage;

class AuthControllerTest extends ControllerTestCase
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
        ]);
        $this->user->save();
    }

    public function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    public function test_new_should_render_login_form_for_guest(): void
    {
        $output = $this->get('new', AuthController::class);

        $this->assertStringNotContainsString('Location:', $output);
    }

    public function test_new_should_redirect_logged_in_user_to_home(): void
    {
        Auth::login($this->user);
        $this->assertTrue(Auth::check());

        $output = $this->get('new', AuthController::class);

        $this->assertStringContainsString('Location: /', $output);
    }

    public function test_create_should_login_with_valid_credentials(): void
    {
        $params = [
            'user' => [
                'email' => 'user@test.com',
                'password' => 'password123'
            ]
        ];

        $output = $this->post('create', AuthController::class, $params);
        $messages = FlashMessage::get();

        $this->assertTrue(Auth::check());
        $this->assertEquals('user@test.com', Auth::user()->email);
        $this->assertStringContainsString('Location: /', $output);
        $this->assertArrayHasKey('success', $messages);
        $this->assertStringContainsString('Login realizado com sucesso', $messages['success']);
    }

    public function test_create_should_fail_with_invalid_password(): void
    {
        $params = [
            'user' => [
                'email' => 'user@test.com',
                'password' => 'wrong-password'
            ]
        ];

        $output = $this->post('create', AuthController::class, $params);
        $messages = FlashMessage::get();

        $this->assertFalse(Auth::check());
        $this->assertStringContainsString('Location: /login', $output);
        $this->assertArrayHasKey('danger', $messages);
        $this->assertEquals('E-mail ou senha inválidos. Por favor, tente novamente.', $messages['danger']);
    }

    public function test_create_should_fail_with_non_existent_email(): void
    {
        $params = [
            'user' => [
                'email' => 'not.found@example.com',
                'password' => 'any-password'
            ]
        ];

        $output = $this->post('create', AuthController::class, $params);
        $messages = FlashMessage::get();

        $this->assertFalse(Auth::check());
        $this->assertStringContainsString('Location: /login', $output);
        $this->assertArrayHasKey('danger', $messages);
    }

    public function test_destroy_should_log_out_the_user(): void
    {
        Auth::login($this->user);
        $this->assertTrue(Auth::check());

        $output = $this->get('destroy', AuthController::class);
        $messages = FlashMessage::get();

        $this->assertFalse(Auth::check());
        $this->assertStringContainsString('Location: /login', $output);
        $this->assertArrayHasKey('success', $messages);
        $this->assertEquals('Você foi desconectado com segurança.', $messages['success']);
    }
}
