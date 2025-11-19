<?php

namespace App\Models;

use Core\Database\ActiveRecord\BelongsTo;
use Lib\Validations;
use Core\Database\ActiveRecord\Model;

/**
 * @property int $id
 * @property int $card_id
 * @property int $user_id
 * @property float $ease_factor
 * @property int $review_interval
 * @property int $repetitions
 * @property string|null $next_review
 * @property string $card_type
 * @property string|null $last_reviewed
 * @property string $created_at
 * @property string $updated_at
 * @property Card $card
 * @property User $user
 */
class CardUserProgress extends Model
{
    protected static string $table = 'card_user_progress';
    protected static array $columns = [
        'card_id',
        'user_id',
        'ease_factor',
        'review_interval',
        'repetitions',
        'next_review',
        'card_type',
        'last_reviewed',
    ];

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class, 'card_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function validates(): void
    {
        Validations::uniqueness(['card_id', 'user_id'], $this);
    }

    /**
     * Check if this is a new card (never studied)
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
        if ($this->next_review === null) {
            return false;
        }

        $now = new \DateTime();
        $nextReview = new \DateTime($this->next_review);

        return $nextReview <= $now;
    }

    /**
     * Update card progress based on user response quality
     *
     * @param int $quality Quality of response (0-5)
     *                     0-2: Again (forgot)
     *                     3: Hard
     *                     4: Good
     *                     5: Easy
     */
    public function updateProgress(int $quality): void
    {
        // SM-2 Algorithm implementation
        if ($quality < 3) {
            // Forgot the card - reset to learning
            $this->repetitions = 0;
            $this->review_interval = 1;
            $this->card_type = 'learning';
        } else {
            // Correct response
            if ($this->repetitions === 0) {
                $this->review_interval = 1;
            } elseif ($this->repetitions === 1) {
                $this->review_interval = 6;
            } else {
                $this->review_interval = (int)round($this->review_interval * $this->ease_factor);
            }

            $this->repetitions++;
            $this->card_type = $this->repetitions > 1 ? 'review' : 'learning';
        }

        // Update ease factor
        $this->ease_factor = max(
            1.3,
            $this->ease_factor + (0.1 - (5 - $quality) * (0.08 + (5 - $quality) * 0.02))
        );

        // Set next review date
        $this->next_review = date('Y-m-d H:i:s', strtotime("+{$this->review_interval} days"));
        $this->last_reviewed = date('Y-m-d H:i:s');
    }

    /**
     * Get or create progress for a specific user and card
     */
    public static function getOrCreate(int $cardId, int $userId): CardUserProgress
    {
        $progress = self::findBy(['card_id' => $cardId, 'user_id' => $userId]);

        if ($progress) {
            return $progress;
        }

        // Create new progress record
        $progress = new CardUserProgress([
            'card_id' => $cardId,
            'user_id' => $userId,
            'ease_factor' => 2.50,
            'review_interval' => 0,
            'repetitions' => 0,
            'card_type' => 'new',
        ]);

        $progress->save();

        return $progress;
    }
}
