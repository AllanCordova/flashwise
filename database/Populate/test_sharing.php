<?php

// Test script to populate deck sharing data

require __DIR__ . '/../../config/bootstrap.php';

use App\Models\User;
use App\Models\Deck;
use App\Models\DeckUserShared;

echo "Testing Deck Sharing Feature\n";
echo "=============================\n\n";

// Get users
$admin = User::findBy(['email' => 'admin@flashwise.com']);
$user1 = User::findBy(['email' => 'user1@flashwise.com']);

if (!$admin || !$user1) {
    echo "Error: Users not found. Please run db:populate first.\n";
    exit(1);
}

echo "Admin: {$admin->name} (ID: {$admin->id})\n";
echo "User 1: {$user1->name} (ID: {$user1->id})\n\n";

// Get any deck from the database
$deck = Deck::all()[0] ?? null;

if (!$deck) {
    echo "Error: No decks found in database.\n";
    exit(1);
}

echo "Deck to share: {$deck->name} (ID: {$deck->id})\n";
echo "Deck owner: {$deck->user->name}\n\n";

// Share deck with user1
echo "Sharing deck with user1...\n";
$deckShared = new DeckUserShared([
    'deck_id' => $deck->id,
    'user_id' => $user1->id
]);

if ($deckShared->save()) {
    echo "✓ Deck shared successfully!\n\n";
} else {
    echo "✗ Error sharing deck: " . $deckShared->errors('deck_id') . "\n\n";
}

// Verify shared decks
echo "User1's shared decks:\n";
$sharedDecks = $user1->shared_decks;
foreach ($sharedDecks as $deck) {
    echo "  - {$deck->name} (Owner: {$deck->user->name})\n";
}

echo "\n";
echo "Users who have access to the deck:\n";
$usersWithAccess = $deck->shared_with_users;
foreach ($usersWithAccess as $user) {
    echo "  - {$user->name}\n";
}

echo "\nTest completed!\n";
