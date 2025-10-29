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

    public function isNew(): bool
    {
        return $this->card_type === 'new';
    }

    public function isDue(): bool
    {
        // New cards are not considered "due", they are just "new"
        if ($this->card_type === 'new') {
            return false;
        }

        if ($this->next_review === null) {
            return false;
        }

        // Support both DATE and DATETIME formats
        // If it has time component, compare with current datetime
        // Otherwise, compare with current date
        $now = time();
        $nextReviewTime = strtotime($this->next_review);

        return $nextReviewTime <= $now;
    }

    public function processResponse(int $quality): void
    {
        if ($quality < 0 || $quality > 3) {
            throw new \InvalidArgumentException('Quality must be between 0 and 3');
        }

        $easeFactor = $this->ease_factor;
        $interval = $this->review_interval;
        $repetitions = $this->repetitions;

        // Map quality to SM-2 scale: 0->0, 1->3, 2->4, 3->5
        $sm2Quality = match ($quality) {
            0 => 0,
            1 => 3,
            2 => 4,
            3 => 5,
        };

        // Calculate ease factor
        if ($sm2Quality >= 3) {
            $easeFactor = $easeFactor + (0.1 - (5 - $sm2Quality) * (0.08 + (5 - $sm2Quality) * 0.02));

            if ($easeFactor < 1.3) {
                $easeFactor = 1.3;
            }
        } elseif ($quality === 0) {
            $easeFactor = max(1.3, $easeFactor - 0.2);
        }

        // Calculate interval based on quality
        if ($quality === 0) {
            $repetitions = 0;
            $interval = 0;
            $this->card_type = 'learning';
            $nextReview = new \DateTime();
            $nextReview->modify('+1 minute');
            $this->next_review = $nextReview->format('Y-m-d H:i:s');
        } elseif ($quality === 1) {
            if ($this->card_type === 'new' || $repetitions === 0) {
                $interval = 0;
                $repetitions = 1;
                $this->card_type = 'learning';
                $nextReview = new \DateTime();
                $nextReview->modify('+10 minutes');
                $this->next_review = $nextReview->format('Y-m-d H:i:s');
            } else {
                $interval = 0;
                $repetitions++;
                $this->card_type = 'learning';
                $nextReview = new \DateTime();
                $nextReview->modify('+1 hour');
                $this->next_review = $nextReview->format('Y-m-d H:i:s');
            }
        } elseif ($quality === 2) {
            if ($this->card_type === 'new' || $repetitions === 0) {
                $interval = 1;
                $repetitions = 1;
                $this->card_type = 'review';
                $nextReview = new \DateTime();
                $nextReview->modify('+1 day');
                $this->next_review = $nextReview->format('Y-m-d');
            } else {
                $interval = (int)round($interval * $easeFactor);
                if ($interval < 1) {
                    $interval = 1;
                }
                $repetitions++;
                $this->card_type = 'review';
                $nextReview = new \DateTime();
                $nextReview->modify("+{$interval} days");
                $this->next_review = $nextReview->format('Y-m-d');
            }
        } else {
            if ($this->card_type === 'new' || $repetitions === 0) {
                $interval = 4;
                $repetitions = 1;
                $this->card_type = 'review';
                $nextReview = new \DateTime();
                $nextReview->modify('+4 days');
                $this->next_review = $nextReview->format('Y-m-d');
            } else {
                $interval = (int)round($interval * ($easeFactor + 0.15));
                if ($interval < 4) {
                    $interval = 4;
                }
                $repetitions++;
                $this->card_type = 'review';
                $nextReview = new \DateTime();
                $nextReview->modify("+{$interval} days");
                $this->next_review = $nextReview->format('Y-m-d');
            }
        }

        $this->ease_factor = round($easeFactor, 2);
        $this->review_interval = $interval;
        $this->repetitions = $repetitions;
        $this->last_reviewed = date('Y-m-d H:i:s');
    }

    public function getNextIntervalText(int $quality): string
    {
        if ($quality === 0) {
            return '< 1min';
        }

        if ($quality === 1) {
            if ($this->card_type === 'new' || $this->repetitions === 0) {
                return '< 10min';
            } else {
                return '< 1h';
            }
        }

        if ($quality === 2) {
            if ($this->card_type === 'new' || $this->repetitions === 0) {
                return '1 dia';
            }

            $interval = (int)round($this->review_interval * $this->ease_factor);
            if ($interval < 1) {
                $interval = 1;
            }
            return $interval === 1 ? '1 dia' : "$interval dias";
        }

        if ($quality === 3) {
            if ($this->card_type === 'new' || $this->repetitions === 0) {
                return '4 dias';
            }

            $interval = (int)round($this->review_interval * ($this->ease_factor + 0.15));
            if ($interval < 4) {
                $interval = 4;
            }
            return "$interval dias";
        }

        return 'N/A';
    }

    /**
     * @return array{days: int, hours: int, minutes: int}|null
     */
    public function getDetailedTimeUntilReview(): ?array
    {
        if ($this->card_type === 'new' || $this->next_review === null) {
            return null;
        }

        $now = time();
        $nextReviewTime = strtotime($this->next_review);

        if ($nextReviewTime <= $now) {
            return null;
        }

        $diff = $nextReviewTime - $now;

        $days = (int)floor($diff / 86400);
        $hours = (int)floor(($diff % 86400) / 3600);
        $minutes = (int)floor(($diff % 3600) / 60);

        return [
            'days' => $days,
            'hours' => $hours,
            'minutes' => $minutes
        ];
    }
}
