<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Controllers\AuthController;
use App\Models\User;
use Lib\Authentication\Auth;

class AuthControllerTest extends ControllerTestCase
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

    public function test_new_renders_login_form(): void
    {
        $output = $this->get('new', AuthController::class);
        $this->assertNotEmpty($output);
    }

    public function test_create_with_valid_user_credentials(): void
    {
        $params = [
            'user' => [
                'email' => 'user@test.com',
                'password' => 'password123'
            ]
        ];

        $this->post('create', AuthController::class, $params);

        $this->assertTrue(Auth::check());
        $this->assertEquals('user@test.com', Auth::user()->email);
    }

    public function test_create_with_valid_admin_credentials(): void
    {
        $params = [
            'user' => [
                'email' => 'admin@test.com',
                'password' => 'admin123'
            ]
        ];

        $this->post('create', AuthController::class, $params);

        $this->assertTrue(Auth::check());
        $this->assertEquals('admin@test.com', Auth::user()->email);
        $this->assertTrue(Auth::user()->isAdmin());
    }

    public function test_create_with_invalid_credentials(): void
    {
        $params = [
            'user' => [
                'email' => 'user@test.com',
                'password' => 'wrongpassword'
            ]
        ];

        $this->post('create', AuthController::class, $params);

        $this->assertFalse(Auth::check());
    }



    public function test_destroy_logs_out_user(): void
    {
        Auth::login($this->user);
        $this->assertTrue(Auth::check());

        $this->get('destroy', AuthController::class);

        $this->assertFalse(Auth::check());
    }
}
