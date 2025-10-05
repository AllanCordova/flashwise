<?php

namespace Tests\Acceptance\access;

use App\Models\User;
use Tests\Acceptance\BaseAcceptanceCest;
use Tests\Support\AcceptanceTester;

class PublicCest extends BaseAcceptanceCest
{
    public function anyUserCanAccessPublicRoutes(AcceptanceTester $I): void
    {
        $I->amOnPage('/');
        $I->see('Bem-vindo ao FlashWise');

        $I->amOnPage('/login');
        $I->see('Faça seu Login');
    }

    public function authenticatedUserIsRedirectedFromGuestOnlyRoutes(AcceptanceTester $I): void
    {
        $user = new User([
            'name' => 'Test User',
            'email' => 'user@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $user->save();

        $I->amOnPage('/login');
        $I->fillField('user[email]', 'user@test.com');
        $I->fillField('user[password]', 'password123');
        $I->click('Entrar');
        $I->seeCurrentUrlEquals('/');

        $I->amOnPage('/login');
        $I->seeCurrentUrlEquals('/');
    }
}
