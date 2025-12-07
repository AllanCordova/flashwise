<?php

namespace App\Models;

use Core\Database\ActiveRecord\BelongsTo;
use Lib\Validations;
use Core\Database\ActiveRecord\Model;

/**
 * @property int $id
 * @property int $deck_id
 * @property string $title
 * @property string $file_path
 * @property int $file_size
 * @property string $mime_type
 * @property string $uploaded_at
 * @property Deck $deck
 */
class Material extends Model
{
    protected static string $table = 'materials';
    protected static array $columns = [
        'deck_id',
        'title',
        'file_path',
        'file_size',
        'mime_type',
        'uploaded_at'
    ];

    public function deck(): BelongsTo
    {
        return $this->belongsTo(Deck::class, 'deck_id');
    }


    public function validates(): void
    {
        Validations::notEmpty('deck_id', $this);
        Validations::notEmpty('title', $this);
        Validations::notEmpty('file_path', $this);
        Validations::notEmpty('file_size', $this);
        Validations::notEmpty('mime_type', $this);

        Validations::maxFileSize('file_size', $this, 20971520);

        Validations::allowedMimeTypes('mime_type', $this, [
            'application/pdf',
            'image/png'
        ]);
    }

    public function getFileUrl(): string
    {
        if ($this->file_path) {
            // Garante que a URL seja absoluta a partir da raiz
            return '/' . ltrim($this->file_path, '/');
        }

        return '';
    }

    public function getFormattedSize(): string
    {
        if (!$this->file_size) {
            return '0 Bytes';
        }

        $bytes = $this->file_size;
        $units = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        $pow = floor(log($bytes) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
