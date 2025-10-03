<?php<?php



namespace Tests\Unit\Models\Users;namespace Tests\Unit\Models\Users;



use App\Models\User;use App\Models\User;

use Tests\TestCase;use Tests\TestCase;



class UserTest extends TestCaseclass UserTest extends TestCase

{{

    private User $user;    private User $user;

    private User $user2;    private User $user2;



    public function setUp(): void    public function setUp(): void

    {    {

        parent::setUp();        parent::setUp();



        $this->user = new User([        $this->user = new User([

            'name' => 'User 1',            'name' => 'User 1',

            'email' => 'fulano@example.com',            'email' => 'fulano@example.com',

            'password' => '123456',            'password' => '123456',

            'password_confirmation' => '123456'            'password_confirmation' => '123456'

        ]);        ]);

        $this->user->save();        $this->user->save();



        $this->user2 = new User([        $this->user2 = new User([

            'name' => 'User 2',            'name' => 'User 2',

            'email' => 'fulano1@example.com',            'email' => 'fulano1@example.com',

            'password' => '123456',            'password' => '123456',

            'password_confirmation' => '123456'            'password_confirmation' => '123456'

        ]);        ]);

        $this->user2->save();        $this->user2->save();

    }    }



    public function test_should_create_new_user(): void    public function test_should_create_new_user(): void

    {    {

        $this->assertCount(2, User::all());        $this->assertCount(2, User::all());

    }    }



    public function test_all_should_return_all_users(): void    public function test_all_should_return_all_users(): void

    {    {

        $this->user2->save();        $this->user2->save();



        $users[] = $this->user->id;        $users[] = $this->user->id;

        $users[] = $this->user2->id;        $users[] = $this->user2->id;



        $all = array_map(fn($user) => $user->id, User::all());        $all = array_map(fn($user) => $user->id, User::all());



        $this->assertCount(2, $all);        $this->assertCount(2, $all);

        $this->assertEquals($users, $all);        $this->assertEquals($users, $all);

    }    }



    public function test_destroy_should_remove_the_user(): void    public function test_destroy_should_remove_the_user(): void

    {    {

        $this->user->destroy();        $this->user->destroy();

        $this->assertCount(1, User::all());        $this->assertCount(1, User::all());

    }    }



    public function test_set_id(): void    public function test_set_id(): void

    {    {

        $this->user->id = 10;        $this->user->id = 10;

        $this->assertEquals(10, $this->user->id);        $this->assertEquals(10, $this->user->id);

    }    }



    public function test_set_name(): void    public function test_set_name(): void

    {    {

        $this->user->name = 'User name';        $this->user->name = 'User name';

        $this->assertEquals('User name', $this->user->name);        $this->assertEquals('User name', $this->user->name);

    }    }



    public function test_set_email(): void    public function test_set_email(): void

    {    {

        $this->user->email = 'outro@example.com';        $this->user->email = 'outro@example.com';

        $this->assertEquals('outro@example.com', $this->user->email);        $this->assertEquals('outro@example.com', $this->user->email);

    }    }



    public function test_errors_should_return_errors(): void    public function test_errors_should_return_errors(): void

    {    {

        $user = new User();        $user = new User();



        $this->assertFalse($user->isValid());        $this->assertFalse($user->isValid());

        $this->assertFalse($user->save());        $this->assertFalse($user->save());

        $this->assertTrue($user->hasErrors());        $this->assertTrue($user->hasErrors());



        $this->assertEquals('não pode ser vazio!', $user->errors('name'));        $this->assertEquals('não pode ser vazio!', $user->errors('name'));

        $this->assertEquals('não pode ser vazio!', $user->errors('email'));        $this->assertEquals('não pode ser vazio!', $user->errors('email'));

    }    }



    public function test_errors_should_return_password_confirmation_error(): void    public function test_errors_should_return_password_confirmation_error(): void

    {    {

        $user = new User([        $user = new User([

            'name' => 'User 3',            'name' => 'User 3',

            'email' => 'fulano3@example.com',            'email' => 'fulano3@example.com',

            'password' => '123456',            'password' => '123456',

            'password_confirmation' => '1234567'            'password_confirmation' => '1234567'

        ]);        ]);



        $this->assertFalse($user->isValid());        $this->assertFalse($user->isValid());

        $this->assertFalse($user->save());        $this->assertFalse($user->save());



        $this->assertEquals('as senhas devem ser idênticas!', $user->errors('password'));        $this->assertEquals('as senhas devem ser idênticas!', $user->errors('password'));

    }    }



    public function test_find_by_id_should_return_the_user(): void    /**

    {     * 3.1 - Teste do método authenticate() do modelo User

        $this->assertEquals($this->user->id, User::findById($this->user->id)->id);     */

    }    public function test_authenticate_should_verify_password_correctly(): void

    {

    public function test_find_by_id_should_return_null(): void        // Senha correta

    {        $this->assertTrue($this->user->authenticate('123456'));

        $this->assertNull(User::findById(3));        

    }        // Senha incorreta

        $this->assertFalse($this->user->authenticate('senhaerrada'));

    public function test_find_by_email_should_return_the_user(): void        $this->assertFalse($this->user->authenticate(''));

    {    }

        $this->assertEquals($this->user->id, User::findByEmail($this->user->email)->id);

    }    /**

     * 3.1 - Teste se a senha é criptografada

    public function test_find_by_email_should_return_null(): void     */

