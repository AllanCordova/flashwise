<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Controllers\AuthController;
use App\Models\User;
use Core\Http\Request;
use Lib\Authentication\Auth;
use Tests\TestCase;

class AuthControllerTest extends TestCase
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
     * Teste do método new() - exibe formulário de login
     */
    public function test_new_renders_login_form(): void
    {
        $controller = new AuthController();
        ob_start();
        $controller->new();
        $output = ob_get_clean();

        // Verifica se algum conteúdo foi renderizado
        $this->assertNotEmpty($output);
    }

    /**
     * Teste de autenticação com credenciais válidas (usuário)
     */
    public function test_create_with_valid_user_credentials(): void
    {
        $_POST['user'] = [
            'email' => 'user@test.com',
            'password' => 'password123'
        ];
        $request = new Request();
        $controller = new AuthController();

        // Captura o redirecionamento
        ob_start();
        try {
            $controller->create($request);
        } catch (\Exception $e) {
            // Redirecionamento causa exit, esperado
        }
        ob_end_clean();

        // Verifica se o usuário foi autenticado
        $this->assertTrue(Auth::check());
        $this->assertEquals('user@test.com', Auth::user()->email);
    }

    /**
     * Teste de autenticação com credenciais válidas (admin)
     */
    public function test_create_with_valid_admin_credentials(): void
    {
        $_POST['user'] = [
            'email' => 'admin@test.com',
            'password' => 'admin123'
        ];
        $request = new Request();
        $controller = new AuthController();
        ob_start();
        try {
            $controller->create($request);
        } catch (\Exception $e) {
            // Redirecionamento esperado
        }
        ob_end_clean();
        $this->assertTrue(Auth::check());
        $this->assertEquals('admin@test.com', Auth::user()->email);
        $this->assertTrue(Auth::user()->isAdmin());
    }

    /**
     * Teste de autenticação com credenciais inválidas
     */
    public function test_create_with_invalid_credentials(): void
    {
        $_POST['user'] = [
            'email' => 'user@test.com',
            'password' => 'wrongpassword'
        ];
        $request = new Request();
        $controller = new AuthController();
        ob_start();
        try {
            $controller->create($request);
        } catch (\Exception $e) {
            // Redirecionamento esperado
        }
        ob_end_clean();

        // Não deve estar autenticado
        $this->assertFalse(Auth::check());
    }

    /**
     * Teste do método destroy() - logout
     */
    public function test_destroy_logs_out_user(): void
    {
        // Fazer login primeiro
        Auth::login($this->user);
        $this->assertTrue(Auth::check());
        $controller = new AuthController();
        ob_start();
        try {
            $controller->destroy();
        } catch (\Exception $e) {
            // Redirecionamento esperado
        }
        ob_end_clean();

        // Deve ter feito logout
        $this->assertFalse(Auth::check());
    }
}