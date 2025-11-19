<?php

namespace App\Models;

use Core\Database\ActiveRecord\BelongsTo;
use Lib\Validations;
use Core\Database\ActiveRecord\Model;

/**
 * @property int $id
 * @property int $deck_id
 * @property int $user_id
 * @property string $shared_at
 * @property Deck $deck
 * @property User $user
 */
class DeckUserShared extends Model
{
    protected static string $table = 'deck_user_shared';
    protected static array $columns = ['deck_id', 'user_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function deck(): BelongsTo
    {
        return $this->belongsTo(Deck::class, 'deck_id');
    }

    public function validates(): void
    {
        Validations::uniqueness(['deck_id', 'user_id'], $this);
    }
}
