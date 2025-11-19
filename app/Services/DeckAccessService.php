<?php

namespace App\Services;

use App\Models\Deck;
use App\Models\User;

class DeckAccessService
{
    /**
     * Get a deck that the user can access (own or shared)
     *
     * @return Deck|null
     */
    public static function getAccessibleDeck(User $user, int $deckId): ?Deck
    {
        // Try to find in user's own decks
        /** @var Deck|null $deck */
        $deck = $user->decks()->findById($deckId);

        if ($deck !== null) {
            return $deck;
        }

        // Try to find in shared decks
        $sharedDecks = $user->shared_decks;
        foreach ($sharedDecks as $sharedDeck) {
            if ($sharedDeck->id === $deckId) {
                /** @var Deck $sharedDeck */
                return $sharedDeck;
            }
        }

        return null;
    }
}
