<?php

namespace Tests\Unit\Models\Auth;

use App\Models\Auth;
use App\Models\User;
use Tests\TestCase;

class AuthTest extends TestCase
{
    private User $user;

    public function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];

        $this->user = new User([
            'name' => 'User 1',
            'email' => 'fulano@example.com',
            'password' => '123456',
            'password_confirmation' => '123456'
        ]);
        $this->user->save();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $_SESSION = [];
    }

    public function test_attemptLogin_should_return_true_with_correct_credentials(): void
    {
        $this->assertTrue(Auth::attemptLogin('fulano@example.com', '123456'));
        $this->assertEquals($this->user->id, $_SESSION['user_id']);
    }

    public function test_attemptLogin_should_return_false_with_incorrect_password(): void
    {
        $this->assertFalse(Auth::attemptLogin('fulano@example.com', 'wrongpassword'));
        $this->assertArrayNotHasKey('user_id', $_SESSION);
    }

    public function test_attemptLogin_should_return_false_for_non_existent_user(): void
    {
        $this->assertFalse(Auth::attemptLogin('nonexistent@example.com', '123456'));
        $this->assertArrayNotHasKey('user_id', $_SESSION);
    }

    public function test_logout_should_destroy_session(): void
    {
        Auth::attemptLogin('fulano@example.com', '123456');
        $this->assertEquals($this->user->id, $_SESSION['user_id']);

        Auth::logout();
        $this->assertArrayNotHasKey('user_id', $_SESSION);
    }
}
