<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Controllers\MaterialsController;
use App\Models\Deck;
use App\Models\Material;
use App\Models\User;
use Lib\Authentication\Auth;
use Lib\FlashMessage;

class MaterialsControllerTest extends ControllerTestCase
{
    private User $user;
    private Deck $deck;

    public function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
        $_FILES = [];

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
        $_FILES = [];
        parent::tearDown();
    }

    public function test_show_should_render_view_for_deck_owner(): void
    {
        Auth::login($this->user);

        $params = ['deck_id' => $this->deck->id];
        $output = $this->get('show', MaterialsController::class, $params);

        $this->assertStringNotContainsString('Location:', $output);
        $this->assertEmpty(FlashMessage::get());
    }

    public function test_new_should_render_form_for_deck_owner(): void
    {
        Auth::login($this->user);

        $params = ['deck_id' => $this->deck->id];
        $output = $this->get('new', MaterialsController::class, $params);

        $this->assertStringNotContainsString('Location:', $output);
        $this->assertEmpty(FlashMessage::get());
    }

    public function test_create_should_create_material_with_valid_file(): void
    {
        Auth::login($this->user);

        $tmpFile = sys_get_temp_dir() . '/test_material_' . uniqid() . '.pdf';
        file_put_contents($tmpFile, '%PDF-1.4 test content');

        $_FILES['deck_material'] = [
            'name' => 'test.pdf',
            'type' => 'application/pdf',
            'tmp_name' => $tmpFile,
            'error' => UPLOAD_ERR_OK,
            'size' => 1024
        ];

        $params = [
            'deck_id' => $this->deck->id
        ];

        $output = $this->post('create', MaterialsController::class, $params);
        $messages = FlashMessage::get();

        $this->assertStringContainsString('Location:', $output);
        $this->assertArrayHasKey('success', $messages);
        $this->assertStringContainsString('Material adicionado com sucesso', $messages['success']);

        $materials = Material::where(['deck_id' => $this->deck->id]);
        $this->assertNotEmpty($materials);
        $material = $materials[0];
        $this->assertEquals($this->deck->id, $material->deck_id);
        $this->assertNotEmpty($material->file_path);

        if (file_exists($tmpFile)) {
            unlink($tmpFile);
        }
    }

    public function test_create_should_fail_with_invalid_mime_type(): void
    {
        Auth::login($this->user);

        $tmpFile = sys_get_temp_dir() . '/test_material_' . uniqid() . '.exe';
        file_put_contents($tmpFile, 'invalid content');

        $_FILES['deck_material'] = [
            'name' => 'test.exe',
            'type' => 'application/x-msdownload',
            'tmp_name' => $tmpFile,
            'error' => UPLOAD_ERR_OK,
            'size' => 1024
        ];

        $params = [
            'deck_id' => $this->deck->id
        ];

        $output = $this->post('create', MaterialsController::class, $params);

        $this->assertStringNotContainsString('Location:', $output);
        $this->assertStringContainsString('Não foi possível fazer upload do material', $output);
        $this->assertStringContainsString('é um tipo de arquivo não permitido', $output);

        $materials = Material::where(['deck_id' => $this->deck->id]);
        $this->assertEmpty($materials);

        if (file_exists($tmpFile)) {
            unlink($tmpFile);
        }
    }

    public function test_create_should_fail_with_file_too_large(): void
    {
        Auth::login($this->user);

        $tmpFile = sys_get_temp_dir() . '/test_material_' . uniqid() . '.pdf';
        $largeContent = str_repeat('A', 1024);
        file_put_contents($tmpFile, $largeContent);

        $_FILES['deck_material'] = [
            'name' => 'test.pdf',
            'type' => 'application/pdf',
            'tmp_name' => $tmpFile,
            'error' => UPLOAD_ERR_OK,
            'size' => 20971521
        ];

        $params = [
            'deck_id' => $this->deck->id
        ];

        $output = $this->post('create', MaterialsController::class, $params);

        $this->assertStringNotContainsString('Location:', $output);
        $this->assertStringContainsString('Não foi possível fazer upload do material', $output);
        $this->assertStringContainsString('é muito grande', $output);

        if (file_exists($tmpFile)) {
            unlink($tmpFile);
        }
    }

    public function test_destroy_should_delete_material_for_owner(): void
    {
        Auth::login($this->user);

        $tmpFile = sys_get_temp_dir() . '/test_material_' . uniqid() . '.pdf';
        file_put_contents($tmpFile, '%PDF-1.4 test content');

        $_FILES['deck_material'] = [
            'name' => 'test.pdf',
            'type' => 'application/pdf',
            'tmp_name' => $tmpFile,
            'error' => UPLOAD_ERR_OK,
            'size' => 1024
        ];

        $params = ['deck_id' => $this->deck->id];
        $this->post('create', MaterialsController::class, $params);

        $materials = Material::where(['deck_id' => $this->deck->id]);
        $this->assertNotEmpty($materials);
        $material = $materials[0];
        $materialId = $material->id;

        $params = ['id' => $materialId];
        $output = $this->post('destroy', MaterialsController::class, $params);
        $messages = FlashMessage::get();

        $this->assertStringContainsString('Location:', $output);
        $this->assertArrayHasKey('success', $messages);
        $this->assertStringContainsString('Material excluído com sucesso', $messages['success']);

        $deletedMaterial = Material::findById($materialId);
        $this->assertNull($deletedMaterial);

        if (file_exists($tmpFile)) {
            unlink($tmpFile);
        }
    }

    public function test_destroy_should_redirect_if_material_not_found(): void
    {
        Auth::login($this->user);

        $params = ['id' => 99999];
        $output = $this->post('destroy', MaterialsController::class, $params);
        $messages = FlashMessage::get();

        $this->assertStringContainsString('Location:', $output);
        $this->assertArrayHasKey('danger', $messages);
        $this->assertStringContainsString('Material não encontrado', $messages['danger']);
    }

    public function test_show_should_redirect_if_deck_not_found(): void
    {
        Auth::login($this->user);

        $params = ['deck_id' => 99999];
        $output = $this->get('show', MaterialsController::class, $params);
        $messages = FlashMessage::get();

        $this->assertStringContainsString('Location: /decks', $output);
        $this->assertArrayHasKey('danger', $messages);
        $this->assertStringContainsString('Deck não encontrado', $messages['danger']);
    }

    public function test_new_should_redirect_if_deck_not_found(): void
    {
        Auth::login($this->user);

        $params = ['deck_id' => 99999];
        $output = $this->get('new', MaterialsController::class, $params);
        $messages = FlashMessage::get();

        $this->assertStringContainsString('Location: /decks', $output);
        $this->assertArrayHasKey('danger', $messages);
        $this->assertStringContainsString('Deck não encontrado', $messages['danger']);
    }
}
