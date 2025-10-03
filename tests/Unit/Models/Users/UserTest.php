<?php

namespace Tests\Unit\Models\Users;

use App\Models\User;
use Tests\TestCase;

class UserTest extends TestCase
{
    private User $user;
    private User $user2;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = new User([
            'name' => 'User 1',
            'email' => 'fulano@example.com',
            'password' => '123456',
            'password_confirmation' => '123456'
        ]);
        $this->user->save();

        $this->user2 = new User([
            'name' => 'User 2',
            'email' => 'fulano1@example.com',
            'password' => '123456',
            'password_confirmation' => '123456'
        ]);
        $this->user2->save();
    }

    public function test_should_create_new_user(): void
    {
        $this->assertCount(2, User::all());
    }

    public function test_all_should_return_all_users(): void
    {
        $this->user2->save();

        $users[] = $this->user->id;
        $users[] = $this->user2->id;

        $all = array_map(fn($user) => $user->id, User::all());

        $this->assertCount(2, $all);
        $this->assertEquals($users, $all);
    }

    public function test_destroy_should_remove_the_user(): void
    {
        $this->user->destroy();
        $this->assertCount(1, User::all());
    }

    public function test_set_id(): void
    {
        $this->user->id = 10;
        $this->assertEquals(10, $this->user->id);
    }

    public function test_set_name(): void
    {
        $this->user->name = 'User name';
        $this->assertEquals('User name', $this->user->name);
    }

    public function test_set_email(): void
    {
        $this->user->email = 'outro@example.com';
        $this->assertEquals('outro@example.com', $this->user->email);
    }

    public function test_errors_should_return_errors(): void
    {
        $user = new User();

        $this->assertFalse($user->isValid());
        $this->assertFalse($user->save());
        $this->assertTrue($user->hasErrors());

        $this->assertEquals('não pode ser vazio!', $user->errors('name'));
        $this->assertEquals('não pode ser vazio!', $user->errors('email'));
    }

    public function test_errors_should_return_password_confirmation_error(): void
    {
        $user = new User([
            'name' => 'User 3',
            'email' => 'fulano3@example.com',
            'password' => '123456',
            'password_confirmation' => '1234567'
        ]);

        $this->assertFalse($user->isValid());
        $this->assertFalse($user->save());

        $this->assertEquals('as senhas devem ser idênticas!', $user->errors('password'));
    }

    /**
     * 3.1 - Teste do método authenticate() do modelo User
     */
    public function test_authenticate_should_verify_password_correctly(): void
    {
        // Senha correta
        $this->assertTrue($this->user->authenticate('123456'));
        
        // Senha incorreta
        $this->assertFalse($this->user->authenticate('senhaerrada'));
        $this->assertFalse($this->user->authenticate(''));
    }

    /**
     * 3.1 - Teste se a senha é criptografada
     */
    public function test_password_should_be_encrypted(): void
    {
        $user = new User([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'mypassword',
            'password_confirmation' => 'mypassword'
        ]);
        $user->save();

        // A senha criptografada não deve ser igual à senha em texto plano
        $this->assertNotEquals('mypassword', $user->encrypted_password);
        
        // Deve começar com $2y$ (bcrypt)
        $this->assertStringStartsWith('$2y$', $user->encrypted_password);
        
        // Deve ter pelo menos 60 caracteres
        $this->assertGreaterThan(59, strlen($user->encrypted_password));
    }

    /**
     * 3.1 - Teste do método findByEmail()
     */
    public function test_find_by_email_should_return_user(): void
    {
        $found = User::findByEmail('fulano@example.com');
        
        $this->assertNotNull($found);
        $this->assertEquals('User 1', $found->name);
        $this->assertEquals('fulano@example.com', $found->email);
    }

    public function test_find_by_email_should_return_null_when_not_found(): void
    {
        $found = User::findByEmail('naoexiste@example.com');
        
        $this->assertNull($found);
    }

    /**
     * 3.1 - Teste dos métodos isAdmin() e isUser()
     */
    public function test_is_admin_should_return_true_for_admin(): void
    {
        $admin = new User([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => '123456',
            'password_confirmation' => '123456',
            'role' => 'admin'
        ]);
        $admin->save();

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isUser());
    }

    public function test_is_user_should_return_true_for_regular_user(): void
    {
        $this->assertTrue($this->user->isUser());
        $this->assertFalse($this->user->isAdmin());
    }

    /**
     * 3.1 - Teste de validação de email único
     */
    public function test_email_should_be_unique(): void
    {
        $duplicateUser = new User([
            'name' => 'Duplicate User',
            'email' => 'fulano@example.com', // Email já existe
            'password' => '123456',
            'password_confirmation' => '123456'
        ]);

        $this->assertFalse($duplicateUser->save());
        $this->assertTrue($duplicateUser->hasErrors());
        $this->assertEquals('esse e-mail já está cadastrado!', $duplicateUser->errors('email'));
    }

    /**
     * 3.1 - Teste de role padrão
     */
    public function test_default_role_should_be_user(): void
    {
        $user = new User([
            'name' => 'New User',
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
