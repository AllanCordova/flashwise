<?php

namespace App\Services;

use App\Models\Material;
use Core\Constants\Constants;

class MaterialService
{
    public function __construct(
        /** @var array<string, mixed> */
        private array $file,
        private Material $material
    ) {
    }


    public function upload(): Material
    {
        $fileInfo = pathinfo($this->file['name'] ?? '');
        $title = $fileInfo['filename'] ?: 'material';
        $fileSize = $this->file['size'] ?? 0;
        $tmpFilePath = $this->getTmpFilePath();
        $mimeType = $this->file['type'] ?? ($tmpFilePath && file_exists($tmpFilePath) ? mime_content_type($tmpFilePath) : '');

        $this->material->title = $title;
        $this->material->file_size = $fileSize;
        $this->material->mime_type = $mimeType;

        $fileName = $this->getFileName();
        $filePath = $this->baseDir() . $fileName;
        $absolutePath = $this->getAbsoluteFilePath($fileName);

        $this->material->file_path = $filePath;

        if (!$this->material->save()) {
            if (file_exists($absolutePath)) {
                unlink($absolutePath);
            }
            return $this->material;
        }

        if (move_uploaded_file($tmpFilePath, $absolutePath)) {
            // Garantir permissões corretas no arquivo movido (importante para ambientes CI)
            $oldUmask = umask(0);
            chmod($absolutePath, 0666);
            umask($oldUmask);
        }
        
        return $this->material;
    }

    public function destroy(): bool
    {
        if ($this->material->file_path) {
            $absolutePath = Constants::rootPath()->join('public' . $this->material->file_path);
            if (file_exists($absolutePath)) {
                @unlink($absolutePath);
            }
        }

        return $this->material->destroy();
    }

    private function getTmpFilePath(): string
    {
        return $this->file['tmp_name'] ?? '';
    }

    private function getFileName(): string
    {
        $file_name_splitted = explode('.', $this->file['name']);
        $file_extension = end($file_name_splitted);
        $timestamp = time();
        return "material_{$timestamp}.{$file_extension}";
    }

    private function getAbsoluteFilePath(string $fileName): string
    {
        return $this->storeDir() . $fileName;
    }

    private function baseDir(): string
    {
        return "/assets/uploads/{$this->material::table()}/{$this->material->deck_id}/";
    }

    private function storeDir(): string
    {
        $path = Constants::rootPath()->join('public' . $this->baseDir());
        if (!is_dir($path)) {
            // Usar umask para garantir permissões corretas em ambientes CI
            $oldUmask = umask(0);
            mkdir($path, 0777, true);
            umask($oldUmask);
        }

        return $path;
    }
}
