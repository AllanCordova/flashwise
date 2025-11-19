<?php

namespace App\Models;

use App\Services\DeckMaterial;
use App\Services\FileSystemService;
use Lib\Validations;
use Core\Database\ActiveRecord\Model;
use Core\Database\ActiveRecord\HasMany;
use Core\Database\ActiveRecord\BelongsTo;
use Core\Database\ActiveRecord\BelongsToMany;
use Core\Constants\Constants;

/**
 * @property int $id
 * @property string $name
 * @property string $description
 * @property string $material
 * @property string $path_img
 * @property int $category_id
 * @property int $user_id
 * @property string $created_at
 * @property string $updated_at
 * @property Card[] $cards
 * @property Material[] $materials
 * @property User $user
 * @property User[] $shared_with_users
 */
class Deck extends Model
{
    protected static string $table = 'decks';
    protected static array $columns = [
        'name',
        'description',
        'material',
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

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class, 'deck_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function sharedWithUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'deck_user_shared', 'deck_id', 'user_id');
    }

    public function isSharedWithUser(User $user): bool
    {
        return DeckUserShared::exists(['deck_id' => $this->id, 'user_id' => $user->id]);
    }

    /**
     * Count new cards (never studied) for a specific user
     */
    public function countNewCards(?int $userId = null): int
    {
        if ($userId === null) {
            // Fallback to old behavior for backward compatibility
            $cards = $this->cards;
            return count(array_filter($cards, fn($card) => $card->isNew()));
        }

        $cards = $this->cards;
        $count = 0;

        foreach ($cards as $card) {
            $progress = CardUserProgress::findBy(['card_id' => $card->id, 'user_id' => $userId]);
            if ($progress === null || $progress->isNew()) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Count cards due for review for a specific user
     */
    public function countDueCards(?int $userId = null): int
    {
        if ($userId === null) {
            // Fallback to old behavior for backward compatibility
            $cards = $this->cards;
            return count(array_filter($cards, fn($card) => $card->isDue()));
        }

        $cards = $this->cards;
        $count = 0;

        foreach ($cards as $card) {
            $progress = CardUserProgress::findBy(['card_id' => $card->id, 'user_id' => $userId]);
            if ($progress && $progress->isDue()) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Count total cards in deck
     */
    public function countTotalCards(): int
    {
        return count($this->cards);
    }

    /**
     * Get cards ready for study (new cards + due cards) for a specific user
     * @return Card[]
     */
    public function getCardsForStudy(int $userId): array
    {
        $cards = $this->cards;
        $studyCards = [];

        // Collect new cards and due cards based on user progress
        foreach ($cards as $card) {
            $progress = CardUserProgress::findBy(['card_id' => $card->id, 'user_id' => $userId]);

            // If no progress exists, it's a new card
            if ($progress === null || $progress->isNew() || $progress->isDue()) {
                $studyCards[] = $card;
            }
        }

        // Sort: new cards first, then due cards by next_review date
        usort($studyCards, function ($a, $b) use ($userId) {
            $progressA = CardUserProgress::findBy(['card_id' => $a->id, 'user_id' => $userId]);
            $progressB = CardUserProgress::findBy(['card_id' => $b->id, 'user_id' => $userId]);

            $isNewA = $progressA === null || $progressA->isNew();
            $isNewB = $progressB === null || $progressB->isNew();

            // New cards come first
            if ($isNewA && !$isNewB) {
                return -1;
            }
            if (!$isNewA && $isNewB) {
                return 1;
            }

            // Both new or both due - sort by next_review
            if ($progressA === null || $progressA->next_review === null) {
                return -1;
            }
            if ($progressB === null || $progressB->next_review === null) {
                return 1;
            }

            return strcmp($progressA->next_review, $progressB->next_review);
        });

        return $studyCards;
    }

    /**
     * Check if deck has cards available for study for a specific user
     */
    public function hasCardsToStudy(int $userId): bool
    {
        return $this->countNewCards($userId) > 0 || $this->countDueCards($userId) > 0;
    }


    public function destroy(): bool
    {
        $materialsDir = Constants::rootPath()->join("public/assets/uploads/materials/{$this->id}");

        FileSystemService::deleteDirectoryRecursive($materialsDir);

        return parent::destroy();
    }
}
