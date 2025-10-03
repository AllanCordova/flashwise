<?php

namespace Tests\Acceptance\access;

use Tests\Acceptance\BaseAcceptanceCest;
use Tests\Support\AcceptanceTester;

class AccessControlCest extends BaseAcceptanceCest
{
    public function testAuthenticatedRoutesRequireLogin(AcceptanceTester $I): void
    {
        $I->amOnPage('/decks');
        $I->seeCurrentUrlEquals('/login');
        
        $I->amOnPage('/admin');
        $I->seeCurrentUrlEquals('/login');
    }

    public function testPublicRoutesAccessibleByAnyone(AcceptanceTester $I): void
    {
        $I->amOnPage('/');
        $I->see('Bem-vindo ao FlashWise');
        
        $I->amOnPage('/login');
        $I->see('Faça seu Login');
    }

    public function testAuthenticatedUsersCanAccessPublicRoutes(AcceptanceTester $I): void
    {
        $I->amOnPage('/login');
        $I->fillField('user[email]', 'user1@flashwise.com');
        $I->fillField('user[password]', 'password123');
        $I->click('Entrar');
        
        $I->amOnPage('/');
        $I->see('Bem-vindo ao FlashWise');
        $I->see('Meus Decks');
    }

    public function testRegularUserCannotAccessAdminArea(AcceptanceTester $I): void
    {
        $I->amOnPage('/login');
        $I->fillField('user[email]', 'user1@flashwise.com');
        $I->fillField('user[password]', 'password123');
        $I->click('Entrar');
        
        $I->amOnPage('/admin');
        $I->seeCurrentUrlEquals('/');
        $I->see('Acesso negado');
    }

    public function testAdminCanAccessAllAreas(AcceptanceTester $I): void
    {
        $I->amOnPage('/login');
        $I->fillField('user[email]', 'admin@flashwise.com');
        $I->fillField('user[password]', 'admin123');
        $I->click('Entrar');
        
        $I->amOnPage('/');
        $I->see('Bem-vindo ao FlashWise');
        
        $I->amOnPage('/decks');
        $I->see('Meus Decks');
        
        $I->amOnPage('/admin');
        $I->see('Painel do Administrador');
    }
}
