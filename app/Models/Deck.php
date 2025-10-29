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

    /**
     * Get cards ready for study (new cards + due cards)
     * @return Card[]
     */
    public function getCardsForStudy(): array
    {
        $cards = $this->cards;
        $studyCards = [];

        // Collect new cards and due cards
        foreach ($cards as $card) {
            if ($card->card_type === 'new' || $card->isDue()) {
                $studyCards[] = $card;
            }
        }

        // Sort: new cards first, then due cards by next_review date
        usort($studyCards, function ($a, $b) {
            // New cards come first
            if ($a->card_type === 'new' && $b->card_type !== 'new') {
                return -1;
            }
            if ($a->card_type !== 'new' && $b->card_type === 'new') {
                return 1;
            }

            // Both new or both due - sort by next_review
            if ($a->next_review === null) {
                return -1;
            }
            if ($b->next_review === null) {
                return 1;
            }

            return strcmp($a->next_review, $b->next_review);
        });

        return $studyCards;
    }

    /**
     * Check if deck has cards available for study
     */
    public function hasCardsToStudy(): bool
    {
        return $this->countNewCards() > 0 || $this->countDueCards() > 0;
    }
}
