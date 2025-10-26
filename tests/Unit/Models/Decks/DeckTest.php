<?php

namespace Tests\Unit\Models\Decks;

use App\Models\Card;
use App\Models\Deck;
use App\Models\User;
use Tests\TestCase;

class DeckTest extends TestCase
{
    private ?User $testUser = null;

    public function setUp(): void
    {
        parent::setUp();

        // Create a test user for deck tests
        $this->testUser = new User([
            'name' => 'Test User',
            'email' => 'testdeck@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'user',
        ]);
        $this->testUser->save();
    }

    // ------------ creation ------------
    public function test_should_create_deck_with_valid_data(): void
    {
        $deck = new Deck([
            'name' => 'Test Deck',
            'description' => 'Test Description',
            'user_id' => $this->testUser->id,
        ]);

        $this->assertTrue($deck->save());
        $this->assertNull($deck->errors('name'));
        $this->assertNull($deck->errors('description'));

        $this->assertGreaterThan(0, $deck->id);
        $this->assertEquals('Test Deck', $deck->name);
        $this->assertEquals('Test Description', $deck->description);
        $this->assertEquals($this->testUser->id, $deck->user_id);
    }

    public function test_should_not_save_with_invalid_data(): void
    {
        $deck = new Deck();

        $this->assertFalse($deck->save());
        $this->assertTrue($deck->hasErrors());
        $this->assertEquals('não pode ser vazio!', $deck->errors('name'));
        $this->assertEquals('não pode ser vazio!', $deck->errors('description'));
    }

    // ------------ validation ------------
    public function test_should_fail_if_name_is_empty(): void
    {
        $deck = new Deck([
            'name' => '',
            'description' => 'Valid Description',
            'user_id' => $this->testUser->id,
        ]);

        $this->assertFalse($deck->save());
        $this->assertTrue($deck->hasErrors());
        $this->assertEquals('não pode ser vazio!', $deck->errors('name'));
    }

    public function test_should_fail_if_description_is_empty(): void
    {
        $deck = new Deck([
            'name' => 'Valid Name',
            'description' => '',
            'user_id' => $this->testUser->id,
        ]);

        $this->assertFalse($deck->save());
        $this->assertTrue($deck->hasErrors());
        $this->assertEquals('não pode ser vazio!', $deck->errors('description'));
    }

    public function test_should_fail_if_name_is_not_unique(): void
    {
        $deck1 = new Deck([
            'name' => 'Unique Deck',
            'description' => 'First deck',
            'user_id' => $this->testUser->id,
        ]);
        $deck1->save();

        $deck2 = new Deck([
            'name' => 'Unique Deck',
            'description' => 'Second deck',
            'user_id' => $this->testUser->id,
        ]);

        $this->assertFalse($deck2->save());
        $this->assertTrue($deck2->hasErrors());
        $this->assertEquals('já existe um registro com esse dado', $deck2->errors('name'));
    }

    public function test_description_can_be_long(): void
    {
        $longDescription = str_repeat('Este é um deck com uma descrição muito longa. ', 50);

        $deck = new Deck([
            'name' => 'Long Description Deck',
            'description' => $longDescription,
            'user_id' => $this->testUser->id,
        ]);

        $this->assertTrue($deck->save());
        $this->assertEquals($longDescription, $deck->description);
    }

    // ------------ crud operations ------------
    public function test_deck_can_be_found_by_id(): void
    {
        $deck = new Deck([
            'name' => 'Findable Deck',
            'description' => 'Can be found',
            'user_id' => $this->testUser->id,
        ]);
        $deck->save();

        $foundDeck = Deck::findById($deck->id);

        $this->assertNotNull($foundDeck);
        $this->assertInstanceOf(Deck::class, $foundDeck);
        $this->assertEquals($deck->id, $foundDeck->id);
        $this->assertEquals('Findable Deck', $foundDeck->name);
        $this->assertEquals('Can be found', $foundDeck->description);
    }

    public function test_find_by_id_should_return_null_if_not_found(): void
    {
        $foundDeck = Deck::findById(99999);
        $this->assertNull($foundDeck);
    }

    public function test_deck_can_be_updated(): void
    {
        $deck = new Deck([
            'name' => 'Original Name',
            'description' => 'Original Description',
            'user_id' => $this->testUser->id,
        ]);
        $deck->save();
        $deckId = $deck->id;

        $deck->name = 'Updated Name';
        $deck->description = 'Updated Description';
        $this->assertTrue($deck->save());

        $updatedDeck = Deck::findById($deckId);
        $this->assertEquals('Updated Name', $updatedDeck->name);
        $this->assertEquals('Updated Description', $updatedDeck->description);
    }

    public function test_deck_can_be_deleted(): void
    {
        $deck = new Deck([
            'name' => 'Deletable Deck',
            'description' => 'Will be deleted',
            'user_id' => $this->testUser->id,
        ]);
        $deck->save();
        $deckId = $deck->id;

        $this->assertTrue($deck->destroy());
        $this->assertNull(Deck::findById($deckId));
    }

    public function test_all_decks_can_be_retrieved(): void
    {
        $deck1 = new Deck([
            'name' => 'Deck 1',
            'description' => 'First deck',
            'user_id' => $this->testUser->id,
        ]);
        $deck1->save();

        $deck2 = new Deck([
            'name' => 'Deck 2',
            'description' => 'Second deck',
            'user_id' => $this->testUser->id,
        ]);
        $deck2->save();

        $allDecks = Deck::all();

        $this->assertGreaterThanOrEqual(2, count($allDecks));
    }

