<?php

namespace Tests\Acceptance;

use Core\Database\Database;
use Core\Env\EnvLoader;
use Tests\Support\AcceptanceTester;

class BaseAcceptanceCest
{
    public function _before(AcceptanceTester $page): void
    {
        EnvLoader::init();
        
        // Recriar banco de dados limpo para cada teste
        Database::drop();
        Database::create();
        Database::migrate();
    }
}
