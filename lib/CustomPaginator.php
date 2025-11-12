<?php

namespace Lib;

/**
 * CustomPaginator - Paginator for in-memory data
 *
 * This paginator is used when data needs to be sorted in memory
 * before pagination, such as when sorting by calculated fields
 * that cannot be efficiently sorted in the database.
 */
class CustomPaginator
{
    /**
     * @param array<mixed> $data
     * @param int $page
     * @param int $perPage
     * @param int $totalRegisters
     * @param int $totalPages
     * @param string|null $routeName
     */
    public function __construct(
        private array $data,
        private int $page,
        private int $perPage,
        private int $totalRegisters,
        private int $totalPages,
        private ?string $routeName = null
    ) {
    }

    /**
     * Get the current page items
     *
     * @return array<mixed>
     */
    public function registers(): array
    {
        return $this->data;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function perPage(): int
    {
        return $this->perPage;
    }

    public function totalOfPages(): int
    {
        return $this->totalPages;
    }

    public function totalOfRegisters(): int
    {
        return $this->totalRegisters;
    }

    public function totalOfRegistersOfPage(): int
    {
        return count($this->data);
    }

    public function previousPage(): int
    {
        return $this->page - 1;
    }

    public function nextPage(): int
    {
        return $this->page + 1;
    }

    public function hasPreviousPage(): bool
    {
        return $this->previousPage() >= 1;
    }

    public function hasNextPage(): bool
    {
        return $this->nextPage() <= $this->totalPages;
    }

    public function isPage(int $page): bool
    {
        return $this->page === $page;
    }

    public function entriesInfo(): string
    {
        $offset = ($this->page - 1) * $this->perPage;
        $totalVisualizedBegin = $offset + 1;
        $totalVisualizedEnd = $offset + count($this->data);
        return "Mostrando {$totalVisualizedBegin} - {$totalVisualizedEnd} de {$this->totalRegisters}";
    }

    public function renderPagesNavigation(): void
    {
        $paginator = $this;
        require __DIR__ . '/../app/views/paginator/_pages.phtml';
    }

    public function getRouteName(): string
    {
        return $this->routeName ?? 'default.paginate';
    }

    /**
     * Create a CustomPaginator from an array of data
     *
     * @param array<mixed> $allData All data to paginate
     * @param int $currentPage Current page number
     * @param int $perPage Items per page
     * @param string|null $routeName Route name for pagination links
     * @return self
     */
    public static function fromArray(
        array $allData,
        int $currentPage,
        int $perPage,
        ?string $routeName = null
    ): self {
        $totalRegisters = count($allData);
        $totalPages = (int)ceil($totalRegisters / $perPage);
        $offset = ($currentPage - 1) * $perPage;
        $pageData = array_slice($allData, $offset, $perPage);

        return new self(
            data: $pageData,
            page: $currentPage,
            perPage: $perPage,
            totalRegisters: $totalRegisters,
            totalPages: $totalPages,
            routeName: $routeName
        );
    }
}
