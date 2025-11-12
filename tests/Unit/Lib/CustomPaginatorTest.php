<?php

namespace Tests\Unit\Lib;

use Lib\CustomPaginator;
use Tests\TestCase;

class CustomPaginatorTest extends TestCase
{
    public function testFromArrayCreatesValidPaginator(): void
    {
        $data = range(1, 25); // Array with 25 items
        $paginator = CustomPaginator::fromArray($data, 1, 10, 'test.route');

        $this->assertEquals(1, $paginator->getPage());
        $this->assertEquals(10, $paginator->perPage());
        $this->assertEquals(25, $paginator->totalOfRegisters());
        $this->assertEquals(3, $paginator->totalOfPages());
        $this->assertEquals(10, $paginator->totalOfRegistersOfPage());
        $this->assertEquals('test.route', $paginator->getRouteName());
    }

    public function testFromArrayReturnsCorrectPageData(): void
    {
        $data = range(1, 25);
        $paginator = CustomPaginator::fromArray($data, 2, 10);

        $registers = $paginator->registers();
        $this->assertEquals(10, count($registers));
        $this->assertEquals(11, $registers[0]); // Second page starts at 11
        $this->assertEquals(20, $registers[9]); // Second page ends at 20
    }

    public function testFromArrayLastPageHasCorrectItemCount(): void
    {
        $data = range(1, 25);
        $paginator = CustomPaginator::fromArray($data, 3, 10);

        $this->assertEquals(5, $paginator->totalOfRegistersOfPage());
        $this->assertEquals(25, $paginator->totalOfRegisters());
        $registers = $paginator->registers();
        $this->assertEquals(21, $registers[0]);
        $this->assertEquals(25, $registers[4]);
    }

    public function testNavigationMethods(): void
    {
        $data = range(1, 25);
        $paginator = CustomPaginator::fromArray($data, 2, 10);

        // Previous page
        $this->assertTrue($paginator->hasPreviousPage());
        $this->assertEquals(1, $paginator->previousPage());

        // Next page
        $this->assertTrue($paginator->hasNextPage());
        $this->assertEquals(3, $paginator->nextPage());

        // Is page
        $this->assertTrue($paginator->isPage(2));
        $this->assertFalse($paginator->isPage(1));
    }

    public function testFirstPageNavigation(): void
    {
        $data = range(1, 25);
        $paginator = CustomPaginator::fromArray($data, 1, 10);

        $this->assertFalse($paginator->hasPreviousPage());
        $this->assertTrue($paginator->hasNextPage());
    }

    public function testLastPageNavigation(): void
    {
        $data = range(1, 25);
        $paginator = CustomPaginator::fromArray($data, 3, 10);

        $this->assertTrue($paginator->hasPreviousPage());
        $this->assertFalse($paginator->hasNextPage());
    }

    public function testEntriesInfoFirstPage(): void
    {
        $data = range(1, 25);
        $paginator = CustomPaginator::fromArray($data, 1, 10);

        $info = $paginator->entriesInfo();
        $this->assertEquals('Mostrando 1 - 10 de 25', $info);
    }

    public function testEntriesInfoMiddlePage(): void
    {
        $data = range(1, 25);
        $paginator = CustomPaginator::fromArray($data, 2, 10);

        $info = $paginator->entriesInfo();
        $this->assertEquals('Mostrando 11 - 20 de 25', $info);
    }

    public function testEntriesInfoLastPage(): void
    {
        $data = range(1, 25);
        $paginator = CustomPaginator::fromArray($data, 3, 10);

        $info = $paginator->entriesInfo();
        $this->assertEquals('Mostrando 21 - 25 de 25', $info);
    }

    public function testEmptyDataset(): void
    {
        $data = [];
        $paginator = CustomPaginator::fromArray($data, 1, 10);

        $this->assertEquals(0, $paginator->totalOfRegisters());
        $this->assertEquals(0, $paginator->totalOfPages());
        $this->assertEquals(0, $paginator->totalOfRegistersOfPage());
        $this->assertEmpty($paginator->registers());
        $this->assertFalse($paginator->hasPreviousPage());
        $this->assertFalse($paginator->hasNextPage());
    }

    public function testSinglePage(): void
    {
        $data = range(1, 5);
        $paginator = CustomPaginator::fromArray($data, 1, 10);

        $this->assertEquals(1, $paginator->totalOfPages());
        $this->assertEquals(5, $paginator->totalOfRegistersOfPage());
        $this->assertFalse($paginator->hasPreviousPage());
        $this->assertFalse($paginator->hasNextPage());
    }

    public function testDefaultRouteName(): void
    {
        $data = range(1, 10);
        $paginator = CustomPaginator::fromArray($data, 1, 10);

        $this->assertEquals('default.paginate', $paginator->getRouteName());
    }

    public function testWithComplexObjects(): void
    {
        $objects = [
            (object)['id' => 1, 'name' => 'Item 1'],
            (object)['id' => 2, 'name' => 'Item 2'],
            (object)['id' => 3, 'name' => 'Item 3'],
            (object)['id' => 4, 'name' => 'Item 4'],
            (object)['id' => 5, 'name' => 'Item 5'],
        ];

        $paginator = CustomPaginator::fromArray($objects, 1, 3);

        $registers = $paginator->registers();
        $this->assertEquals(3, count($registers));
        $this->assertEquals(1, $registers[0]->id);
        $this->assertEquals('Item 1', $registers[0]->name);
    }
}
