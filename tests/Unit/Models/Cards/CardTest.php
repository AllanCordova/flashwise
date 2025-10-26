<?php

namespace Tests\Unit\Models\Cards;

use App\Models\Card;
use App\Models\Deck;
use App\Models\User;
use Tests\TestCase;

class CardTest extends TestCase
{
    private ?User $testUser = null;
    private ?Deck $testDeck = null;

    public function setUp(): void
    {
        parent::setUp();

        // Create a test user
        $this->testUser = new User([
            'name' => 'Test User',
            'email' => 'testcard@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'user',
        ]);
        $this->testUser->save();

        // Create a test deck
        $this->testDeck = new Deck([
            'name' => 'Test Deck',
            'description' => 'Test Description',
            'user_id' => $this->testUser->id,
        ]);
        $this->testDeck->save();
    }

    // ------------ creation ------------
    public function test_should_create_card_with_valid_data(): void
    {
        $card = new Card([
            'front' => 'What is PHP?',
            'back' => 'PHP is a programming language',
            'deck_id' => $this->testDeck->id,
            'ease_factor' => 2.50,
            'review_interval' => 0,
            'repetitions' => 0,
            'next_review' => null,
            'card_type' => 'new',
            'last_reviewed' => null,
        ]);

        $this->assertTrue($card->save());
        $this->assertNull($card->errors('front'));
        $this->assertNull($card->errors('back'));
        $this->assertNull($card->errors('deck_id'));

        $this->assertGreaterThan(0, $card->id);
        $this->assertEquals('What is PHP?', $card->front);
        $this->assertEquals('PHP is a programming language', $card->back);
        $this->assertEquals($this->testDeck->id, $card->deck_id);
        $this->assertEquals(2.50, $card->ease_factor);
        $this->assertEquals(0, $card->review_interval);
        $this->assertEquals('new', $card->card_type);
    }

    public function test_should_not_save_with_invalid_data(): void
    {
        $card = new Card();

        $this->assertFalse($card->save());
        $this->assertTrue($card->hasErrors());
        $this->assertEquals('não pode ser vazio!', $card->errors('front'));
        $this->assertEquals('não pode ser vazio!', $card->errors('back'));
        $this->assertEquals('não pode ser vazio!', $card->errors('deck_id'));
    }

    // ------------ validation ------------
    public function test_should_fail_if_front_is_empty(): void
    {
        $card = new Card([
            'front' => '',
            'back' => 'Valid Answer',
            'deck_id' => $this->testDeck->id,
            'ease_factor' => 2.50,
            'review_interval' => 0,
            'repetitions' => 0,
            'card_type' => 'new',
        ]);

        $this->assertFalse($card->save());
        $this->assertTrue($card->hasErrors());
        $this->assertEquals('não pode ser vazio!', $card->errors('front'));
    }

    public function test_should_fail_if_back_is_empty(): void
    {
        $card = new Card([
            'front' => 'Valid Question',
            'back' => '',
            'deck_id' => $this->testDeck->id,
            'ease_factor' => 2.50,
            'review_interval' => 0,
            'repetitions' => 0,
            'card_type' => 'new',
        ]);

        $this->assertFalse($card->save());
        $this->assertTrue($card->hasErrors());
        $this->assertEquals('não pode ser vazio!', $card->errors('back'));
    }

    public function test_should_fail_if_deck_id_is_empty(): void
    {
        $card = new Card([
            'front' => 'Valid Question',
            'back' => 'Valid Answer',
            'deck_id' => null,
            'ease_factor' => 2.50,
            'review_interval' => 0,
            'repetitions' => 0,
            'card_type' => 'new',
        ]);

        $this->assertFalse($card->save());
        $this->assertTrue($card->hasErrors());
        $this->assertEquals('não pode ser vazio!', $card->errors('deck_id'));
    }

    public function test_front_can_be_long(): void
    {
        $longFront = str_repeat('Esta é uma pergunta muito longa. ', 30);

        $card = new Card([
            'front' => $longFront,
            'back' => 'Answer',
            'deck_id' => $this->testDeck->id,
            'ease_factor' => 2.50,
            'review_interval' => 0,
            'repetitions' => 0,
            'card_type' => 'new',
        ]);

        $this->assertTrue($card->save());
        $this->assertEquals($longFront, $card->front);
    }

    public function test_back_can_be_long(): void
    {
        $longBack = str_repeat('Esta é uma resposta muito longa. ', 30);

        $card = new Card([
            'front' => 'Question',
            'back' => $longBack,
            'deck_id' => $this->testDeck->id,
            'ease_factor' => 2.50,
            'review_interval' => 0,
            'repetitions' => 0,
            'card_type' => 'new',
        ]);

        $this->assertTrue($card->save());
        $this->assertEquals($longBack, $card->back);
    }

