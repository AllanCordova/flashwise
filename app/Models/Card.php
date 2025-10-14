<?php

namespace App\Models;

use Lib\Validations;
use Core\Database\ActiveRecord\Model;
use Core\Database\ActiveRecord\BelongsTo;

/**
 * @property int $id
 * @property string $front
 * @property string $back
 * @property int $deck_id
 * @property float $ease_factor
 * @property int $review_interval
 * @property int $repetitions
 * @property string|null $next_review
 * @property string $card_type
 * @property string|null $last_reviewed
 * @property string $created_at
 * @property string $updated_at
 * @property Deck $deck
 */
class Card extends Model
{
    protected static string $table = 'cards';
    protected static array $columns = [
        'front',
        'back',
        'deck_id',
        'ease_factor',
        'review_interval',
        'repetitions',
        'next_review',
        'card_type',
        'last_reviewed',
    ];

    public function validates(): void
    {
        Validations::notEmpty('front', $this);
        Validations::notEmpty('back', $this);
        Validations::notEmpty('deck_id', $this);
    }

    public function deck(): BelongsTo
    {
        return $this->belongsTo(Deck::class, 'deck_id');
    }

    /**
     * Check if card is new (never studied)
     */
    public function isNew(): bool
    {
        return $this->card_type === 'new';
    }

    /**
     * Check if card is due for review
     */
    public function isDue(): bool
    {
        if ($this->card_type === 'new') {
            return false;
        }

        if ($this->next_review === null) {
            return false;
        }

        return strtotime($this->next_review) <= strtotime(date('Y-m-d'));
    }
}
