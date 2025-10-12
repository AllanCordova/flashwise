<?php

namespace Tests\Acceptance\access;

use App\Models\User;
use Tests\Acceptance\BaseAcceptanceCest;
use Tests\Support\AcceptanceTester;

class PrivatedCest extends BaseAcceptanceCest
{
    public function authenticatedUserCanAccessProtectedRouteDecks(AcceptanceTester $I): void
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

        $I->amOnPage('/decks');
        $I->seeCurrentUrlEquals('/decks');
        $I->see('Meus Decks');
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
