<?php

namespace App\Models;

use Lib\Validations;
use Core\Database\ActiveRecord\Model;
use Core\Database\ActiveRecord\HasMany;
use Core\Database\ActiveRecord\BelongsTo;

/**
 * @property int $id
 * @property string $name
 * @property string $description
 * @property string $path_img
 * @property int $category_id
 * @property int $user_id
 * @property string $created_at
 * @property string $updated_at
 * @property Card[] $cards
 * @property User $user
 */
class Deck extends Model
{
    protected static string $table = 'decks';
    protected static array $columns = [
        'name',
        'description',
        'path_img',
        'category_id',
        'user_id',
    ];


    public function validates(): void
    {
        Validations::notEmpty('name', $this);
        Validations::notEmpty('description', $this);

        Validations::uniqueness('name', $this);
    }

    public function cards(): HasMany
    {
        return $this->hasMany(Card::class, 'deck_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Count new cards (never studied)
     */
    public function countNewCards(): int
    {
        $cards = $this->cards;
        return count(array_filter($cards, fn($card) => $card->isNew()));
    }

    /**
     * Count cards due for review
     */
    public function countDueCards(): int
    {
        $cards = $this->cards;
        return count(array_filter($cards, fn($card) => $card->isDue()));
    }

    /**
     * Count total cards in deck
     */
    public function countTotalCards(): int
    {
        return count($this->cards);
    }
}
