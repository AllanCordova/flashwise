<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Controllers\SharedDecksController;
use App\Models\Deck;
use App\Models\DeckUserShared;
use App\Models\User;
use App\Services\ShareTokenService;
use Lib\Authentication\Auth;
use Lib\FlashMessage;

class SharedDecksControllerTest extends ControllerTestCase
{
    private User $user;
    private User $ownerUser;
    private Deck $deck;

    public function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
        $_GET = [];

        if (!isset($_ENV['FLASHWISE_SECRET_KEY'])) {
            $_ENV['FLASHWISE_SECRET_KEY'] = 'test-secret-key-for-unit-tests';
        }

        $this->user = new User([
            'name' => 'Test User',
            'email' => 'user@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'user'
        ]);
        $this->user->save();

        $this->ownerUser = new User([
            'name' => 'Owner User',
            'email' => 'owner@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'user'
        ]);
        $this->ownerUser->save();

        $this->deck = new Deck([
            'name' => 'Shared Deck',
            'description' => 'Test Description',
            'user_id' => $this->ownerUser->id
        ]);
        $this->deck->save();
    }

    public function tearDown(): void
    {
        $_SESSION = [];
        $_GET = [];
        parent::tearDown();
    }

    // ------------ index ------------
    public function test_index_should_render_view_with_shared_decks(): void
    {
        Auth::login($this->user);
        $this->assertTrue(Auth::check());

        $sharedDeck = new DeckUserShared([
            'deck_id' => $this->deck->id,
            'user_id' => $this->user->id
        ]);
        $sharedDeck->save();

        $output = $this->get('index', SharedDecksController::class);

        $this->assertStringNotContainsString('Location:', $output);
        $this->assertEmpty(FlashMessage::get());
    }

    public function test_index_should_render_view_with_empty_shared_decks(): void
    {
        Auth::login($this->user);
        $this->assertTrue(Auth::check());

        $output = $this->get('index', SharedDecksController::class);

        $this->assertStringNotContainsString('Location:', $output);
        $this->assertEmpty(FlashMessage::get());
    }

    public function test_index_should_handle_pagination(): void
    {
        Auth::login($this->user);
        $this->assertTrue(Auth::check());

        for ($i = 1; $i <= 3; $i++) {
            $deck = new Deck([
                'name' => "Shared Deck $i",
                'description' => "Description $i",
                'user_id' => $this->ownerUser->id
            ]);
            $deck->save();

            $sharedDeck = new DeckUserShared([
                'deck_id' => $deck->id,
                'user_id' => $this->user->id
            ]);
            $sharedDeck->save();
        }

        $_GET['page'] = '1';
        $output = $this->get('index', SharedDecksController::class);

        $this->assertStringNotContainsString('Location:', $output);
        $this->assertEmpty(FlashMessage::get());
    }

    // ------------ new (aceitar compartilhamento) ------------
    public function test_new_should_accept_share_with_valid_token(): void
    {
        Auth::login($this->user);
        $this->assertTrue(Auth::check());

        $token = ShareTokenService::generate($this->deck->id);
        $params = ['token' => $token];

        $output = $this->get('new', SharedDecksController::class, $params);
        $messages = FlashMessage::get();

        $this->assertStringContainsString('Location: /shared-decks', $output);
        $this->assertArrayHasKey('success', $messages);
        $this->assertStringContainsString('Deck compartilhado com sucesso!', $messages['success']);

        $sharedDecks = DeckUserShared::where([
            'deck_id' => $this->deck->id,
            'user_id' => $this->user->id
        ]);
        $this->assertCount(1, $sharedDecks);
        $this->assertEquals($this->deck->id, $sharedDecks[0]->deck_id);
        $this->assertEquals($this->user->id, $sharedDecks[0]->user_id);
    }

    public function test_new_should_fail_when_owner_tries_to_accept_own_share(): void
    {
        Auth::login($this->ownerUser);
        $this->assertTrue(Auth::check());

        $token = ShareTokenService::generate($this->deck->id);
        $params = ['token' => $token];

        $output = $this->get('new', SharedDecksController::class, $params);
        $messages = FlashMessage::get();

        $this->assertStringContainsString('Location: /decks', $output);
        $this->assertArrayHasKey('danger', $messages);
        $this->assertStringContainsString('Você não pode compartilhar o deck com você mesmo!', $messages['danger']);

        $sharedDecks = DeckUserShared::where([
            'deck_id' => $this->deck->id,
            'user_id' => $this->ownerUser->id
        ]);
        $this->assertEmpty($sharedDecks);
    }

    public function test_new_should_fail_with_invalid_token(): void
    {
        Auth::login($this->user);
        $this->assertTrue(Auth::check());

        $invalidToken = 'invalid-token-123';
        $params = ['token' => $invalidToken];

        $output = $this->get('new', SharedDecksController::class, $params);
        $messages = FlashMessage::get();

        $this->assertStringContainsString('Location: /decks', $output);
        $this->assertArrayHasKey('danger', $messages);
        $this->assertStringContainsString('Link de compartilhamento expirado', $messages['danger']);
    }

    public function test_new_should_fail_when_share_already_exists(): void
    {
        Auth::login($this->user);
        $this->assertTrue(Auth::check());

        $existingShare = new DeckUserShared([
            'deck_id' => $this->deck->id,
            'user_id' => $this->user->id
        ]);
        $existingShare->save();

        $token = ShareTokenService::generate($this->deck->id);
        $params = ['token' => $token];

        $output = $this->get('new', SharedDecksController::class, $params);
        $messages = FlashMessage::get();

        $this->assertStringContainsString('Location: /shared-decks', $output);
        $this->assertArrayHasKey('danger', $messages);
        $this->assertStringContainsString('Erro ao compartilhar deck', $messages['danger']);

        $sharedDecks = DeckUserShared::where([
            'deck_id' => $this->deck->id,
            'user_id' => $this->user->id
        ]);
        $this->assertCount(1, $sharedDecks);
    }

    // ------------ create (gerar link de compartilhamento) ------------
    public function test_create_should_generate_share_url_with_valid_deck(): void
    {
        Auth::login($this->ownerUser);
        $this->assertTrue(Auth::check());

        $params = ['id' => $this->deck->id];

        $output = $this->post('create', SharedDecksController::class, $params);

        $this->assertStringContainsString('success', $output);
        $this->assertStringContainsString('shareUrl', $output);
        $this->assertStringContainsString('\/shared-decks\/accept\/', $output);

        $jsonStart = strpos($output, '{');
        $jsonEnd = strrpos($output, '}');
        if ($jsonStart !== false && $jsonEnd !== false) {
            $json = substr($output, $jsonStart, $jsonEnd - $jsonStart + 1);
            $data = json_decode($json, true);
            $this->assertIsArray($data);
            $this->assertTrue($data['success']);
            $this->assertNotEmpty($data['shareUrl']);
            $this->assertStringContainsString('/shared-decks/accept/', $data['shareUrl']);
            $this->assertStringContainsString('Link de compartilhamento gerado com sucesso!', $data['message']);
        }
    }

    public function test_create_should_generate_url_even_for_nonexistent_deck(): void
    {
        Auth::login($this->ownerUser);
        $this->assertTrue(Auth::check());

        $params = ['id' => 99999];

        $output = $this->post('create', SharedDecksController::class, $params);

        $this->assertStringContainsString('success', $output);
        $this->assertStringContainsString('shareUrl', $output);

        $jsonStart = strpos($output, '{');
        $jsonEnd = strrpos($output, '}');
        if ($jsonStart !== false && $jsonEnd !== false) {
            $json = substr($output, $jsonStart, $jsonEnd - $jsonStart + 1);
            $data = json_decode($json, true);
            $this->assertIsArray($data);
            $this->assertTrue($data['success']);
        }
    }

    // ------------ destroy (remover compartilhamento) ------------
    public function test_destroy_should_remove_share_successfully(): void
    {
        Auth::login($this->user);
        $this->assertTrue(Auth::check());

        $sharedDeck = new DeckUserShared([
            'deck_id' => $this->deck->id,
            'user_id' => $this->user->id
        ]);
        $sharedDeck->save();
        $sharedDeckId = $sharedDeck->id;

        $beforeDelete = DeckUserShared::findById($sharedDeckId);
        $this->assertNotNull($beforeDelete);
        $this->assertEquals($this->deck->id, $beforeDelete->deck_id);
        $this->assertEquals($this->user->id, $beforeDelete->user_id);

        $params = ['id' => $this->deck->id];
        $output = $this->post('destroy', SharedDecksController::class, $params);
        $messages = FlashMessage::get();

        $this->assertStringContainsString('Location: /shared-decks?page=1', $output);
        $this->assertArrayHasKey('success', $messages);
        $this->assertStringContainsString('Compartilhamento removido com sucesso', $messages['success']);

        $deletedShare = DeckUserShared::findById($sharedDeckId);
        $this->assertNull($deletedShare, 'Compartilhamento deve ter sido removido do banco de dados');

        $remainingShares = DeckUserShared::where([
            'deck_id' => $this->deck->id,
            'user_id' => $this->user->id
        ]);
        $this->assertEmpty($remainingShares);
    }

    public function test_destroy_should_handle_pagination_parameter(): void
    {
        Auth::login($this->user);
        $this->assertTrue(Auth::check());

        $sharedDeck = new DeckUserShared([
            'deck_id' => $this->deck->id,
            'user_id' => $this->user->id
        ]);
        $sharedDeck->save();

        $params = ['id' => $this->deck->id, 'page' => 2];
        $output = $this->post('destroy', SharedDecksController::class, $params);

        $this->assertStringContainsString('Location: /shared-decks?page=2', $output);
    }

    public function test_destroy_should_fail_when_share_not_found(): void
    {
        Auth::login($this->user);
        $this->assertTrue(Auth::check());

        $params = ['id' => $this->deck->id];
        $output = $this->post('destroy', SharedDecksController::class, $params);
        $messages = FlashMessage::get();

        $this->assertStringContainsString('Location: /shared-decks?page=1', $output);
        $this->assertArrayHasKey('danger', $messages);
        $this->assertStringContainsString(
            'Erro ao remover compartilhamento: você não tem acesso a este deck',
            $messages['danger']
        );

        $sharedDecks = DeckUserShared::where([
            'deck_id' => $this->deck->id,
            'user_id' => $this->user->id
        ]);
        $this->assertEmpty($sharedDecks);
    }

    public function test_destroy_should_only_remove_share_for_current_user(): void
    {
        Auth::login($this->user);
        $this->assertTrue(Auth::check());

        $otherUser = new User([
            'name' => 'Other User',
            'email' => 'other@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'user'
        ]);
        $otherUser->save();

        $shareForCurrentUser = new DeckUserShared([
            'deck_id' => $this->deck->id,
            'user_id' => $this->user->id
        ]);
        $shareForCurrentUser->save();
        $shareForCurrentUserId = $shareForCurrentUser->id;

        $shareForOtherUser = new DeckUserShared([
            'deck_id' => $this->deck->id,
            'user_id' => $otherUser->id
        ]);
        $shareForOtherUser->save();
        $shareForOtherUserId = $shareForOtherUser->id;

        $params = ['id' => $this->deck->id];
        $output = $this->post('destroy', SharedDecksController::class, $params);

        $deletedShare = DeckUserShared::findById($shareForCurrentUserId);
        $this->assertNull($deletedShare, 'Compartilhamento do usuário atual deve ter sido removido');

        $remainingShare = DeckUserShared::findById($shareForOtherUserId);
        $this->assertNotNull($remainingShare, 'Compartilhamento do outro usuário deve permanecer');
        $this->assertEquals($otherUser->id, $remainingShare->user_id);
    }
}