    public function test_deck_can_be_found_with_where_clause(): void
    {
        $deck = new Deck([
            'name' => 'Searchable Deck',
            'description' => 'Can be searched',
            'user_id' => $this->testUser->id,
        ]);
        $deck->save();

        /** @var Deck[] $foundDecks */
        $foundDecks = Deck::where(['name' => 'Searchable Deck']);

        $this->assertNotEmpty($foundDecks);
        $this->assertEquals('Searchable Deck', $foundDecks[0]->name);
    }

    // ------------ relationships ------------
    public function test_deck_has_cards_relationship(): void
    {
        $deck = new Deck([
            'name' => 'Deck with Cards',
            'description' => 'Testing relationship',
            'user_id' => $this->testUser->id,
        ]);
        $deck->save();

        $card1 = new Card([
            'front' => 'Question 1',
            'back' => 'Answer 1',
            'deck_id' => $deck->id,
            'ease_factor' => 2.50,
            'review_interval' => 0,
            'repetitions' => 0,
            'next_review' => null,
            'card_type' => 'new',
            'last_reviewed' => null,
        ]);
        $card1->save();

        $card2 = new Card([
            'front' => 'Question 2',
            'back' => 'Answer 2',
            'deck_id' => $deck->id,
            'ease_factor' => 2.50,
            'review_interval' => 0,
            'repetitions' => 0,
            'next_review' => null,
            'card_type' => 'new',
            'last_reviewed' => null,
        ]);
        $card2->save();

        $cards = $deck->cards;

        $this->assertCount(2, $cards);
    }

    public function test_deck_belongs_to_user(): void
    {
        $deck = new Deck([
            'name' => 'User Deck',
            'description' => 'Belongs to user',
            'user_id' => $this->testUser->id,
        ]);
        $deck->save();

        $user = $deck->user;

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($this->testUser->id, $user->id);
        $this->assertEquals($this->testUser->email, $user->email);
    }

    // ------------ card counting methods ------------
    public function test_count_new_cards_returns_correct_count(): void
    {
        $deck = new Deck([
            'name' => 'Deck for Counting',
            'description' => 'Testing card counts',
            'user_id' => $this->testUser->id,
        ]);
        $deck->save();

        // Create 3 new cards
        for ($i = 1; $i <= 3; $i++) {
            $card = new Card([
                'front' => "New Question $i",
                'back' => "New Answer $i",
                'deck_id' => $deck->id,
                'ease_factor' => 2.50,
                'review_interval' => 0,
                'repetitions' => 0,
                'next_review' => null,
                'card_type' => 'new',
                'last_reviewed' => null,
            ]);
            $card->save();
        }

        $this->assertEquals(3, $deck->countNewCards());
    }

    public function test_count_due_cards_returns_correct_count(): void
    {
        $deck = new Deck([
            'name' => 'Deck for Due Cards',
            'description' => 'Testing due card counts',
            'user_id' => $this->testUser->id,
        ]);
        $deck->save();

        // Create 2 due cards (reviewed, with past review date)
        for ($i = 1; $i <= 2; $i++) {
            $card = new Card([
                'front' => "Due Question $i",
                'back' => "Due Answer $i",
                'deck_id' => $deck->id,
                'ease_factor' => 2.50,
                'review_interval' => 1,
                'repetitions' => 1,
                'next_review' => date('Y-m-d', strtotime('-1 day')), // Yesterday
                'card_type' => 'learning',
                'last_reviewed' => date('Y-m-d', strtotime('-2 days')),
            ]);
            $card->save();
        }

        // Create 1 new card (should not count as due)
        $newCard = new Card([
            'front' => 'New Question',
            'back' => 'New Answer',
            'deck_id' => $deck->id,
            'ease_factor' => 2.50,
            'review_interval' => 0,
            'repetitions' => 0,
            'next_review' => null,
            'card_type' => 'new',
            'last_reviewed' => null,
        ]);
        $newCard->save();

        $this->assertEquals(2, $deck->countDueCards());
    }

    public function test_count_total_cards_returns_correct_count(): void
    {
        $deck = new Deck([
            'name' => 'Deck for Total Count',
            'description' => 'Testing total card count',
            'user_id' => $this->testUser->id,
        ]);
        $deck->save();

        // Create 5 cards of different types
        for ($i = 1; $i <= 5; $i++) {
            $card = new Card([
                'front' => "Question $i",
                'back' => "Answer $i",
                'deck_id' => $deck->id,
                'ease_factor' => 2.50,
                'review_interval' => 0,
                'repetitions' => 0,
                'next_review' => null,
                'card_type' => 'new',
                'last_reviewed' => null,
            ]);
            $card->save();
        }

        $this->assertEquals(5, $deck->countTotalCards());
    }

    public function test_count_methods_return_zero_for_deck_without_cards(): void
    {
        $deck = new Deck([
            'name' => 'Empty Deck',
            'description' => 'No cards here',
            'user_id' => $this->testUser->id,
        ]);
        $deck->save();

        $this->assertEquals(0, $deck->countNewCards());
        $this->assertEquals(0, $deck->countDueCards());
        $this->assertEquals(0, $deck->countTotalCards());
    }
}
