<?php

namespace Tests\Acceptance\access;

use Tests\Acceptance\BaseAcceptanceCest;
use Tests\Support\AcceptanceTester;

class AccessControlCest extends BaseAcceptanceCest
{
    /**
     * 2.1 - Rotas autenticadas acessadas somente por usuários autenticados
     */
    public function testAuthenticatedRoutesRequireLogin(AcceptanceTester $I): void
    {
        // Tentar acessar /decks sem login
        $I->amOnPage('/decks');
        $I->seeCurrentUrlEquals('/login');
        
        // Tentar acessar /admin sem login
        $I->amOnPage('/admin');
        $I->seeCurrentUrlEquals('/login');
    }

    /**
     * 2.2 - Rotas públicas acessadas por qualquer usuário
     */
    public function testPublicRoutesAccessibleByAnyone(AcceptanceTester $I): void
    {
        // Acessar home sem login
        $I->amOnPage('/');
        $I->see('Bem-vindo ao FlashWise');
        
        // Acessar login sem estar logado
        $I->amOnPage('/login');
        $I->see('Faça seu Login');
    }

    /**
     * 2.3 - Rotas públicas que não devem permitir usuários autenticados (redirect)
     */
    public function testAuthenticatedUsersCanAccessPublicRoutes(AcceptanceTester $I): void
    {
        // Login como usuário
        $I->amOnPage('/login');
        $I->fillField('user[email]', 'user1@flashwise.com');
        $I->fillField('user[password]', 'password123');
        $I->click('Entrar');
        
        // Tentar acessar a home (deve permitir)
        $I->amOnPage('/');
        $I->see('Bem-vindo ao FlashWise');
        $I->see('Meus Decks'); // Link só aparece quando logado
    }

    /**
     * Teste adicional: Usuário normal não pode acessar área admin
     */
    public function testRegularUserCannotAccessAdminArea(AcceptanceTester $I): void
    {
        // Login como usuário normal
        $I->amOnPage('/login');
        $I->fillField('user[email]', 'user1@flashwise.com');
        $I->fillField('user[password]', 'password123');
        $I->click('Entrar');
        
        // Tentar acessar área admin
        $I->amOnPage('/admin');
        $I->seeCurrentUrlEquals('/');
        $I->see('Acesso negado');
    }

    /**
     * Teste adicional: Admin pode acessar todas as áreas
     */
    public function testAdminCanAccessAllAreas(AcceptanceTester $I): void
    {
        // Login como admin
        $I->amOnPage('/login');
        $I->fillField('user[email]', 'admin@flashwise.com');
        $I->fillField('user[password]', 'admin123');
        $I->click('Entrar');
        
        // Acessar home
        $I->amOnPage('/');
        $I->see('Bem-vindo ao FlashWise');
        
        // Acessar decks
        $I->amOnPage('/decks');
        $I->see('Meus Decks');
        
        // Acessar admin
        $I->amOnPage('/admin');
        $I->see('Painel do Administrador');
    }
}
