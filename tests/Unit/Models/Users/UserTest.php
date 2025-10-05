<?php

namespace Tests\Unit\Models\Users;

use App\Models\User;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function test_should_create_user_with_valid_data(): void
    {
        $user = new User([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ]);

        $this->assertTrue($user->save());
        $this->assertNull($user->errors('name'));
        $this->assertNull($user->errors('email'));
        $this->assertNull($user->errors('password'));

        $this->assertGreaterThan(0, $user->id);
        $this->assertEquals('user', $user->role);
    }

    public function test_should_not_save_with_invalid_data(): void
    {
        $user = new User();

        $this->assertFalse($user->save());
        $this->assertTrue($user->hasErrors());
        $this->assertEquals('não pode ser vazio!', $user->errors('name'));
        $this->assertEquals('não pode ser vazio!', $user->errors('email'));
    }

    public function test_should_fail_if_email_is_not_unique(): void
    {
        $user1 = new User([
            'name' => 'User One',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ]);
        $user1->save();

        $user2 = new User([
            'name' => 'User Two',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ]);

        $this->assertFalse($user2->save());
        $this->assertEquals('já existe um registro com esse dado', $user2->errors('email'));
    }

    public function test_should_fail_if_password_confirmation_does_not_match(): void
    {
        $user = new User([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'wrong_password'
        ]);

        $this->assertFalse($user->save());
        $this->assertEquals('as senhas devem ser idênticas!', $user->errors('password'));
    }

    public function test_password_should_be_encrypted_on_set(): void
    {
        $user = new User([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'my-secret-password'
        ]);

        $this->assertNotEmpty($user->encrypted_password);
        $this->assertNotEquals('my-secret-password', $user->encrypted_password);
        $this->assertTrue(password_verify('my-secret-password', $user->encrypted_password));
    }

    public function test_authenticate_should_return_true_for_correct_password(): void
    {
        $user = new User([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ]);
        $user->save();

        $this->assertTrue($user->authenticate('password123'));
    }

    public function test_authenticate_should_return_false_for_incorrect_password(): void
    {
        $user = new User([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ]);
        $user->save();

        $this->assertFalse($user->authenticate('wrong-password'));
    }

    public function test_find_by_email_should_return_correct_user(): void
    {
        $user = new User([
            'name' => 'Find Me',
            'email' => 'findme@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ]);
        $user->save();

        $foundUser = User::findByEmail('findme@example.com');
        $this->assertInstanceOf(User::class, $foundUser);
        $this->assertEquals($user->id, $foundUser->id);
    }

    public function test_find_by_email_should_return_null_if_not_found(): void
    {
        $foundUser = User::findByEmail('notfound@example.com');
        $this->assertNull($foundUser);
    }

    public function test_is_admin_should_return_correct_boolean(): void
    {
        $user = new User(['role' => 'user']);
        $admin = new User(['role' => 'admin']);

        $this->assertFalse($user->isAdmin());
        $this->assertTrue($admin->isAdmin());
    }
}
