<?php

namespace Tests\Unit\Lib\Authentication;

use Lib\Authentication\Auth;
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
        parent::setUp();
        $_SESSION = [];
    }

    public function test_login(): void
    {
        Auth::login($this->user);

        $this->assertEquals(1, $_SESSION['user']['id']);
    }

    public function test_user(): void
    {
        Auth::login($this->user);

        $userFromSession = Auth::user();

        $this->assertEquals($this->user->id, $userFromSession->id);
    }

    public function test_check(): void
    {
        Auth::login($this->user);

        $this->assertTrue(Auth::check());
    }

    public function test_logout(): void
    {
        Auth::login($this->user);
        Auth::logout();

        $this->assertFalse(Auth::check());
    }

    /**
     * 3.2 - Teste de login armazena ID correto na sessão
     */
    public function test_login_stores_user_id_in_session(): void
    {
        Auth::login($this->user);

        $this->assertArrayHasKey('user', $_SESSION);
        $this->assertArrayHasKey('id', $_SESSION['user']);
        $this->assertEquals($this->user->id, $_SESSION['user']['id']);
    }

    /**
     * 3.2 - Teste de check quando não logado
     */
    public function test_check_returns_false_when_not_logged_in(): void
    {
        $this->assertFalse(Auth::check());
    }

    /**
     * 3.2 - Teste de user() retorna null quando não logado
     */
    public function test_user_returns_null_when_not_logged_in(): void
    {
        $this->assertNull(Auth::user());
    }

    /**
     * 3.2 - Teste de logout remove dados da sessão
     */
    public function test_logout_removes_user_from_session(): void
    {
        Auth::login($this->user);
        $this->assertTrue(Auth::check());

        Auth::logout();
        
        $this->assertFalse(isset($_SESSION['user']['id']));
        $this->assertFalse(Auth::check());
    }

    /**
     * 3.2 - Teste de múltiplos logins/logouts
     */
    public function test_multiple_login_logout_cycles(): void
    {
        // Primeiro ciclo
        Auth::login($this->user);
        $this->assertTrue(Auth::check());
        Auth::logout();
        $this->assertFalse(Auth::check());

        // Segundo ciclo
        Auth::login($this->user);
        $this->assertTrue(Auth::check());
        Auth::logout();
        $this->assertFalse(Auth::check());
    }
}
