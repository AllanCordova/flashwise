<?php

namespace App\Services;

class FileSystemService
{
    /**
     * Delete a directory and all its contents recursively
     */
    public static function deleteDirectoryRecursive(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $filePath = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($filePath)) {
                self::deleteDirectoryRecursive($filePath);
            } else {
                unlink($filePath);
            }
        }
        rmdir($dir);
    }
}
