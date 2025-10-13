<?php

namespace Tests\Acceptance\home;

use Tests\Acceptance\BaseAcceptanceCest;
use Tests\Support\AcceptanceTester;

class HomeIndexCest extends BaseAcceptanceCest
{
    public function seeHomePage(AcceptanceTester $page): void
    {
        $page->amOnPage('/');
        $page->wait(1); // Wait for page load and animations
        $page->waitForText('Bem-vindo ao FlashWise', 15);
    }
}
