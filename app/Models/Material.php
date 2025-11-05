<?php

namespace App\Models;

use Lib\Validations;
use Core\Database\ActiveRecord\Model;
use Core\Database\ActiveRecord\BelongsTo;
use App\Services\MaterialFileUpload;

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
    ];

    // Property to store uploaded_at from database
    public ?string $uploaded_at = null;

    /**
     * Property to store file validation errors (deprecated - use errors('file') instead)
     * @var array<string, string>|null
     */
    public ?array $fileErrors = null;

    /**
     * Get the file upload service instance
     */
    public function fileUpload(): MaterialFileUpload
    {
        return new MaterialFileUpload($this, [
            'extension' => ['pdf'],
            'mime_types' => ['application/pdf'],
            'size' => 20971520 // 20MB
        ]);
    }

    public function validates(): void
    {
        Validations::notEmpty('deck_id', $this);
        Validations::notEmpty('title', $this);

        // Validate title length
        if (!empty($this->title) && strlen($this->title) > 255) {
            $this->addError('title', 'deve ter no máximo 255 caracteres');
        }
    }

    public function deck(): BelongsTo
    {
        return $this->belongsTo(Deck::class, 'deck_id');
    }

    /**
     * Get all materials for a specific deck
     * @param int $deckId
     * @return Material[]
     */
    public static function findByDeck(int $deckId): array
    {
        $pdo = \Core\Database\Database::getDatabaseConn();
        $stmt = $pdo->prepare("SELECT * FROM materials WHERE deck_id = ? ORDER BY uploaded_at DESC");
        $stmt->execute([$deckId]);

        $materials = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $material = new self($row);
            // Set uploaded_at separately since it's not in $columns
            if (isset($row['uploaded_at'])) {
                $material->uploaded_at = $row['uploaded_at'];
            }
            $materials[] = $material;
        }

        return $materials;
    }

    /**
     * Get the full URL for the material file
     */
    public function getFileUrl(): string
    {
        return $this->fileUpload()->path();
    }

    /**
     * Get the absolute file path
     */
    public function getAbsoluteFilePath(): string
    {
        return __DIR__ . '/../../public/assets/uploads/materials/' . $this->file_path;
    }

    /**
     * Get human-readable file size
     */
    public function getFormattedSize(): string
    {
        return $this->fileUpload()->getFormattedSize();
    }

    /**
     * Delete the physical file
     */
    public function deleteFile(): bool
    {
        return $this->fileUpload()->delete();
    }

    /**
     * Check if file exists on filesystem
     */
    public function fileExists(): bool
    {
        return $this->fileUpload()->exists();
    }
}
