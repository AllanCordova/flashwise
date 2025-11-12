<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Controllers\DecksController;
use App\Models\Deck;
use App\Models\User;
use Lib\Authentication\Auth;
use Lib\FlashMessage;

class DecksControllerTest extends ControllerTestCase
{
    private User $user;
    private User $admin;

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

        $this->admin = new User([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => 'admin123',
            'password_confirmation' => 'admin123',
            'role' => 'admin'
        ]);
        $this->admin->save();
    }

    public function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    // ------------ index ------------
    public function test_index_should_render_view_for_common_user(): void
    {
        Auth::login($this->user);
        $this->assertTrue(Auth::check());

        $output = $this->get('index', DecksController::class);

        $this->assertStringNotContainsString('Location:', $output);
        $this->assertEmpty(FlashMessage::get());
    }

    public function test_index_should_render_view_for_admin_user(): void
    {
        Auth::login($this->admin);
        $this->assertTrue(Auth::check());

        $output = $this->get('index', DecksController::class);

        $this->assertStringNotContainsString('Location:', $output);
        $this->assertEmpty(FlashMessage::get());
    }

    // ------------ new ------------
    public function test_new_should_render_form_for_logged_user(): void
    {
        Auth::login($this->user);
        $this->assertTrue(Auth::check());

        $output = $this->get('new', DecksController::class);

        $this->assertStringNotContainsString('Location:', $output);
        $this->assertEmpty(FlashMessage::get());
    }

    // ------------ create ------------
    public function test_create_should_create_deck_with_valid_data(): void
    {
        Auth::login($this->user);

        $params = [
            'deck' => [
                'name' => 'New Deck',
                'description' => 'Test Description'
            ]
        ];

        $output = $this->post('create', DecksController::class, $params);
        $messages = FlashMessage::get();

        $this->assertStringContainsString('Location: /decks', $output);
        $this->assertArrayHasKey('success', $messages);
        $this->assertStringContainsString('Deck criado com sucesso', $messages['success']);

        $decks = Deck::where(['name' => 'New Deck']);
        $this->assertNotEmpty($decks);
        $deck = $decks[0];
        $this->assertEquals('New Deck', $deck->name);
        $this->assertEquals('Test Description', $deck->description);
        $this->assertEquals($this->user->id, $deck->user_id);
    }

    public function test_create_should_fail_with_empty_name(): void
    {
        Auth::login($this->user);

        $params = [
            'deck' => [
                'name' => '',
                'description' => 'Test Description'
            ]
        ];

        $output = $this->post('create', DecksController::class, $params);

        $this->assertStringNotContainsString('Location:', $output);

        $decks = Deck::where(['description' => 'Test Description']);
        $this->assertEmpty($decks);
    }

    public function test_create_should_fail_with_duplicate_name(): void
    {
        Auth::login($this->user);

        $firstDeck = new Deck([
            'name' => 'Duplicate Deck',
            'description' => 'First',
            'user_id' => $this->user->id
        ]);
        $firstDeck->save();

        $params = [
            'deck' => [
                'name' => 'Duplicate Deck',
                'description' => 'Second'
            ]
        ];

        $output = $this->post('create', DecksController::class, $params);

        $this->assertStringNotContainsString('Location:', $output);

        $decks = Deck::where(['name' => 'Duplicate Deck']);
        $this->assertCount(1, $decks);
        $this->assertEquals('First', $decks[0]->description);
    }

    // ------------ edit ------------
    public function test_edit_should_render_form_for_deck_owner(): void
    {
        Auth::login($this->user);

        $deck = new Deck([
            'name' => 'Edit Deck',
            'description' => 'Edit Description',
            'user_id' => $this->user->id
        ]);
        $deck->save();

        $params = ['id' => $deck->id];
        $output = $this->get('edit', DecksController::class, $params);

        $this->assertStringNotContainsString('Location:', $output);
        $this->assertEmpty(FlashMessage::get());
    }

    public function test_edit_should_redirect_if_deck_not_found(): void
    {
        Auth::login($this->user);

        $params = ['id' => 99999];
        $output = $this->get('edit', DecksController::class, $params);
        $messages = FlashMessage::get();

        $this->assertStringContainsString('Location: /decks', $output);
        $this->assertArrayHasKey('danger', $messages);
        $this->assertStringContainsString('Deck não encontrado', $messages['danger']);
    }

    // ------------ update ------------
    public function test_update_should_update_deck_with_valid_data(): void
    {
        Auth::login($this->user);

        $deck = new Deck([
            'name' => 'Original Name',
            'description' => 'Original Description',
            'user_id' => $this->user->id
        ]);
        $deck->save();

        $params = [
            'id' => $deck->id,
            'deck' => [
                'name' => 'Updated Name',
                'description' => 'Updated Description'
            ]
        ];

        $output = $this->put('update', DecksController::class, $params);
        $messages = FlashMessage::get();

        $this->assertStringContainsString('Location: /decks', $output);
        $this->assertArrayHasKey('success', $messages);
        $this->assertStringContainsString('Deck atualizado com sucesso', $messages['success']);

        $updatedDeck = Deck::findById($deck->id);
        $this->assertEquals('Updated Name', $updatedDeck->name);
        $this->assertEquals('Updated Description', $updatedDeck->description);
    }

    public function test_update_should_fail_with_empty_data(): void
    {
        Auth::login($this->user);

        $deck = new Deck([
            'name' => 'Original Name',
            'description' => 'Original Description',
            'user_id' => $this->user->id
        ]);
        $deck->save();

        $params = [
            'id' => $deck->id,
            'deck' => [
                'name' => '',
                'description' => ''
            ]
        ];

        $output = $this->put('update', DecksController::class, $params);

        $this->assertStringNotContainsString('Location:', $output);

        $unchangedDeck = Deck::findById($deck->id);
        $this->assertEquals('Original Name', $unchangedDeck->name);
        $this->assertEquals('Original Description', $unchangedDeck->description);
    }

    public function test_update_should_redirect_if_deck_not_found(): void
    {
        Auth::login($this->user);

        $params = [
            'id' => 99999,
            'deck' => [
                'name' => 'Test',
                'description' => 'Test'
            ]
        ];

        $output = $this->put('update', DecksController::class, $params);
        $messages = FlashMessage::get();

        $this->assertStringContainsString('Location: /decks', $output);
        $this->assertArrayHasKey('danger', $messages);
        $this->assertStringContainsString('Deck não encontrado', $messages['danger']);
    }

    // ------------ show ------------
    public function test_show_should_display_deck_for_owner(): void
    {
        Auth::login($this->user);

        $deck = new Deck([
            'name' => 'Show Deck',
            'description' => 'Show Description',
            'user_id' => $this->user->id
        ]);
        $deck->save();

        $params = ['id' => $deck->id];
        $output = $this->get('show', DecksController::class, $params);

        $this->assertStringNotContainsString('Location:', $output);
        $this->assertEmpty(FlashMessage::get());
    }

    public function test_show_should_redirect_if_deck_not_found(): void
    {
        Auth::login($this->user);

        $params = ['id' => 99999];
        $output = $this->get('show', DecksController::class, $params);
        $messages = FlashMessage::get();

        $this->assertStringContainsString('Location: /decks', $output);
        $this->assertArrayHasKey('danger', $messages);
        $this->assertStringContainsString('Deck não encontrado', $messages['danger']);
    }

    // ------------ destroy ------------
    public function test_destroy_should_delete_deck_for_owner(): void
    {
        Auth::login($this->user);

        $deck = new Deck([
            'name' => 'Delete Deck',
            'description' => 'Delete Description',
            'user_id' => $this->user->id
        ]);
        $deck->save();
        $deckId = $deck->id;

        $params = ['id' => $deckId];
        $output = $this->post('destroy', DecksController::class, $params);
        $messages = FlashMessage::get();

        $this->assertStringContainsString('Location: /decks', $output);
        $this->assertArrayHasKey('success', $messages);
        $this->assertStringContainsString('Deck excluído com sucesso', $messages['success']);

        $deletedDeck = Deck::findById($deckId);
        $this->assertNull($deletedDeck);
    }

    public function test_destroy_should_redirect_if_deck_not_found(): void
    {
        Auth::login($this->user);

        $params = ['id' => 99999];
        $output = $this->post('destroy', DecksController::class, $params);
        $messages = FlashMessage::get();

        $this->assertStringContainsString('Location: /decks', $output);
        $this->assertArrayHasKey('danger', $messages);
        $this->assertStringContainsString('Deck não encontrado', $messages['danger']);
    }

    // ------------ index with sorting ------------
    public function test_index_should_use_custom_paginator_for_review_priority_sort(): void
    {
        Auth::login($this->user);

        // Create decks for sorting
        $deck1 = new Deck(['name' => 'Deck 1', 'description' => 'Test', 'user_id' => $this->user->id]);
        $deck1->save();

        $deck2 = new Deck(['name' => 'Deck 2', 'description' => 'Test', 'user_id' => $this->user->id]);
        $deck2->save();

        // Set sort parameter
        $_GET['sort'] = 'review_priority';

        $output = $this->get('index', DecksController::class);

        $this->assertStringNotContainsString('Location:', $output);
        $this->assertEmpty(FlashMessage::get());
    }

    public function test_index_should_handle_name_asc_sort(): void
    {
        Auth::login($this->user);

        $_GET['sort'] = 'name_asc';

        $output = $this->get('index', DecksController::class);

        $this->assertStringNotContainsString('Location:', $output);
        $this->assertEmpty(FlashMessage::get());
    }

    public function test_index_should_handle_pagination(): void
    {
        Auth::login($this->user);

        $_GET['page'] = '2';
        $_GET['sort'] = 'created_desc';

        $output = $this->get('index', DecksController::class);

        $this->assertStringNotContainsString('Location:', $output);
        $this->assertEmpty(FlashMessage::get());
    }
}
