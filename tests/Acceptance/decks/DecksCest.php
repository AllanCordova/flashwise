<?php

namespace Tests\Acceptance\decks;

use App\Models\Deck;
use App\Models\User;
use Tests\Acceptance\BaseAcceptanceCest;
use Tests\Support\AcceptanceTester;

class DecksCest extends BaseAcceptanceCest
{
    private function createUser(): User
    {
        $user = new User([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'user',
        ]);
        $user->save();

        return $user;
    }

    private function login(AcceptanceTester $I): User
    {
        $user = $this->createUser();

        $I->amOnPage('/login');
        $I->fillField('user[email]', 'test@example.com');
        $I->fillField('user[password]', 'password123');
        $I->click('Entrar');
        $I->seeCurrentUrlEquals('/');

        return $user;
    }

    public function tryToCreateDeckWithInvalidData(AcceptanceTester $I): void
    {
        $this->login($I);

        $I->amOnPage('/decks/create');
        $I->click('Salvar');

        $I->see('O campo título é obrigatório');
        $I->seeInCurrentUrl('/decks/create');
    }
}
