<?php

namespace Tests\Acceptance;

use Core\Constants\Constants;
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

    public function _after(AcceptanceTester $I): void
    {
        // Limpar arquivos de upload criados durante os testes
        $this->cleanupUploadFiles();
    }

    private function cleanupUploadFiles(): void
    {
        $materialsDir = Constants::rootPath()->join('public/assets/uploads/materials');

        if (is_dir($materialsDir)) {
            $this->removeDirectory($materialsDir);
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . '/' . $file;

            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
