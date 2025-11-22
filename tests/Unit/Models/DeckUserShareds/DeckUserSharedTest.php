<?php

namespace Tests\Unit\Models\DeckUserShareds;

use App\Models\Deck;
use App\Models\DeckUserShared;
use App\Models\User;
use Tests\TestCase;

class DeckUserSharedTest extends TestCase
{
    private ?User $testUser = null;
    private ?User $ownerUser = null;
    private ?Deck $testDeck = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->testUser = new User([
            'name' => 'Test User',
            'email' => 'testshared@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'user',
        ]);
        $this->testUser->save();

        $this->ownerUser = new User([
            'name' => 'Owner User',
            'email' => 'owner@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'user',
        ]);
        $this->ownerUser->save();

        $this->testDeck = new Deck([
            'name' => 'Test Deck',
            'description' => 'Test Description',
            'user_id' => $this->ownerUser->id,
        ]);
        $this->testDeck->save();
    }

    // ------------ creation ------------
    public function test_should_create_deck_user_shared_with_valid_data(): void
    {
        $deckUserShared = new DeckUserShared([
            'deck_id' => $this->testDeck->id,
            'user_id' => $this->testUser->id,
        ]);

        $this->assertTrue($deckUserShared->save());
        $this->assertNull($deckUserShared->errors('deck_id'));
        $this->assertNull($deckUserShared->errors('user_id'));

        $this->assertGreaterThan(0, $deckUserShared->id);
        $this->assertEquals($this->testDeck->id, $deckUserShared->deck_id);
        $this->assertEquals($this->testUser->id, $deckUserShared->user_id);
    }

    public function test_should_not_save_with_invalid_data(): void
    {
        $deckUserShared = new DeckUserShared();

        $this->assertFalse($deckUserShared->save());
        $this->assertTrue($deckUserShared->hasErrors());
        $this->assertEquals('não pode ser vazio!', $deckUserShared->errors('deck_id'));
        $this->assertEquals('não pode ser vazio!', $deckUserShared->errors('user_id'));
    }

    // ------------ validation ------------
    public function test_should_fail_if_deck_id_is_empty(): void
    {
        $deckUserShared = new DeckUserShared([
            'deck_id' => null,
            'user_id' => $this->testUser->id,
        ]);

        $this->assertFalse($deckUserShared->save());
        $this->assertTrue($deckUserShared->hasErrors());
        $this->assertEquals('não pode ser vazio!', $deckUserShared->errors('deck_id'));
    }

    public function test_should_fail_if_user_id_is_empty(): void
    {
        $deckUserShared = new DeckUserShared([
            'deck_id' => $this->testDeck->id,
            'user_id' => null,
        ]);

        $this->assertFalse($deckUserShared->save());
        $this->assertTrue($deckUserShared->hasErrors());
        $this->assertEquals('não pode ser vazio!', $deckUserShared->errors('user_id'));
    }

    public function test_should_fail_if_deck_user_combination_is_not_unique(): void
    {
        $deckUserShared1 = new DeckUserShared([
            'deck_id' => $this->testDeck->id,
            'user_id' => $this->testUser->id,
        ]);
        $deckUserShared1->save();

        $deckUserShared2 = new DeckUserShared([
            'deck_id' => $this->testDeck->id,
            'user_id' => $this->testUser->id,
        ]);

        $this->assertFalse($deckUserShared2->save());
        $this->assertTrue($deckUserShared2->hasErrors());
        $this->assertEquals('já existe um registro com esse dado', $deckUserShared2->errors('deck_id'));
    }

    public function test_should_allow_same_deck_shared_with_different_users(): void
    {
        $anotherUser = new User([
            'name' => 'Another User',
            'email' => 'another@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'user',
        ]);
        $anotherUser->save();

        $deckUserShared1 = new DeckUserShared([
            'deck_id' => $this->testDeck->id,
            'user_id' => $this->testUser->id,
        ]);
        $this->assertTrue($deckUserShared1->save());

        $deckUserShared2 = new DeckUserShared([
            'deck_id' => $this->testDeck->id,
            'user_id' => $anotherUser->id,
        ]);
        $this->assertTrue($deckUserShared2->save());

        $this->assertGreaterThan(0, $deckUserShared1->id);
        $this->assertGreaterThan(0, $deckUserShared2->id);
        $this->assertEquals($this->testDeck->id, $deckUserShared1->deck_id);
        $this->assertEquals($this->testDeck->id, $deckUserShared2->deck_id);
        $this->assertEquals($this->testUser->id, $deckUserShared1->user_id);
        $this->assertEquals($anotherUser->id, $deckUserShared2->user_id);
    }

    public function test_should_allow_same_user_with_different_decks(): void
    {
        $anotherDeck = new Deck([
            'name' => 'Another Deck',
            'description' => 'Another Description',
            'user_id' => $this->ownerUser->id,
        ]);
        $anotherDeck->save();

        $deckUserShared1 = new DeckUserShared([
            'deck_id' => $this->testDeck->id,
            'user_id' => $this->testUser->id,
        ]);
        $this->assertTrue($deckUserShared1->save());

        $deckUserShared2 = new DeckUserShared([
            'deck_id' => $anotherDeck->id,
            'user_id' => $this->testUser->id,
        ]);
        $this->assertTrue($deckUserShared2->save());

        $this->assertGreaterThan(0, $deckUserShared1->id);
        $this->assertGreaterThan(0, $deckUserShared2->id);
        $this->assertEquals($this->testDeck->id, $deckUserShared1->deck_id);
        $this->assertEquals($anotherDeck->id, $deckUserShared2->deck_id);
        $this->assertEquals($this->testUser->id, $deckUserShared1->user_id);
        $this->assertEquals($this->testUser->id, $deckUserShared2->user_id);
    }

    public function test_deck_user_shared_can_be_found_by_id(): void
    {
        $deckUserShared = new DeckUserShared([
            'deck_id' => $this->testDeck->id,
            'user_id' => $this->testUser->id,
        ]);
        $deckUserShared->save();

        $foundDeckUserShared = DeckUserShared::findById($deckUserShared->id);

        $this->assertNotNull($foundDeckUserShared);
        $this->assertInstanceOf(DeckUserShared::class, $foundDeckUserShared);
        $this->assertEquals($deckUserShared->id, $foundDeckUserShared->id);
        $this->assertEquals($this->testDeck->id, $foundDeckUserShared->deck_id);
        $this->assertEquals($this->testUser->id, $foundDeckUserShared->user_id);
    }

    public function test_find_by_id_should_return_null_if_not_found(): void
    {
        $foundDeckUserShared = DeckUserShared::findById(99999);
        $this->assertNull($foundDeckUserShared);
    }

    public function test_deck_user_shared_can_be_found_with_where_clause(): void
    {
        $deckUserShared = new DeckUserShared([
            'deck_id' => $this->testDeck->id,
            'user_id' => $this->testUser->id,
        ]);
        $deckUserShared->save();

        /** @var DeckUserShared[] $foundShares */
        $foundShares = DeckUserShared::where([
            'deck_id' => $this->testDeck->id,
            'user_id' => $this->testUser->id
        ]);

        $this->assertNotEmpty($foundShares);
        $this->assertEquals($this->testDeck->id, $foundShares[0]->deck_id);
        $this->assertEquals($this->testUser->id, $foundShares[0]->user_id);
    }

    public function test_deck_user_shared_can_be_found_by_deck_id(): void
    {
        $user2 = new User([
            'name' => 'User 2',
            'email' => 'user2@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'user',
        ]);
        $user2->save();

        $share1 = new DeckUserShared([
            'deck_id' => $this->testDeck->id,
            'user_id' => $this->testUser->id,
        ]);
        $share1->save();

        $share2 = new DeckUserShared([
            'deck_id' => $this->testDeck->id,
            'user_id' => $user2->id,
        ]);
        $share2->save();

        /** @var DeckUserShared[] $foundShares */
        $foundShares = DeckUserShared::where(['deck_id' => $this->testDeck->id]);

        $this->assertCount(2, $foundShares);
    }

    public function test_deck_user_shared_can_be_found_by_user_id(): void
    {
        $deck2 = new Deck([
            'name' => 'Deck 2',
            'description' => 'Description 2',
            'user_id' => $this->ownerUser->id,
        ]);
        $deck2->save();

        $share1 = new DeckUserShared([
            'deck_id' => $this->testDeck->id,
            'user_id' => $this->testUser->id,
        ]);
        $share1->save();

        $share2 = new DeckUserShared([
            'deck_id' => $deck2->id,
            'user_id' => $this->testUser->id,
        ]);
        $share2->save();

        /** @var DeckUserShared[] $foundShares */
        $foundShares = DeckUserShared::where(['user_id' => $this->testUser->id]);

        $this->assertCount(2, $foundShares);
    }

    public function test_deck_user_shared_can_be_deleted(): void
    {
        $deckUserShared = new DeckUserShared([
            'deck_id' => $this->testDeck->id,
            'user_id' => $this->testUser->id,
        ]);
        $deckUserShared->save();
        $shareId = $deckUserShared->id;

        $this->assertTrue($deckUserShared->destroy());
        $this->assertNull(DeckUserShared::findById($shareId));
    }

    public function test_all_deck_user_shared_can_be_retrieved(): void
    {
        $user2 = new User([
            'name' => 'User 2',
            'email' => 'user2all@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'user',
        ]);
        $user2->save();

        $deck2 = new Deck([
            'name' => 'Deck 2',
            'description' => 'Description 2',
            'user_id' => $this->ownerUser->id,
        ]);
        $deck2->save();

        $share1 = new DeckUserShared([
            'deck_id' => $this->testDeck->id,
            'user_id' => $this->testUser->id,
        ]);
        $share1->save();

        $share2 = new DeckUserShared([
            'deck_id' => $deck2->id,
            'user_id' => $user2->id,
        ]);
        $share2->save();

        $allShares = DeckUserShared::all();

        $this->assertGreaterThanOrEqual(2, count($allShares));
    }

    // ------------ relationships ------------
    public function test_deck_user_shared_belongs_to_deck(): void
    {
        $deckUserShared = new DeckUserShared([
            'deck_id' => $this->testDeck->id,
            'user_id' => $this->testUser->id,
        ]);
        $deckUserShared->save();

        $deck = $deckUserShared->deck;

        $this->assertInstanceOf(Deck::class, $deck);
        $this->assertEquals($this->testDeck->id, $deck->id);
        $this->assertEquals($this->testDeck->name, $deck->name);
        $this->assertEquals($this->testDeck->description, $deck->description);
    }

    public function test_deck_user_shared_belongs_to_user(): void
    {
        $deckUserShared = new DeckUserShared([
            'deck_id' => $this->testDeck->id,
            'user_id' => $this->testUser->id,
        ]);
        $deckUserShared->save();

        $user = $deckUserShared->user;

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($this->testUser->id, $user->id);
        $this->assertEquals($this->testUser->email, $user->email);
        $this->assertEquals($this->testUser->name, $user->name);
    }
}
