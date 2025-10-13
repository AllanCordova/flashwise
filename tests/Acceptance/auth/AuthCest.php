<?php

namespace Tests\Acceptance\auth;

use App\Models\User;
use Tests\Acceptance\BaseAcceptanceCest;
use Tests\Support\AcceptanceTester;

class AuthCest extends BaseAcceptanceCest
{
    public function guestIsRedirectedFromProtectedRoute(AcceptanceTester $I): void
    {
        $I->amOnPage('/decks');
        $I->seeCurrentUrlEquals('/login');
        $I->waitForText('Você deve estar logado para acessar essa página.', 10);
    }

    public function loginFailsWithInvalidCredentials(AcceptanceTester $I): void
    {
        $user = new User([
            'name' => 'Test User',
            'email' => 'user@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'user',
        ]);
        $user->save();

        $I->amOnPage('/login');
        $I->fillField('user[email]', 'user@test.com');
        $I->fillField('user[password]', 'wrong-password');
        $I->click('Entrar');

        $I->seeCurrentUrlEquals('/login');
        $I->waitForText('E-mail ou senha inválidos. Por favor, tente novamente.', 10);
    }

    public function successfulLogin(AcceptanceTester $I): void
    {
        $user = new User([
            'name' => 'Allan',
            'email' => 'allan@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $user->save();

        $I->amOnPage('/login');
        $I->fillField('user[email]', 'allan@test.com');
        $I->fillField('user[password]', 'password123');
        $I->click('Entrar');

        $I->seeCurrentUrlEquals('/');
        $I->waitForText('Login realizado com sucesso! Bem-vindo(a), Allan!', 10);
        $I->wait(1); // Esperar 1 segundo para o header carregar
        $I->see('Sair'); // Verificação simples sem wait
    }

    public function successfulLogout(AcceptanceTester $I): void
    {
        $user = new User([
            'name' => 'Logout User',
            'email' => 'logout@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $user->save();

        $I->amOnPage('/login');
        $I->fillField('user[email]', 'logout@test.com');
        $I->fillField('user[password]', 'password123');
        $I->click('Entrar');
        $I->seeCurrentUrlEquals('/');

        $I->amOnPage('/logout');

        $I->seeCurrentUrlEquals('/login');
        $I->waitForText('Você foi desconectado com segurança.', 10);

        $I->amOnPage('/decks');
        $I->seeCurrentUrlEquals('/login');
    }
}
