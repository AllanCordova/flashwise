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
        $I->waitForText('Bem-vindo ao FlashWise', 10);

        $I->amOnPage('/login');
        $I->waitForText('Faça seu Login', 10);
    }
}