    // ------------ crud operations ------------
    public function test_card_can_be_found_by_id(): void
    {
        $card = new Card([
            'front' => 'Findable Question',
            'back' => 'Findable Answer',
            'deck_id' => $this->testDeck->id,
            'ease_factor' => 2.50,
            'review_interval' => 0,
            'repetitions' => 0,
            'card_type' => 'new',
        ]);
        $card->save();

        $foundCard = Card::findById($card->id);

        $this->assertNotNull($foundCard);
        $this->assertInstanceOf(Card::class, $foundCard);
        $this->assertEquals($card->id, $foundCard->id);
        $this->assertEquals('Findable Question', $foundCard->front);
        $this->assertEquals('Findable Answer', $foundCard->back);
    }

    public function test_find_by_id_should_return_null_if_not_found(): void
    {
        $foundCard = Card::findById(99999);
        $this->assertNull($foundCard);
    }

    public function test_card_can_be_updated(): void
    {
        $card = new Card([
            'front' => 'Original Question',
            'back' => 'Original Answer',
            'deck_id' => $this->testDeck->id,
            'ease_factor' => 2.50,
            'review_interval' => 0,
            'repetitions' => 0,
            'card_type' => 'new',
        ]);
        $card->save();
        $cardId = $card->id;

        $card->front = 'Updated Question';
        $card->back = 'Updated Answer';
        $this->assertTrue($card->save());

        $updatedCard = Card::findById($cardId);
        $this->assertEquals('Updated Question', $updatedCard->front);
        $this->assertEquals('Updated Answer', $updatedCard->back);
    }

    public function test_card_can_be_deleted(): void
    {
        $card = new Card([
            'front' => 'Deletable Question',
            'back' => 'Deletable Answer',
            'deck_id' => $this->testDeck->id,
            'ease_factor' => 2.50,
            'review_interval' => 0,
            'repetitions' => 0,
            'card_type' => 'new',
        ]);
        $card->save();
        $cardId = $card->id;

        $this->assertTrue($card->destroy());
        $this->assertNull(Card::findById($cardId));
    }

    public function test_all_cards_can_be_retrieved(): void
    {
        $card1 = new Card([
            'front' => 'Question 1',
            'back' => 'Answer 1',
            'deck_id' => $this->testDeck->id,
            'ease_factor' => 2.50,
            'review_interval' => 0,
            'repetitions' => 0,
            'card_type' => 'new',
        ]);
        $card1->save();

        $card2 = new Card([
            'front' => 'Question 2',
            'back' => 'Answer 2',
            'deck_id' => $this->testDeck->id,
            'ease_factor' => 2.50,
            'review_interval' => 0,
            'repetitions' => 0,
            'card_type' => 'new',
        ]);
        $card2->save();

        $allCards = Card::all();

        $this->assertGreaterThanOrEqual(2, count($allCards));
    }

    public function test_card_can_be_found_with_where_clause(): void
    {
        $card = new Card([
            'front' => 'Searchable Question',
            'back' => 'Searchable Answer',
            'deck_id' => $this->testDeck->id,
            'ease_factor' => 2.50,
            'review_interval' => 0,
            'repetitions' => 0,
            'card_type' => 'new',
        ]);
        $card->save();

        /** @var Card[] $foundCards */
        $foundCards = Card::where(['front' => 'Searchable Question']);

        $this->assertNotEmpty($foundCards);
        $this->assertEquals('Searchable Question', $foundCards[0]->front);
    }

    // ------------ relationships ------------
    public function test_card_belongs_to_deck(): void
    {
        $card = new Card([
            'front' => 'Deck Relationship Question',
            'back' => 'Deck Relationship Answer',
            'deck_id' => $this->testDeck->id,
            'ease_factor' => 2.50,
            'review_interval' => 0,
            'repetitions' => 0,
            'card_type' => 'new',
        ]);
        $card->save();

        $deck = $card->deck;

        $this->assertInstanceOf(Deck::class, $deck);
        $this->assertEquals($this->testDeck->id, $deck->id);
        $this->assertEquals($this->testDeck->name, $deck->name);
    }

    // ------------ card state methods ------------
    public function test_is_new_returns_true_for_new_cards(): void
    {
        $card = new Card([
            'front' => 'New Card Question',
            'back' => 'New Card Answer',
            'deck_id' => $this->testDeck->id,
            'ease_factor' => 2.50,
            'review_interval' => 0,
            'repetitions' => 0,
            'next_review' => null,
            'card_type' => 'new',
            'last_reviewed' => null,
        ]);
        $card->save();

        $this->assertTrue($card->isNew());
    }

    public function test_is_new_returns_false_for_learning_cards(): void
    {
        $card = new Card([
            'front' => 'Learning Card Question',
            'back' => 'Learning Card Answer',
            'deck_id' => $this->testDeck->id,
            'ease_factor' => 2.50,
            'review_interval' => 1,
            'repetitions' => 1,
            'next_review' => date('Y-m-d', strtotime('+1 day')),
            'card_type' => 'learning',
            'last_reviewed' => date('Y-m-d'),
        ]);
        $card->save();

        $this->assertFalse($card->isNew());
    }

