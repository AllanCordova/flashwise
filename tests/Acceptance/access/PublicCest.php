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
        $I->see('Bem-vindo ao FlashWise', 'h1');

        $I->amOnPage('/login');
        $I->see('Faça seu Login');
    }
}
