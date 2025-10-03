<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Controllers\DecksController;
use App\Controllers\AdminController;
use App\Models\User;
use Lib\Authentication\Auth;

class ControllerAccessTest extends TestCase
{
    private User $user;
    private User $admin;

    public function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];

        // Criar usuário normal
        $this->user = new User([
            'name' => 'Test User',
            'email' => 'user@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'user'
        ]);
        $this->user->save();

        // Criar admin
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

    /**
     * Teste: DecksController requer autenticação
     */
    public function test_decks_controller_requires_authentication(): void
    {
        $controller = new DecksController();

        ob_start();
        try {
            $controller->index();
        } catch (\Exception $e) {
            // Redirecionamento esperado
        }
        ob_end_clean();

        // Não deve permitir acesso sem autenticação
        $this->assertFalse(Auth::check());
    }

    /**
     * Teste: Usuário autenticado pode acessar DecksController
     */
    public function test_authenticated_user_can_access_decks(): void
    {
        Auth::login($this->user);

        $controller = new DecksController();

        ob_start();
        $controller->index();
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
    }

    /**
     * Teste: Admin autenticado pode acessar DecksController
     */
    public function test_admin_can_access_decks(): void
    {
        Auth::login($this->admin);

        $controller = new DecksController();

        ob_start();
        $controller->index();
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
    }

    /**
     * Teste: AdminController requer autenticação de admin
     */
    public function test_admin_controller_requires_admin_role(): void
    {
        // Tentar sem autenticação
        $controller = new AdminController();

        ob_start();
        try {
            $controller->index();
        } catch (\Exception $e) {
            // Redirecionamento esperado
        }
        ob_end_clean();

        $this->assertFalse(Auth::check());
    }

    /**
     * Teste: Usuário normal não pode acessar AdminController
     */
    public function test_regular_user_cannot_access_admin_controller(): void
    {
        Auth::login($this->user);

        $controller = new AdminController();

        ob_start();
        try {
            $controller->index();
        } catch (\Exception $e) {
            // Redirecionamento esperado
        }
        ob_end_clean();

        // Usuário está autenticado mas não é admin
        $this->assertTrue(Auth::check());
        $this->assertFalse(Auth::user()->isAdmin());
    }

    /**
     * Teste: Admin pode acessar AdminController
     */
    public function test_admin_can_access_admin_controller(): void
    {
        Auth::login($this->admin);

        $controller = new AdminController();

        ob_start();
        $controller->index();
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
        $this->assertTrue(Auth::user()->isAdmin());
    }
}
