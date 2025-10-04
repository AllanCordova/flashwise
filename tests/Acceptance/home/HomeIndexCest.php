<?php

namespace Tests\Acceptance\home;

use Tests\Acceptance\BaseAcceptanceCest;
use Tests\Support\AcceptanceTester;

class HomeIndexCest extends BaseAcceptanceCest
{
    public function seeHomePage(AcceptanceTester $page): void
    {
        $page->amOnPage('/');

        $page->see('Bem-vindo ao FlashWise', '//h1[contains(., "Bem-vindo ao FlashWise")]');
    }
}
