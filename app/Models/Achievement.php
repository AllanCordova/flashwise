<?php

namespace App\Models;

use Core\Database\ActiveRecord\BelongsTo;
use Lib\Validations;
use Core\Database\ActiveRecord\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string $file_path
 * @property int $file_size
 * @property string $mime_type
 * @property string $uploaded_at
 * @property Deck $deck
 */
class Achievement extends Model
{
    protected static string $table = 'achievements';
    protected static array $columns = [
        'user_id',
        'title',
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
        Validations::notEmpty('file_path', $this);
        Validations::notEmpty('file_size', $this);
        Validations::notEmpty('mime_type', $this);

        Validations::maxFileSize('file_size', $this, 20971520);

        Validations::allowedMimeTypes('mime_type', $this, [
            'image/png',
            'image/jpeg',
        ]);
    }
}