    public function test_is_due_returns_false_for_new_cards(): void
    {
        $card = new Card([
            'front' => 'New Card Question',
            'back' => 'New Card Answer',
            'deck_id' => $this->testDeck->id,
            'ease_factor' => 2.50,
            'review_interval' => 0,
            'repetitions' => 0,
            'next_review' => null,
            'card_type' => 'new',
            'last_reviewed' => null,
        ]);
        $card->save();

        $this->assertFalse($card->isDue());
    }

    public function test_is_due_returns_true_for_cards_with_past_review_date(): void
    {
        $card = new Card([
            'front' => 'Due Card Question',
            'back' => 'Due Card Answer',
            'deck_id' => $this->testDeck->id,
            'ease_factor' => 2.50,
            'review_interval' => 1,
            'repetitions' => 1,
            'next_review' => date('Y-m-d', strtotime('-1 day')), // Yesterday
            'card_type' => 'learning',
            'last_reviewed' => date('Y-m-d', strtotime('-2 days')),
        ]);
        $card->save();

        $this->assertTrue($card->isDue());
    }

    public function test_is_due_returns_false_for_cards_with_future_review_date(): void
    {
        $card = new Card([
            'front' => 'Future Card Question',
            'back' => 'Future Card Answer',
            'deck_id' => $this->testDeck->id,
            'ease_factor' => 2.50,
            'review_interval' => 1,
            'repetitions' => 1,
            'next_review' => date('Y-m-d', strtotime('+1 day')), // Tomorrow
            'card_type' => 'learning',
            'last_reviewed' => date('Y-m-d'),
        ]);
        $card->save();

        $this->assertFalse($card->isDue());
    }

    public function test_is_due_returns_true_for_cards_with_today_review_date(): void
    {
        $card = new Card([
            'front' => 'Today Card Question',
            'back' => 'Today Card Answer',
            'deck_id' => $this->testDeck->id,
            'ease_factor' => 2.50,
            'review_interval' => 1,
            'repetitions' => 1,
            'next_review' => date('Y-m-d'), // Today
            'card_type' => 'learning',
            'last_reviewed' => date('Y-m-d', strtotime('-1 day')),
        ]);
        $card->save();

        $this->assertTrue($card->isDue());
    }

    public function test_is_due_returns_false_when_next_review_is_null(): void
    {
        $card = new Card([
            'front' => 'No Review Date Card',
            'back' => 'No Review Date Answer',
            'deck_id' => $this->testDeck->id,
            'ease_factor' => 2.50,
            'review_interval' => 1,
            'repetitions' => 1,
            'next_review' => null,
            'card_type' => 'learning',
            'last_reviewed' => date('Y-m-d'),
        ]);
        $card->save();

        $this->assertFalse($card->isDue());
    }

    // ------------ spaced repetition properties ------------
    public function test_card_starts_with_default_ease_factor(): void
    {
        $card = new Card([
            'front' => 'Question',
            'back' => 'Answer',
            'deck_id' => $this->testDeck->id,
            'ease_factor' => 2.50,
            'review_interval' => 0,
            'repetitions' => 0,
            'card_type' => 'new',
        ]);
        $card->save();

        $this->assertEquals(2.50, $card->ease_factor);
    }

    public function test_card_starts_with_zero_repetitions(): void
    {
        $card = new Card([
            'front' => 'Question',
            'back' => 'Answer',
            'deck_id' => $this->testDeck->id,
            'ease_factor' => 2.50,
            'review_interval' => 0,
            'repetitions' => 0,
            'card_type' => 'new',
        ]);
        $card->save();

        $this->assertEquals(0, $card->repetitions);
    }

    public function test_card_can_update_spaced_repetition_properties(): void
    {
        $card = new Card([
            'front' => 'Question',
            'back' => 'Answer',
            'deck_id' => $this->testDeck->id,
            'ease_factor' => 2.50,
            'review_interval' => 0,
            'repetitions' => 0,
            'card_type' => 'new',
        ]);
        $card->save();
        $cardId = $card->id;

        // Simulate a review
        $card->ease_factor = 2.60;
        $card->review_interval = 1;
        $card->repetitions = 1;
        $card->card_type = 'learning';
        $card->last_reviewed = date('Y-m-d');
        $card->next_review = date('Y-m-d', strtotime('+1 day'));
        $card->save();

        $updatedCard = Card::findById($cardId);
        $this->assertEquals(2.60, $updatedCard->ease_factor);
        $this->assertEquals(1, $updatedCard->review_interval);
        $this->assertEquals(1, $updatedCard->repetitions);
        $this->assertEquals('learning', $updatedCard->card_type);
    }
}
