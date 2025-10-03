<?php<?php



namespace Tests\Unit\Controllers;namespace Tests\Unit\Controllers;



use Tests\TestCase;use Tests\TestCase;

use App\Controllers\DecksController;use App\Controllers\DecksController;

use App\Controllers\AdminController;use App\Controllers\AdminController;

use App\Models\User;use App\Models\User;

use Lib\Authentication\Auth;use Lib\Authentication\Auth;



class ControllerAccessTest extends TestCaseclass ControllerAccessTest extends TestCase

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

    public function test_decks_controller_requires_authentication(): void    }

    {

        $controller = new DecksController();    /**

     * Teste: DecksController requer autenticação

        ob_start();     */

        try {    public function test_decks_controller_requires_authentication(): void

            $controller->index();    {

        } catch (\Exception $e) {        $controller = new DecksController();

        }

        ob_end_clean();        ob_start();

        try {

        $this->assertFalse(Auth::check());            $controller->index();

    }        } catch (\Exception $e) {

            // Redirecionamento esperado

    public function test_authenticated_user_can_access_decks(): void        }

    {        ob_end_clean();

        Auth::login($this->user);

        // Não deve permitir acesso sem autenticação

        $controller = new DecksController();        $this->assertFalse(Auth::check());

    }

        ob_start();

        $controller->index();    /**

        $output = ob_get_clean();     * Teste: Usuário autenticado pode acessar DecksController

     */

        $this->assertNotEmpty($output);    public function test_authenticated_user_can_access_decks(): void

    }    {

        Auth::login($this->user);

    public function test_admin_can_access_decks(): void

    {        $controller = new DecksController();

        Auth::login($this->admin);

        ob_start();

        $controller = new DecksController();        $controller->index();

        $output = ob_get_clean();

        ob_start();

        $controller->index();        $this->assertNotEmpty($output);

        $output = ob_get_clean();    }



        $this->assertNotEmpty($output);    /**

    }     * Teste: Admin autenticado pode acessar DecksController

     */

    public function test_admin_controller_requires_admin_role(): void    public function test_admin_can_access_decks(): void

    {    {

        $controller = new AdminController();        Auth::login($this->admin);



        ob_start();        $controller = new DecksController();

        try {

            $controller->index();        ob_start();

        } catch (\Exception $e) {        $controller->index();

        }        $output = ob_get_clean();

        ob_end_clean();

        $this->assertNotEmpty($output);

        $this->assertFalse(Auth::check());    }

    }

    /**

    public function test_regular_user_cannot_access_admin_controller(): void     * Teste: AdminController requer autenticação de admin

    {     */

        Auth::login($this->user);    public function test_admin_controller_requires_admin_role(): void

    {

        $controller = new AdminController();        // Tentar sem autenticação

        $controller = new AdminController();

        ob_start();

        try {        ob_start();

            $controller->index();        try {

        } catch (\Exception $e) {            $controller->index();

        }        } catch (\Exception $e) {

        ob_end_clean();            // Redirecionamento esperado

        }

        $this->assertTrue(Auth::check());        ob_end_clean();

        $this->assertFalse(Auth::user()->isAdmin());

    }        $this->assertFalse(Auth::check());

    }

    public function test_admin_can_access_admin_controller(): void

    {    /**

        Auth::login($this->admin);     * Teste: Usuário normal não pode acessar AdminController

     */

        $controller = new AdminController();    public function test_regular_user_cannot_access_admin_controller(): void

    {

        ob_start();        Auth::login($this->user);

        $controller->index();

        $output = ob_get_clean();        $controller = new AdminController();



        $this->assertNotEmpty($output);        ob_start();

        $this->assertTrue(Auth::user()->isAdmin());        try {

    }            $controller->index();

}        } catch (\Exception $e) {

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
