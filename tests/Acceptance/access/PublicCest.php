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
        $I->wait(1); // Wait for page load and animations
        $I->waitForText('Bem-vindo ao FlashWise', 15);

        $I->amOnPage('/login');
        $I->wait(1); // Wait for page load and animations
        $I->waitForText('Faça seu Login', 15);
    }
}
