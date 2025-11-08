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

        // Garantir que os diretórios de upload existam com permissões corretas
        $this->ensureUploadDirectoriesExist();

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

    private function ensureUploadDirectoriesExist(): void
    {
        $baseUploadDir = Constants::rootPath()->join('public/assets/uploads');
        $materialsDir = Constants::rootPath()->join('public/assets/uploads/materials');
        $avatarsDir = Constants::rootPath()->join('public/assets/uploads/avatars');

        $directories = [$baseUploadDir, $materialsDir, $avatarsDir];

        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                $oldUmask = umask(0);
                @mkdir($dir, 0777, true);
                umask($oldUmask);
            }
        }
    }
}
