<?php<?php



namespace Tests\Acceptance\auth;namespace Tests\Acceptance\auth;



use Tests\Acceptance\BaseAcceptanceCest;use Tests\Acceptance\BaseAcceptanceCest;

use Tests\Support\AcceptanceTester;

class LoginCest extends BaseAcceptanceCest

class LoginCest extends BaseAcceptanceCest{

{    public function _before(\AcceptanceTester $I)

    /**    {

     * 1.1 - Tentativa de acesso à área restrita sem autenticação        parent::_before($I);

     */    }

    public function testAccessRestrictedAreaWithoutAuth(AcceptanceTester $I): void

    {    public function testUserCanSeeLoginPage(\AcceptanceTester $I)

        $I->amOnPage('/decks');    {

        $I->seeCurrentUrlEquals('/login');        $I->amOnPage('/login');

        $I->see('Você precisa estar logado');        $I->see('FlashWise - Login');

    }        $I->seeElement('input[name="email"]');

        $I->seeElement('input[name="password"]');

    /**        $I->seeElement('button[type="submit"]');

     * 1.1 - Tentativa de acesso à área admin sem autenticação    }

     */

    public function testAccessAdminAreaWithoutAuth(AcceptanceTester $I): void    public function testUserCannotLoginWithInvalidCredentials(\AcceptanceTester $I)

    {    {

        $I->amOnPage('/admin');        $I->amOnPage('/login');

        $I->seeCurrentUrlEquals('/login');        $I->fillField('email', 'wrong@email.com');

        $I->see('Você precisa estar logado');        $I->fillField('password', 'wrongpassword');

    }        $I->click('button[type="submit"]');

        

    /**        $I->seeCurrentUrlEquals('/login');

     * 1.2 - Tentativa de autenticação com dados incorretos        $I->see('Email ou senha inválidos');

     */    }

    public function testLoginWithIncorrectCredentials(AcceptanceTester $I): void

    {    public function testUserCanLoginWithValidCredentials(\AcceptanceTester $I)

        $I->amOnPage('/login');    {

        $I->fillField('user[email]', 'wrong@email.com');        $I->amOnPage('/login');

        $I->fillField('user[password]', 'wrongpassword');        $I->fillField('email', 'user@flashwise.com');

        $I->click('Entrar');        $I->fillField('password', 'user123');

                $I->click('button[type="submit"]');

        $I->seeCurrentUrlEquals('/login');        

        $I->see('E-mail ou senha inválidos');        $I->seeCurrentUrlEquals('/user/dashboard');

    }        $I->see('Bem-vindo');

        $I->see('Meus Estudos - FlashWise');

    /**    }

     * 1.2 - Tentativa de autenticação com email correto e senha incorreta

     */    public function testAdminCanLoginAndAccessAdminDashboard(\AcceptanceTester $I)

    public function testLoginWithCorrectEmailButWrongPassword(AcceptanceTester $I): void    {

    {        $I->amOnPage('/login');

        $I->amOnPage('/login');        $I->fillField('email', 'admin@flashwise.com');

        $I->fillField('user[email]', 'user1@flashwise.com');        $I->fillField('password', 'admin123');

        $I->fillField('user[password]', 'senhaerrada');        $I->click('button[type="submit"]');

        $I->click('Entrar');        

                $I->seeCurrentUrlEquals('/admin/dashboard');

        $I->seeCurrentUrlEquals('/login');        $I->see('Painel Administrativo - FlashWise');

        $I->see('E-mail ou senha inválidos');        $I->see('Gerenciar Usuários');

    }    }



    /**    public function testUserCannotAccessAdminArea(\AcceptanceTester $I)

     * 1.3 - Autenticação bem-sucedida (usuário normal)    {

     */        // Login como usuário regular

    public function testSuccessfulLoginAsUser(AcceptanceTester $I): void        $I->amOnPage('/login');

    {        $I->fillField('email', 'user@flashwise.com');

        $I->amOnPage('/login');        $I->fillField('password', 'user123');

        $I->fillField('user[email]', 'user1@flashwise.com');        $I->click('button[type="submit"]');

        $I->fillField('user[password]', 'password123');        

        $I->click('Entrar');        // Tentar acessar área admin

                $I->amOnPage('/admin/dashboard');

        $I->seeCurrentUrlEquals('/decks');        $I->dontSeeCurrentUrlEquals('/admin/dashboard');

        $I->see('Login realizado com sucesso');        $I->see('Acesso negado');

        $I->see('Usuário 1');    }

    }

    public function testAdminCannotAccessUserArea(\AcceptanceTester $I)

    /**    {

     * 1.3 - Autenticação bem-sucedida (admin)        // Login como admin

     */        $I->amOnPage('/login');

    public function testSuccessfulLoginAsAdmin(AcceptanceTester $I): void        $I->fillField('email', 'admin@flashwise.com');

    {        $I->fillField('password', 'admin123');

        $I->amOnPage('/login');        $I->click('button[type="submit"]');

        $I->fillField('user[email]', 'admin@flashwise.com');        

        $I->fillField('user[password]', 'admin123');        // Tentar acessar área de usuário

        $I->click('Entrar');        $I->amOnPage('/user/dashboard');

                $I->dontSeeCurrentUrlEquals('/user/dashboard');

        $I->seeCurrentUrlEquals('/admin');        $I->see('Acesso negado');

        $I->see('Login realizado com sucesso');    }

        $I->see('Administrador');

    }    public function testGuestCannotAccessProtectedPages(\AcceptanceTester $I)

    {

    /**        // Tentar acessar dashboard de usuário sem login

     * 1.4 - Logout        $I->amOnPage('/user/dashboard');

     */        $I->seeCurrentUrlEquals('/login');

    public function testLogout(AcceptanceTester $I): void        $I->see('Você deve estar logado');

    {        

        // Primeiro fazer login        // Tentar acessar dashboard admin sem login

        $I->amOnPage('/login');        $I->amOnPage('/admin/dashboard');

        $I->fillField('user[email]', 'user1@flashwise.com');        $I->seeCurrentUrlEquals('/login');

        $I->fillField('user[password]', 'password123');        $I->see('Você deve estar logado');

        $I->click('Entrar');    }

        

        // Fazer logout    public function testUserCanLogout(\AcceptanceTester $I)

        $I->amOnPage('/logout');    {

        $I->seeCurrentUrlEquals('/login');        // Login

        $I->see('Você foi desconectado com segurança');        $I->amOnPage('/login');

                $I->fillField('email', 'user@flashwise.com');

        // Tentar acessar área restrita novamente        $I->fillField('password', 'user123');

        $I->amOnPage('/decks');        $I->click('button[type="submit"]');

        $I->seeCurrentUrlEquals('/login');        

    }        $I->seeCurrentUrlEquals('/user/dashboard');

}        

        // Logout
        $I->click('Sair');
        
        $I->seeCurrentUrlEquals('/login');
        $I->see('Logout realizado com sucesso');
    }

    public function testLoggedInUserIsRedirectedFromLoginPage(\AcceptanceTester $I)
    {
        // Login
        $I->amOnPage('/login');
        $I->fillField('email', 'user@flashwise.com');
        $I->fillField('password', 'user123');
        $I->click('button[type="submit"]');
        
        // Tentar acessar página de login novamente
        $I->amOnPage('/login');
        $I->dontSeeCurrentUrlEquals('/login');
        $I->seeCurrentUrlEquals('/');
    }
}
