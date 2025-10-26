<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Controllers\CardsController;
use App\Models\Card;
use App\Models\Deck;
use App\Models\User;
use Lib\Authentication\Auth;
use Lib\FlashMessage;

class CardsControllerTest extends ControllerTestCase
{
    private User $user;
    private Deck $deck;

    public function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];

        $this->user = new User([
            'name' => 'Test User',
            'email' => 'user@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'user'
        ]);
        $this->user->save();

        $this->deck = new Deck([
            'name' => 'Test Deck',
            'description' => 'Test Description',
            'user_id' => $this->user->id
        ]);
        $this->deck->save();
    }

    public function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    // ------------ new ------------
    public function test_new_should_render_form_when_user_has_decks(): void
    {
        Auth::login($this->user);

        $output = $this->get('new', CardsController::class);

        $this->assertStringNotContainsString('Location:', $output);
        $this->assertEmpty(FlashMessage::get());
    }

    public function test_new_should_redirect_when_user_has_no_decks(): void
    {
        $userWithoutDecks = new User([
            'name' => 'No Decks User',
            'email' => 'nodecks@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'user'
        ]);
        $userWithoutDecks->save();

        Auth::login($userWithoutDecks);

        $output = $this->get('new', CardsController::class);
        $messages = FlashMessage::get();

        $this->assertStringContainsString('Location: /decks/new', $output);
        $this->assertArrayHasKey('danger', $messages);
        $this->assertStringContainsString('Você precisa criar um deck antes de adicionar cards', $messages['danger']);
    }

    // ------------ create ------------
    public function test_create_should_create_card_with_valid_data(): void
    {
        Auth::login($this->user);

        $params = [
            'card' => [
                'front' => 'What is PHP?',
                'back' => 'PHP is a programming language',
                'deck_id' => $this->deck->id
            ]
        ];

        $output = $this->post('create', CardsController::class, $params);
        $messages = FlashMessage::get();

        $this->assertStringContainsString('Location: /decks', $output);
        $this->assertArrayHasKey('success', $messages);
        $this->assertStringContainsString('Card criado com sucesso', $messages['success']);

        /** @var Card[] $cards */
        $cards = Card::where(['front' => 'What is PHP?']);
        $this->assertNotEmpty($cards);
        $card = $cards[0];
        $this->assertEquals('What is PHP?', $card->front);
        $this->assertEquals('PHP is a programming language', $card->back);
        $this->assertEquals($this->deck->id, $card->deck_id);
        $this->assertEquals('new', $card->card_type);
        $this->assertEquals(2.50, $card->ease_factor);
    }

    public function test_create_should_fail_with_empty_front(): void
    {
        Auth::login($this->user);

        $params = [
            'card' => [
                'front' => '',
                'back' => 'Answer',
                'deck_id' => $this->deck->id
            ]
        ];

        $output = $this->post('create', CardsController::class, $params);

        $this->assertStringNotContainsString('Location:', $output);

        /** @var Card[] $cards */
        $cards = Card::where(['back' => 'Answer']);
        $this->assertEmpty($cards);
    }

    public function test_create_should_fail_with_empty_back(): void
    {
        Auth::login($this->user);

        $params = [
            'card' => [
                'front' => 'Question',
                'back' => '',
                'deck_id' => $this->deck->id
            ]
        ];

        $output = $this->post('create', CardsController::class, $params);

        $this->assertStringNotContainsString('Location:', $output);

        /** @var Card[] $cards */
        $cards = Card::where(['front' => 'Question', 'deck_id' => $this->deck->id]);
        $this->assertEmpty($cards);
    }

    public function test_create_should_fail_with_invalid_deck(): void
    {
        Auth::login($this->user);

        $params = [
            'card' => [
                'front' => 'Question',
                'back' => 'Answer',
                'deck_id' => 99999
            ]
        ];

        $output = $this->post('create', CardsController::class, $params);

        $this->assertStringNotContainsString('Location:', $output);

        /** @var Card[] $cards */
        $cards = Card::where(['deck_id' => 99999]);
        $this->assertEmpty($cards);
    }

    public function test_create_should_fail_with_deck_from_another_user(): void
    {
        $otherUser = new User([
            'name' => 'Other User',
            'email' => 'other@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'user'
        ]);
        $otherUser->save();

        $otherDeck = new Deck([
            'name' => 'Other Deck',
            'description' => 'Other Description',
            'user_id' => $otherUser->id
        ]);
        $otherDeck->save();

        // Try to create card in other user's deck
        Auth::login($this->user);

        $params = [
            'card' => [
                'front' => 'Question',
                'back' => 'Answer',
                'deck_id' => $otherDeck->id
            ]
        ];

        $output = $this->post('create', CardsController::class, $params);

        $this->assertStringNotContainsString('Location:', $output);

        /** @var Card[] $cards */
        $cards = Card::where(['deck_id' => $otherDeck->id]);
        $this->assertEmpty($cards);
    }

    // ------------ edit ------------
    public function test_edit_should_render_form_for_card_owner(): void
    {
        Auth::login($this->user);

        $card = new Card([
            'front' => 'Question',
            'back' => 'Answer',
            'deck_id' => $this->deck->id,
            'ease_factor' => 2.50,
            'review_interval' => 0,
            'repetitions' => 0,
            'next_review' => null,
            'card_type' => 'new',
            'last_reviewed' => null,
        ]);
        $card->save();

        $params = ['id' => $card->id];
        $output = $this->get('edit', CardsController::class, $params);

        $this->assertStringNotContainsString('Location:', $output);
        $this->assertEmpty(FlashMessage::get());
    }

    public function test_edit_should_redirect_if_card_not_found(): void
    {
        Auth::login($this->user);

        $params = ['id' => 99999];
        $output = $this->get('edit', CardsController::class, $params);
        $messages = FlashMessage::get();

        $this->assertStringContainsString('Location: /decks', $output);
        $this->assertArrayHasKey('danger', $messages);
        $this->assertStringContainsString('Card não encontrado', $messages['danger']);
    }

    public function test_edit_should_redirect_if_card_belongs_to_another_user(): void
    {
        $otherUser = new User([
            'name' => 'Other User',
            'email' => 'other@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'user'
        ]);
        $otherUser->save();

        $otherDeck = new Deck([
            'name' => 'Other Deck',
            'description' => 'Other Description',
            'user_id' => $otherUser->id
        ]);
        $otherDeck->save();

        $otherCard = new Card([
            'front' => 'Question',
            'back' => 'Answer',
            'deck_id' => $otherDeck->id,
            'ease_factor' => 2.50,
            'review_interval' => 0,
            'repetitions' => 0,
            'next_review' => null,
            'card_type' => 'new',
            'last_reviewed' => null,
        ]);
        $otherCard->save();

        Auth::login($this->user);

        $params = ['id' => $otherCard->id];
        $output = $this->get('edit', CardsController::class, $params);
        $messages = FlashMessage::get();

        $this->assertStringContainsString('Location: /decks', $output);
        $this->assertArrayHasKey('danger', $messages);
        $this->assertStringContainsString('Você não tem permissão para editar este card', $messages['danger']);
    }

    // ------------ update ------------
    public function test_update_should_update_card_with_valid_data(): void
    {
        Auth::login($this->user);

        $card = new Card([
            'front' => 'Original Question',
            'back' => 'Original Answer',
            'deck_id' => $this->deck->id,
            'ease_factor' => 2.50,
            'review_interval' => 0,
            'repetitions' => 0,
            'next_review' => null,
            'card_type' => 'new',
            'last_reviewed' => null,
        ]);
        $card->save();

        $params = [
            'id' => $card->id,
            'card' => [
                'front' => 'Updated Question',
                'back' => 'Updated Answer',
                'deck_id' => $this->deck->id
            ]
        ];

        $output = $this->put('update', CardsController::class, $params);
        $messages = FlashMessage::get();

        $this->assertStringContainsString('Location: /decks/' . $this->deck->id . '/edit', $output);
        $this->assertArrayHasKey('success', $messages);
        $this->assertStringContainsString('Card atualizado com sucesso', $messages['success']);

        $updatedCard = Card::findById($card->id);
        $this->assertEquals('Updated Question', $updatedCard->front);
        $this->assertEquals('Updated Answer', $updatedCard->back);
    }

    public function test_update_should_fail_with_empty_data(): void
    {
        Auth::login($this->user);

        $card = new Card([
            'front' => 'Original Question',
            'back' => 'Original Answer',
            'deck_id' => $this->deck->id,
            'ease_factor' => 2.50,
            'review_interval' => 0,
            'repetitions' => 0,
            'next_review' => null,
            'card_type' => 'new',
            'last_reviewed' => null,
        ]);
        $card->save();

        $params = [
            'id' => $card->id,
            'card' => [
                'front' => '',
                'back' => '',
                'deck_id' => $this->deck->id
            ]
        ];

        $output = $this->put('update', CardsController::class, $params);

        $this->assertStringNotContainsString('Location:', $output);

        $unchangedCard = Card::findById($card->id);
        $this->assertEquals('Original Question', $unchangedCard->front);
        $this->assertEquals('Original Answer', $unchangedCard->back);
    }

    public function test_update_should_redirect_if_card_not_found(): void
    {
        Auth::login($this->user);

        $params = [
            'id' => 99999,
            'card' => [
                'front' => 'Question',
                'back' => 'Answer',
                'deck_id' => $this->deck->id
            ]
        ];

        $output = $this->put('update', CardsController::class, $params);
        $messages = FlashMessage::get();

        $this->assertStringContainsString('Location: /decks', $output);
        $this->assertArrayHasKey('danger', $messages);
        $this->assertStringContainsString('Card não encontrado', $messages['danger']);
    }

    // ------------ destroy ------------
    public function test_destroy_should_delete_card_for_owner(): void
    {
        Auth::login($this->user);

        $card = new Card([
            'front' => 'Delete Question',
            'back' => 'Delete Answer',
            'deck_id' => $this->deck->id,
            'ease_factor' => 2.50,
            'review_interval' => 0,
            'repetitions' => 0,
            'next_review' => null,
            'card_type' => 'new',
            'last_reviewed' => null,
        ]);
        $card->save();
        $cardId = $card->id;

        $params = ['id' => $cardId];
        $output = $this->post('destroy', CardsController::class, $params);
        $messages = FlashMessage::get();

        $this->assertStringContainsString('Location: /decks/' . $this->deck->id . '/edit', $output);
        $this->assertArrayHasKey('success', $messages);
        $this->assertStringContainsString('Card removido com sucesso', $messages['success']);

        $deletedCard = Card::findById($cardId);
        $this->assertNull($deletedCard);
    }

    public function test_destroy_should_redirect_if_card_not_found(): void
    {
        Auth::login($this->user);

        $params = ['id' => 99999];
        $output = $this->post('destroy', CardsController::class, $params);
        $messages = FlashMessage::get();

        $this->assertStringContainsString('Location: /decks', $output);
        $this->assertArrayHasKey('danger', $messages);
        $this->assertStringContainsString('Card não encontrado', $messages['danger']);
    }

    public function test_destroy_should_redirect_if_card_belongs_to_another_user(): void
    {
        $otherUser = new User([
            'name' => 'Other User',
            'email' => 'other@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'user'
        ]);
        $otherUser->save();

        $otherDeck = new Deck([
            'name' => 'Other Deck',
            'description' => 'Other Description',
            'user_id' => $otherUser->id
        ]);
        $otherDeck->save();

        $otherCard = new Card([
            'front' => 'Question',
            'back' => 'Answer',
            'deck_id' => $otherDeck->id,
            'ease_factor' => 2.50,
            'review_interval' => 0,
            'repetitions' => 0,
            'next_review' => null,
            'card_type' => 'new',
            'last_reviewed' => null,
        ]);
        $otherCard->save();

        Auth::login($this->user);

        $params = ['id' => $otherCard->id];
        $output = $this->post('destroy', CardsController::class, $params);
        $messages = FlashMessage::get();

        $this->assertStringContainsString('Location: /decks', $output);
        $this->assertArrayHasKey('danger', $messages);
        $this->assertStringContainsString('Você não tem permissão para excluir este card', $messages['danger']);
    }
}
