<?php

namespace Tests\Unit\Models;

use App\Models\Deck;
use Tests\TestCase;

class DeckTest extends TestCase
{
    public function testDeckModelCanBeCreated(): void
    {
        $deck = new Deck([
            'name' => 'Test Deck',
            'description' => 'Test Description',
            'path_img' => 'test.png',
            'category_id' => null,
        ]);

        $result = $deck->save();

        $this->assertTrue($result);
        $this->assertGreaterThan(0, $deck->id);
        $this->assertEquals('Test Deck', $deck->name);
        $this->assertEquals('Test Description', $deck->description);
    }

    public function testDeckValidationRequiresName(): void
    {
        $deck = new Deck([
            'name' => '',
            'description' => 'Valid Description',
            'path_img' => 'test.png',
            'category_id' => null,
        ]);

        $result = $deck->save();

        $this->assertFalse($result);
        $this->assertTrue($deck->hasErrors());
        $this->assertEquals('não pode ser vazio!', $deck->errors('name'));
    }

    public function testDeckValidationRequiresDescription(): void
    {
        $deck = new Deck([
            'name' => 'Valid Name',
            'description' => '',
            'path_img' => 'test.png',
            'category_id' => null,
        ]);

        $result = $deck->save();

        $this->assertFalse($result);
        $this->assertTrue($deck->hasErrors());
        $this->assertEquals('não pode ser vazio!', $deck->errors('description'));
    }

    public function testDeckNameMustBeUnique(): void
    {
        $deck1 = new Deck([
            'name' => 'Unique Deck',
            'description' => 'First deck',
            'path_img' => 'test1.png',
            'category_id' => null,
        ]);
        $deck1->save();

        $deck2 = new Deck([
            'name' => 'Unique Deck',
            'description' => 'Second deck',
            'path_img' => 'test2.png',
            'category_id' => null,
        ]);
        $result = $deck2->save();

        $this->assertFalse($result);
        $this->assertTrue($deck2->hasErrors());
        $this->assertNotNull($deck2->errors('name'));
    }

    public function testDeckCanBeFound(): void
    {
        $deck = new Deck([
            'name' => 'Findable Deck',
            'description' => 'Can be found',
            'path_img' => 'find.png',
            'category_id' => null,
        ]);
        $deck->save();

        $foundDeck = Deck::findById($deck->id);

        $this->assertNotNull($foundDeck);
        $this->assertEquals('Findable Deck', $foundDeck->name);
        $this->assertEquals('Can be found', $foundDeck->description);
    }

    public function testDeckCanBeDeleted(): void
    {
        $deck = new Deck([
            'name' => 'Deletable Deck',
            'description' => 'Will be deleted',
            'path_img' => 'delete.png',
            'category_id' => null,
        ]);
        $deck->save();
        $deckId = $deck->id;

        $result = $deck->destroy();

        $this->assertTrue($result);
        $this->assertNull(Deck::findById($deckId));
    }

    public function testAllDecksCanBeRetrieved(): void
    {
        $deck1 = new Deck([
            'name' => 'Deck 1',
            'description' => 'First deck',
            'path_img' => 'deck1.png',
            'category_id' => null,
        ]);
        $deck1->save();

        $deck2 = new Deck([
            'name' => 'Deck 2',
            'description' => 'Second deck',
            'path_img' => 'deck2.png',
            'category_id' => null,
        ]);
        $deck2->save();

        $allDecks = Deck::all();

        $this->assertGreaterThanOrEqual(2, count($allDecks));
    }

    public function testDeckCanHaveNullCategory(): void
    {
        $deck = new Deck([
            'name' => 'No Category Deck',
            'description' => 'Deck without category',
            'path_img' => 'nocat.png',
            'category_id' => null,
        ]);

        $result = $deck->save();

        $this->assertTrue($result);
        /** @phpstan-ignore-next-line */
        $this->assertNull($deck->category_id);
    }

    public function testDeckDescriptionCanBeLong(): void
    {
        $longDescription = str_repeat('Este é um deck com uma descrição muito longa. ', 50);

        $deck = new Deck([
            'name' => 'Long Description Deck',
            'description' => $longDescription,
            'path_img' => 'long.png',
            'category_id' => null,
        ]);

        $result = $deck->save();

        $this->assertTrue($result);
        $this->assertEquals($longDescription, $deck->description);
    }
}
