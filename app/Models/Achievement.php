<?php

namespace App\Models;

use Core\Database\ActiveRecord\BelongsTo;
use Lib\Validations;
use Core\Database\ActiveRecord\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string|null $icon
 * @property string|null $description
 * @property string $color_class
 * @property string|null $file_path
 * @property int|null $file_size
 * @property string|null $mime_type
 * @property string $uploaded_at
 * @property User $user
 */
class Achievement extends Model
{
    protected static string $table = 'achievements';
    protected static array $columns = [
        'user_id',
        'title',
        'icon',
        'description',
        'color_class',
        'file_path',
        'file_size',
        'mime_type',
        'uploaded_at'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }


    public function validates(): void
    {
        Validations::notEmpty('user_id', $this);
        Validations::notEmpty('title', $this);

        // file_path, file_size e mime_type agora são opcionais (usamos ícones)
        if (!empty($this->file_path)) {
            Validations::notEmpty('file_size', $this);
            Validations::notEmpty('mime_type', $this);
            Validations::maxFileSize('file_size', $this, 20971520);
            Validations::allowedMimeTypes('mime_type', $this, [
                'image/png',
                'image/jpeg',
            ]);
        }
    }
}
