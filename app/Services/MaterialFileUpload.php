<?php

namespace App\Services;

use Core\Constants\Constants;
use Core\Database\ActiveRecord\Model;
use App\Models\Material;

/**
 * Service for handling material file uploads
 *
 * @property Material $model
 */
class MaterialFileUpload
{
    /** @var array<string, mixed> $file */
    private array $file;

    /** @var string|null $generatedFileName */
    private ?string $generatedFileName = null;

    /** @var string|null $uploadedFilePath */
    private ?string $uploadedFilePath = null;

    private const ALLOWED_TYPES = ['application/pdf'];
    private const MAX_FILE_SIZE = 20971520; // 20MB

    /** @param array<string, mixed> $validations */
    public function __construct(
        private Material $model,
        private array $validations = []
    ) {
    }

    /**
     * Get the URL path for the material file
     */
    public function path(): string
    {
        if ($this->model->file_path) {
            return $this->baseDir() . $this->model->file_path;
        }

        return '';
    }

    /**
     * Upload and save the file
     * @param array<string, mixed> $file
     */
    public function upload(array $file): bool
    {
        $this->file = $file;

        if (!$this->isValidFile()) {
            return false;
        }

        if ($this->uploadFile()) {
            // Get file info from the uploaded file
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $this->uploadedFilePath);
            finfo_close($finfo);

            // Set file properties in the model (don't save yet)
            $this->model->file_path = $this->getFileName();
            $this->model->file_size = $this->file['size'];
            $this->model->mime_type = $mimeType;

            return true;
        }

        return false;
    }

    /**
     * Move the uploaded file to the destination
     */
    protected function uploadFile(): bool
    {
        if (empty($this->getTmpFilePath())) {
            return false;
        }

        $this->removeOldFile();

        // Generate destination path once and store it
        $this->uploadedFilePath = $this->getAbsoluteDestinationPath();

        $resp = move_uploaded_file(
            $this->getTmpFilePath(),
            $this->uploadedFilePath
        );

        if (!$resp) {
            $error = error_get_last();
            throw new \RuntimeException(
                'Failed to move uploaded file: ' . ($error['message'] ?? 'Unknown error')
            );
        }

        return true;
    }

    /**
     * Delete the file from filesystem
     */
    public function delete(): bool
    {
        if ($this->model->file_path) {
            $filePath = $this->getAbsoluteSavedFilePath();
            if (file_exists($filePath)) {
                return unlink($filePath);
            }
        }

        return true;
    }

    /**
     * Check if file exists on filesystem
     */
    public function exists(): bool
    {
        if (!$this->model->file_path) {
            return false;
        }

        return file_exists($this->getAbsoluteSavedFilePath());
    }

    /**
     * Get human-readable file size
     */
    public function getFormattedSize(): string
    {
        if (!$this->model->file_size) {
            return '0 B';
        }

        $bytes = $this->model->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get the temporary file path from upload
     */
    private function getTmpFilePath(): string
    {
        return $this->file['tmp_name'] ?? '';
    }

    /**
     * Remove old file if exists
     */
    private function removeOldFile(): void
    {
        if ($this->model->file_path) {
            $oldPath = $this->getAbsoluteSavedFilePath();
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }
    }

    /**
     * Generate a unique filename
     */
    private function getFileName(): string
    {
        // Cache the generated filename to ensure consistency
        if ($this->generatedFileName === null) {
            $fileExtension = pathinfo($this->file['name'], PATHINFO_EXTENSION);
            $this->generatedFileName = uniqid() . '_' . time() . '.' . $fileExtension;
        }

        return $this->generatedFileName;
    }

    /**
     * Get the absolute path where file will be saved
     */
    private function getAbsoluteDestinationPath(): string
    {
        return $this->storeDir() . $this->getFileName();
    }

    /**
     * Get the base directory URL
     */
    private function baseDir(): string
    {
        return "/assets/uploads/materials/";
    }

    /**
     * Get the storage directory path (creates if not exists)
     */
    private function storeDir(): string
    {
        $path = Constants::rootPath()->join('public' . $this->baseDir());
        if (!is_dir($path)) {
            mkdir(directory: $path, recursive: true);
        }

        return $path;
    }

    /**
     * Get the absolute path of a saved file
     */
    private function getAbsoluteSavedFilePath(): string
    {
        return Constants::rootPath()->join('public' . $this->baseDir())->join($this->model->file_path);
    }

    /**
     * Validate the uploaded file
     */
    private function isValidFile(): bool
    {
        // Check if file was uploaded
        if (!isset($this->file['tmp_name']) || $this->file['error'] === UPLOAD_ERR_NO_FILE) {
            $this->model->addError('file', 'Você deve selecionar um arquivo PDF');
            return false;
        }

        // Check for upload errors
        if ($this->file['error'] !== UPLOAD_ERR_OK) {
            $this->model->addError('file', $this->getUploadErrorMessage($this->file['error']));
            return false;
        }

        // Validate extension
        $this->validateFileExtension();

        // Validate MIME type
        $this->validateFileMimeType();

        // Validate size
        $this->validateFileSize();

        return $this->model->errors('file') === null;
    }

    /**
     * Get error message for upload error code
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'O arquivo excede o tamanho máximo permitido pelo servidor',
            UPLOAD_ERR_FORM_SIZE => 'O arquivo excede o tamanho máximo permitido',
            UPLOAD_ERR_PARTIAL => 'O arquivo foi enviado apenas parcialmente',
            UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária ausente',
            UPLOAD_ERR_CANT_WRITE => 'Falha ao gravar arquivo no disco',
            UPLOAD_ERR_EXTENSION => 'Uma extensão PHP interrompeu o upload'
        ];

        return $errorMessages[$errorCode] ?? 'Erro ao fazer upload do arquivo';
    }

    /**
     * Validate file extension
     */
    private function validateFileExtension(): void
    {
        $fileExtension = strtolower(pathinfo($this->file['name'], PATHINFO_EXTENSION));

        if (isset($this->validations['extension'])) {
            if (!in_array($fileExtension, $this->validations['extension'])) {
                $this->model->addError('file', 'Extensão inválida. Apenas arquivos .pdf são aceitos');
            }
        } elseif ($fileExtension !== 'pdf') {
            $this->model->addError('file', 'Extensão inválida. Apenas arquivos .pdf são aceitos');
        }
    }

    /**
     * Validate file MIME type
     */
    private function validateFileMimeType(): void
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $this->file['tmp_name']);
        finfo_close($finfo);

        if (isset($this->validations['mime_types'])) {
            if (!in_array($mimeType, $this->validations['mime_types'])) {
                $this->model->addError('file', 'Formato inválido. Apenas arquivos PDF são permitidos');
            }
        } elseif (!in_array($mimeType, self::ALLOWED_TYPES)) {
            $this->model->addError('file', 'Formato inválido. Apenas arquivos PDF são permitidos');
        }
    }

    /**
     * Validate file size
     */
    private function validateFileSize(): void
    {
        $maxSize = $this->validations['size'] ?? self::MAX_FILE_SIZE;

        if ($this->file['size'] > $maxSize) {
            $sizeInMB = round($maxSize / 1024 / 1024);
            $this->model->addError('file', "O arquivo excede o tamanho máximo permitido pelo servidor");
        }
    }
}
