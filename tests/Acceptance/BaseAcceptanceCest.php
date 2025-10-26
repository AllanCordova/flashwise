<?php

namespace Tests\Acceptance;

use Core\Database\Database;
use Core\Env\EnvLoader;
use Tests\Support\AcceptanceTester;
use Tests\Support\Helper\LoginHelper;

class BaseAcceptanceCest
{
    protected LoginHelper $loginHelper;

    public function _before(AcceptanceTester $page, LoginHelper $loginHelper): void
    {
        EnvLoader::init();
        // Recriar banco de dados limpo para cada teste
        Database::drop();
        Database::create();
        Database::migrate();

        $page->wait(0.5);

        $this->loginHelper = $loginHelper;
    }
}
