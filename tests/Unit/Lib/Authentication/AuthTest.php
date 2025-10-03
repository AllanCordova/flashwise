<?php<?php



namespace Tests\Unit\Lib\Authentication;namespace Tests\Unit\Lib\Authentication;



use Lib\Authentication\Auth;use Lib\Authentication\Auth;

use App\Models\User;use App\Models\User;

use Tests\TestCase;use Tests\TestCase;



class AuthTest extends TestCaseclass AuthTest extends TestCase

{{

    private User $user;    private User $user;



    public function setUp(): void    public function setUp(): void

    {    {

        parent::setUp();        parent::setUp();

        $_SESSION = [];        $_SESSION = [];

        $this->user = new User([        $this->user = new User([

            'name' => 'User 1',            'name' => 'User 1',

            'email' => 'fulano@example.com',            'email' => 'fulano@example.com',

            'password' => '123456',            'password' => '123456',

            'password_confirmation' => '123456'            'password_confirmation' => '123456'

        ]);        ]);

        $this->user->save();        $this->user->save();

    }    }



    public function tearDown(): void    public function tearDown(): void

    {    {

        parent::setUp();        parent::setUp();

        $_SESSION = [];        $_SESSION = [];

    }    }



    public function test_login(): void    public function test_login(): void

    {    {

        Auth::login($this->user);        Auth::login($this->user);



        $this->assertEquals(1, $_SESSION['user']['id']);        $this->assertEquals(1, $_SESSION['user']['id']);

    }    }



    public function test_user(): void    public function test_user(): void

    {    {

        Auth::login($this->user);        Auth::login($this->user);



        $userFromSession = Auth::user();        $userFromSession = Auth::user();



        $this->assertEquals($this->user->id, $userFromSession->id);        $this->assertEquals($this->user->id, $userFromSession->id);

    }    }



    public function test_check(): void    public function test_check(): void

    {    {

        Auth::login($this->user);        Auth::login($this->user);



        $this->assertTrue(Auth::check());        $this->assertTrue(Auth::check());

    }    }



    public function test_logout(): void    public function test_logout(): void

    {    {

        Auth::login($this->user);        Auth::login($this->user);

        Auth::logout();        Auth::logout();



        $this->assertFalse(Auth::check());        $this->assertFalse(Auth::check());

    }    }



    public function test_login_stores_user_id_in_session(): void    /**

    {     * 3.2 - Teste de login armazena ID correto na sessão

        Auth::login($this->user);     */

    public function test_login_stores_user_id_in_session(): void

        $this->assertArrayHasKey('user', $_SESSION);    {

        $this->assertArrayHasKey('id', $_SESSION['user']);        Auth::login($this->user);

        $this->assertEquals($this->user->id, $_SESSION['user']['id']);

    }        $this->assertArrayHasKey('user', $_SESSION);

        $this->assertArrayHasKey('id', $_SESSION['user']);

    public function test_check_returns_false_when_not_logged_in(): void        $this->assertEquals($this->user->id, $_SESSION['user']['id']);

    {    }

        $this->assertFalse(Auth::check());

    }    /**

     * 3.2 - Teste de check quando não logado

    public function test_user_returns_null_when_not_logged_in(): void     */

    {    public function test_check_returns_false_when_not_logged_in(): void

        $this->assertNull(Auth::user());    {

    }        $this->assertFalse(Auth::check());

    }

    public function test_logout_removes_user_from_session(): void

    {    /**

        Auth::login($this->user);     * 3.2 - Teste de user() retorna null quando não logado

        $this->assertTrue(Auth::check());     */

    public function test_user_returns_null_when_not_logged_in(): void

        Auth::logout();    {

                $this->assertNull(Auth::user());

        $this->assertFalse(isset($_SESSION['user']['id']));    }

        $this->assertFalse(Auth::check());

    }    /**

     * 3.2 - Teste de logout remove dados da sessão

    public function test_multiple_login_logout_cycles(): void     */

    {    public function test_logout_removes_user_from_session(): void

        Auth::login($this->user);    {

        $this->assertTrue(Auth::check());        Auth::login($this->user);

        Auth::logout();        $this->assertTrue(Auth::check());

        $this->assertFalse(Auth::check());

        Auth::logout();

        Auth::login($this->user);        

        $this->assertTrue(Auth::check());        $this->assertFalse(isset($_SESSION['user']['id']));

        Auth::logout();        $this->assertFalse(Auth::check());

        $this->assertFalse(Auth::check());    }

    }

}    /**

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