    {    public function test_password_should_be_encrypted(): void

        $this->assertNull(User::findByEmail('not.exits@example.com'));    {

    }        $user = new User([

            'name' => 'Test User',

    public function test_authenticate_should_return_true(): void            'email' => 'test@example.com',

    {            'password' => 'mypassword',

        $this->assertTrue($this->user->authenticate('123456'));            'password_confirmation' => 'mypassword'

        $this->assertFalse($this->user->authenticate('wrong'));        ]);

    }        $user->save();



    public function test_authenticate_should_return_false(): void        // A senha criptografada não deve ser igual à senha em texto plano

    {        $this->assertNotEquals('mypassword', $user->encrypted_password);

        $this->assertFalse($this->user->authenticate(''));        

    }        // Deve começar com $2y$ (bcrypt)

        $this->assertStringStartsWith('$2y$', $user->encrypted_password);

    public function test_update_should_not_change_the_password(): void        

    {        // Deve ter pelo menos 60 caracteres

        $this->user->password = '654321';        $this->assertGreaterThan(59, strlen($user->encrypted_password));

        $this->user->save();    }



        $this->assertTrue($this->user->authenticate('123456'));    /**

        $this->assertFalse($this->user->authenticate('654321'));     * 3.1 - Teste do método findByEmail()

    }     */

    public function test_find_by_email_should_return_user(): void

    public function test_password_should_be_encrypted(): void    {

    {        $found = User::findByEmail('fulano@example.com');

        $user = new User([        

            'name' => 'Test User',        $this->assertNotNull($found);

            'email' => 'test@example.com',        $this->assertEquals('User 1', $found->name);

            'password' => 'mypassword',        $this->assertEquals('fulano@example.com', $found->email);

            'password_confirmation' => 'mypassword'    }

        ]);

        $user->save();    public function test_find_by_email_should_return_null_when_not_found(): void

    {

        $this->assertNotEquals('mypassword', $user->encrypted_password);        $found = User::findByEmail('naoexiste@example.com');

        $this->assertStringStartsWith('$2y$', $user->encrypted_password);        

        $this->assertGreaterThan(59, strlen($user->encrypted_password));        $this->assertNull($found);

    }    }



    public function test_is_admin_should_return_true_for_admin(): void    /**

    {     * 3.1 - Teste dos métodos isAdmin() e isUser()

        $admin = new User([     */

            'name' => 'Admin User',    public function test_is_admin_should_return_true_for_admin(): void

            'email' => 'admin@test.com',    {

            'password' => '123456',        $admin = new User([

            'password_confirmation' => '123456',            'name' => 'Admin User',

            'role' => 'admin'            'email' => 'admin@test.com',

        ]);            'password' => '123456',

        $admin->save();            'password_confirmation' => '123456',

            'role' => 'admin'

        $this->assertTrue($admin->isAdmin());        ]);

        $this->assertFalse($admin->isUser());        $admin->save();

    }

        $this->assertTrue($admin->isAdmin());

    public function test_is_user_should_return_true_for_regular_user(): void        $this->assertFalse($admin->isUser());

    {    }

        $this->assertTrue($this->user->isUser());

        $this->assertFalse($this->user->isAdmin());    public function test_is_user_should_return_true_for_regular_user(): void

    }    {

        $this->assertTrue($this->user->isUser());

    public function test_email_should_be_unique(): void        $this->assertFalse($this->user->isAdmin());

    {    }

        $duplicateUser = new User([

            'name' => 'Duplicate User',    /**

            'email' => 'fulano@example.com',     * 3.1 - Teste de validação de email único

            'password' => '123456',     */

            'password_confirmation' => '123456'    public function test_email_should_be_unique(): void

        ]);    {

        $duplicateUser = new User([

        $this->assertFalse($duplicateUser->save());            'name' => 'Duplicate User',

        $this->assertTrue($duplicateUser->hasErrors());            'email' => 'fulano@example.com', // Email já existe

        $this->assertEquals('esse e-mail já está cadastrado!', $duplicateUser->errors('email'));            'password' => '123456',

    }            'password_confirmation' => '123456'

        ]);

    public function test_default_role_should_be_user(): void

    {        $this->assertFalse($duplicateUser->save());

        $user = new User([        $this->assertTrue($duplicateUser->hasErrors());

            'name' => 'New User',        $this->assertEquals('esse e-mail já está cadastrado!', $duplicateUser->errors('email'));

            'email' => 'newuser@example.com',    }

            'password' => '123456',

            'password_confirmation' => '123456'    /**

        ]);     * 3.1 - Teste de role padrão

        $user->save();     */

    public function test_default_role_should_be_user(): void

        $this->assertTrue($user->isUser());    {

    }        $user = new User([

}            'name' => 'New User',

            'email' => 'newuser@example.com',
            'password' => '123456',
            'password_confirmation' => '123456'
        ]);
        $user->save();

        // Se não especificar role, deve ser 'user' por padrão
        $this->assertTrue($user->isUser());
    }
    }

    public function test_find_by_id_should_return_the_user(): void
    {
        $this->assertEquals($this->user->id, User::findById($this->user->id)->id);
    }

    public function test_find_by_id_should_return_null(): void
    {
        $this->assertNull(User::findById(3));
    }

    public function test_find_by_email_should_return_the_user(): void
    {
        $this->assertEquals($this->user->id, User::findByEmail($this->user->email)->id);
    }

    public function test_find_by_email_should_return_null(): void
    {
        $this->assertNull(User::findByEmail('not.exits@example.com'));
    }

    public function test_authenticate_should_return_the_true(): void
    {
        $this->assertTrue($this->user->authenticate('123456'));
        $this->assertFalse($this->user->authenticate('wrong'));
    }

    public function test_authenticate_should_return_false(): void
    {
        $this->assertFalse($this->user->authenticate(''));
    }

    public function test_update_should_not_change_the_password(): void
    {
        $this->user->password = '654321';
        $this->user->save();

        $this->assertTrue($this->user->authenticate('123456'));
        $this->assertFalse($this->user->authenticate('654321'));
    }
}
