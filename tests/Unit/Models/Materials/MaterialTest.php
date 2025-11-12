<?php

namespace Tests\Unit\Models\Materials;

use App\Models\Deck;
use App\Models\Material;
use App\Models\User;
use Tests\TestCase;

class MaterialTest extends TestCase
{
    private ?User $testUser = null;
    private ?Deck $testDeck = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->testUser = new User([
            'name' => 'Test User',
            'email' => 'testmaterial@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'user',
        ]);
        $this->testUser->save();

        $this->testDeck = new Deck([
            'name' => 'Test Deck',
            'description' => 'Test Description',
            'user_id' => $this->testUser->id,
        ]);
        $this->testDeck->save();
    }

    public function test_should_create_material_with_valid_data(): void
    {
        $material = new Material([
            'deck_id' => $this->testDeck->id,
            'title' => 'Test Material',
            'file_path' => '/assets/uploads/materials/1/test.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
        ]);

        $this->assertTrue($material->save());
        $this->assertNull($material->errors('deck_id'));
        $this->assertNull($material->errors('title'));
        $this->assertNull($material->errors('file_path'));

        $this->assertGreaterThan(0, $material->id);
        $this->assertEquals('Test Material', $material->title);
        $this->assertEquals($this->testDeck->id, $material->deck_id);
    }

    public function test_should_not_save_with_invalid_data(): void
    {
        $material = new Material();

        $this->assertFalse($material->save());
        $this->assertTrue($material->hasErrors());
        $this->assertEquals('não pode ser vazio!', $material->errors('deck_id'));
        $this->assertEquals('não pode ser vazio!', $material->errors('title'));
        $this->assertEquals('não pode ser vazio!', $material->errors('file_path'));
    }

    public function test_should_fail_if_deck_id_is_empty(): void
    {
        $material = new Material([
            'deck_id' => null,
            'title' => 'Valid Title',
            'file_path' => '/assets/uploads/materials/1/test.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
        ]);

        $this->assertFalse($material->save());
        $this->assertTrue($material->hasErrors());
        $this->assertEquals('não pode ser vazio!', $material->errors('deck_id'));
    }

    public function test_should_fail_if_title_is_empty(): void
    {
        $material = new Material([
            'deck_id' => $this->testDeck->id,
            'title' => '',
            'file_path' => '/assets/uploads/materials/1/test.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
        ]);

        $this->assertFalse($material->save());
        $this->assertTrue($material->hasErrors());
        $this->assertEquals('não pode ser vazio!', $material->errors('title'));
    }

    public function test_should_fail_if_file_path_is_empty(): void
    {
        $material = new Material([
            'deck_id' => $this->testDeck->id,
            'title' => 'Valid Title',
            'file_path' => '',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
        ]);

        $this->assertFalse($material->save());
        $this->assertTrue($material->hasErrors());
        $this->assertEquals('não pode ser vazio!', $material->errors('file_path'));
    }

    public function test_should_fail_if_file_size_exceeds_limit(): void
    {
        $material = new Material([
            'deck_id' => $this->testDeck->id,
            'title' => 'Valid Title',
            'file_path' => '/assets/uploads/materials/1/test.pdf',
            'file_size' => 20971521,
            'mime_type' => 'application/pdf',
        ]);

        $this->assertFalse($material->save());
        $this->assertTrue($material->hasErrors());
    }

    public function test_should_fail_if_mime_type_is_not_allowed(): void
    {
        $material = new Material([
            'deck_id' => $this->testDeck->id,
            'title' => 'Valid Title',
            'file_path' => '/assets/uploads/materials/1/test.exe',
            'file_size' => 1024,
            'mime_type' => 'application/x-msdownload',
        ]);

        $this->assertFalse($material->save());
        $this->assertTrue($material->hasErrors());
    }

    public function test_material_can_be_found_by_id(): void
    {
        $material = new Material([
            'deck_id' => $this->testDeck->id,
            'title' => 'Findable Material',
            'file_path' => '/assets/uploads/materials/1/test.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
        ]);
        $material->save();

        $foundMaterial = Material::findById($material->id);

        $this->assertNotNull($foundMaterial);
        $this->assertInstanceOf(Material::class, $foundMaterial);
        $this->assertEquals($material->id, $foundMaterial->id);
        $this->assertEquals('Findable Material', $foundMaterial->title);
    }

    public function test_find_by_id_should_return_null_if_not_found(): void
    {
        $foundMaterial = Material::findById(99999);
        $this->assertNull($foundMaterial);
    }

    public function test_material_can_be_updated(): void
    {
        $material = new Material([
            'deck_id' => $this->testDeck->id,
            'title' => 'Original Title',
            'file_path' => '/assets/uploads/materials/1/test.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
        ]);
        $material->save();
        $materialId = $material->id;

        $material->title = 'Updated Title';
        $this->assertTrue($material->save());

        $updatedMaterial = Material::findById($materialId);
        $this->assertEquals('Updated Title', $updatedMaterial->title);
    }

    public function test_material_can_be_deleted(): void
    {
        $material = new Material([
            'deck_id' => $this->testDeck->id,
            'title' => 'Deletable Material',
            'file_path' => '/assets/uploads/materials/1/test.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
        ]);
        $material->save();
        $materialId = $material->id;

        $this->assertTrue($material->destroy());
        $this->assertNull(Material::findById($materialId));
    }

    public function test_material_belongs_to_deck(): void
    {
        $material = new Material([
            'deck_id' => $this->testDeck->id,
            'title' => 'Deck Material',
            'file_path' => '/assets/uploads/materials/1/test.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
        ]);
        $material->save();

        $deck = $material->deck;

        $this->assertInstanceOf(Deck::class, $deck);
        $this->assertEquals($this->testDeck->id, $deck->id);
        $this->assertEquals($this->testDeck->name, $deck->name);
    }

    public function test_get_file_url_returns_file_path(): void
    {
        $filePath = 'assets/uploads/materials/1/test.pdf';
        $material = new Material([
            'deck_id' => $this->testDeck->id,
            'title' => 'Test Material',
            'file_path' => $filePath,
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
        ]);
        $material->save();

        // Deve retornar o file_path com barra inicial (URL absoluta)
        $this->assertEquals('/' . $filePath, $material->getFileUrl());
    }

    public function test_get_file_url_handles_path_with_leading_slash(): void
    {
        $filePath = '/assets/uploads/materials/1/test.pdf';
        $material = new Material([
            'deck_id' => $this->testDeck->id,
            'title' => 'Test Material',
            'file_path' => $filePath,
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
        ]);
        $material->save();

        // Deve retornar o file_path sem duplicar a barra inicial
        $this->assertEquals($filePath, $material->getFileUrl());
    }

    public function test_get_file_url_returns_empty_string_when_no_path(): void
    {
        $material = new Material([
            'deck_id' => $this->testDeck->id,
            'title' => 'Test Material',
            'file_path' => '/assets/uploads/materials/1/test.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
        ]);
        $material->save();
        $material->file_path = '';

        $this->assertEquals('', $material->getFileUrl());
    }

    public function test_get_formatted_size_returns_correct_format(): void
    {
        $material = new Material([
            'deck_id' => $this->testDeck->id,
            'title' => 'Test Material',
            'file_path' => '/assets/uploads/materials/1/test.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
        ]);
        $material->save();

        $formatted = $material->getFormattedSize();
        $this->assertStringContainsString('KB', $formatted);
    }

    public function test_get_formatted_size_returns_zero_bytes_when_no_size(): void
    {
        $material = new Material([
            'deck_id' => $this->testDeck->id,
            'title' => 'Test Material',
            'file_path' => '/assets/uploads/materials/1/test.pdf',
            'file_size' => 0,
            'mime_type' => 'application/pdf',
        ]);
        $material->save();

        $this->assertEquals('0 Bytes', $material->getFormattedSize());
    }

    public function test_material_can_be_found_with_where_clause(): void
    {
        $material = new Material([
            'deck_id' => $this->testDeck->id,
            'title' => 'Searchable Material',
            'file_path' => '/assets/uploads/materials/1/test.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
        ]);
        $material->save();

        $foundMaterials = Material::where(['title' => 'Searchable Material']);

        $this->assertNotEmpty($foundMaterials);
        $this->assertEquals('Searchable Material', $foundMaterials[0]->title);
    }
}
