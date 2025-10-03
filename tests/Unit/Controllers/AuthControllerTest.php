<?php<?php



namespace Tests\Unit\Controllers;namespace Tests\Unit\Controllers;



use Tests\TestCase;use Tests\TestCase;

use App\Controllers\AuthController;use App\Controllers\AuthController;

use App\Models\User;use App\Models\User;

use Lib\Authentication\Auth;use Lib\Authentication\Auth;

use Core\Http\Request;use Core\Http\Request;



class AuthControllerTest extends TestCaseclass AuthControllerTest extends TestCase

{{

    private User $user;    private User $user;

    private User $admin;    private User $admin;



    public function setUp(): void    public function setUp(): void

    {    {

        parent::setUp();        parent::setUp();

        $_SESSION = [];        $_SESSION = [];



        $this->user = new User([        // Criar usuário normal

            'name' => 'Test User',        $this->user = new User([

            'email' => 'user@test.com',            'name' => 'Test User',

            'password' => 'password123',            'email' => 'user@test.com',

            'password_confirmation' => 'password123',            'password' => 'password123',

            'role' => 'user'            'password_confirmation' => 'password123',

        ]);            'role' => 'user'

        $this->user->save();        ]);

        $this->user->save();

        $this->admin = new User([

            'name' => 'Test Admin',        // Criar admin

            'email' => 'admin@test.com',        $this->admin = new User([

            'password' => 'admin123',            'name' => 'Test Admin',

            'password_confirmation' => 'admin123',            'email' => 'admin@test.com',

            'role' => 'admin'            'password' => 'admin123',

        ]);            'password_confirmation' => 'admin123',

        $this->admin->save();            'role' => 'admin'

    }        ]);

        $this->admin->save();

    public function tearDown(): void    }

    {

        $_SESSION = [];    public function tearDown(): void

        parent::tearDown();    {

    }        $_SESSION = [];

        parent::tearDown();

    public function test_new_renders_login_form(): void    }

    {

        $controller = new AuthController();    /**

             * Teste do método new() - exibe formulário de login

        ob_start();     */

        $controller->new();    public function test_new_renders_login_form(): void

        $output = ob_get_clean();    {

        $controller = new AuthController();

        $this->assertNotEmpty($output);        

    }        ob_start();

        $controller->new();

    public function test_create_with_valid_user_credentials(): void        $output = ob_get_clean();

    {

        $_POST['user'] = [        // Verifica se algum conteúdo foi renderizado

            'email' => 'user@test.com',        $this->assertNotEmpty($output);

            'password' => 'password123'    }

        ];

    /**

        $request = new Request();     * Teste de autenticação com credenciais válidas (usuário)

        $controller = new AuthController();     */

    public function test_create_with_valid_user_credentials(): void

        ob_start();    {

        try {        $_POST['user'] = [

            $controller->create($request);            'email' => 'user@test.com',

        } catch (\Exception $e) {            'password' => 'password123'

        }        ];

        ob_end_clean();

        $request = new Request();

        $this->assertTrue(Auth::check());        $controller = new AuthController();

        $this->assertEquals('user@test.com', Auth::user()->email);

    }        // Captura o redirecionamento

        ob_start();

    public function test_create_with_valid_admin_credentials(): void        try {

    {            $controller->create($request);

        $_POST['user'] = [        } catch (\Exception $e) {

            'email' => 'admin@test.com',            // Redirecionamento causa exit, esperado

            'password' => 'admin123'        }

        ];        ob_end_clean();



        $request = new Request();        // Verifica se o usuário foi autenticado

        $controller = new AuthController();        $this->assertTrue(Auth::check());

        $this->assertEquals('user@test.com', Auth::user()->email);

        ob_start();    }

        try {

            $controller->create($request);    /**

        } catch (\Exception $e) {     * Teste de autenticação com credenciais válidas (admin)

        }     */

        ob_end_clean();    public function test_create_with_valid_admin_credentials(): void

    {

        $this->assertTrue(Auth::check());        $_POST['user'] = [

        $this->assertEquals('admin@test.com', Auth::user()->email);            'email' => 'admin@test.com',

        $this->assertTrue(Auth::user()->isAdmin());            'password' => 'admin123'

    }        ];



    public function test_create_with_invalid_credentials(): void        $request = new Request();

    {        $controller = new AuthController();

        $_POST['user'] = [

            'email' => 'user@test.com',        ob_start();

            'password' => 'wrongpassword'        try {

        ];            $controller->create($request);

        } catch (\Exception $e) {

        $request = new Request();            // Redirecionamento esperado

        $controller = new AuthController();        }

        ob_end_clean();

        ob_start();

        try {        $this->assertTrue(Auth::check());

            $controller->create($request);        $this->assertEquals('admin@test.com', Auth::user()->email);

        } catch (\Exception $e) {        $this->assertTrue(Auth::user()->isAdmin());

        }    }

        ob_end_clean();

    /**

        $this->assertFalse(Auth::check());     * Teste de autenticação com credenciais inválidas

    }     */

    public function test_create_with_invalid_credentials(): void

    public function test_destroy_logs_out_user(): void    {

    {        $_POST['user'] = [

        Auth::login($this->user);            'email' => 'user@test.com',

        $this->assertTrue(Auth::check());            'password' => 'wrongpassword'

        ];

        $controller = new AuthController();

        $request = new Request();

        ob_start();        $controller = new AuthController();

        try {

            $controller->destroy();        ob_start();

        } catch (\Exception $e) {        try {

        }            $controller->create($request);

        ob_end_clean();        } catch (\Exception $e) {

            // Redirecionamento esperado

        $this->assertFalse(Auth::check());        }

    }        ob_end_clean();

}

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
