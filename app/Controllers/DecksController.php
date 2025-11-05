<?php

namespace App\Controllers;

use App\Models\Deck;
use Core\Http\Controllers\Controller;
use Lib\FlashMessage;
use Core\Http\Request;

class DecksController extends Controller
{
    public function index(): void
    {
        $perPage = 10;

        $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $userId = $this->currentUser()->id;
        $sortBy = $_GET['sort'] ?? 'created_desc';

        // Map sort options to database columns
        $sortMapping = [
            'name_asc' => ['column' => 'name', 'direction' => 'ASC'],
            'name_desc' => ['column' => 'name', 'direction' => 'DESC'],
            'created_asc' => ['column' => 'created_at', 'direction' => 'ASC'],
            'created_desc' => ['column' => 'created_at', 'direction' => 'DESC'],
        ];

        // Check if sort is by review priority (requires in-memory sorting)
        if ($sortBy === 'review_priority') {
            // Get all decks without pagination for sorting
            $allDecks = Deck::where(['user_id' => $userId]);

            // Sort by review priority (new cards + due cards descending)
            usort($allDecks, function ($a, $b) {
                $priorityA = $a->countNewCards() + $a->countDueCards();
                $priorityB = $b->countNewCards() + $b->countDueCards();
                return $priorityB - $priorityA; // Descending order
            });

            // Manual pagination
            $totalDecks = count($allDecks);
            $totalPages = (int)ceil($totalDecks / $perPage);
            $offset = ($currentPage - 1) * $perPage;
            $decks = array_slice($allDecks, $offset, $perPage);

            // Create a custom paginator-like object
            $paginator = new class ($decks, $currentPage, $perPage, $totalDecks, $totalPages) {
                /**
                 * @param array<\App\Models\Deck> $decks
                 */
                public function __construct(
                    private array $decks,
                    private int $page,
                    private int $perPage,
                    private int $totalRegisters,
                    private int $totalPages
                ) {
                }

                /**
                 * @return array<\App\Models\Deck>
                 */
                public function registers(): array
                {
                    return $this->decks;
                }

                public function getPage(): int
                {
                    return $this->page;
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
                    return count($this->decks);
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
                    $totalVisualizedEnd = $offset + count($this->decks);
                    return "Mostrando {$totalVisualizedBegin} - {$totalVisualizedEnd} de {$this->totalRegisters}";
                }

                public function renderPagesNavigation(): void
                {
                    $paginator = $this;
                    require __DIR__ . '/../../app/views/paginator/_pages.phtml';
                }

                public function getRouteName(): string
                {
                    return 'decks.index';
                }
            };
        } else {
            // Default sorting with database
            $orderBy = 'created_at';
            $orderDirection = 'DESC';

            if (isset($sortMapping[$sortBy])) {
                $orderBy = $sortMapping[$sortBy]['column'];
                $orderDirection = $sortMapping[$sortBy]['direction'];
            }

            $paginator = Deck::paginate(
                page: $currentPage,
                per_page: $perPage,
                route: 'decks.index',
                conditions: ['user_id' => $userId],
                orderBy: $orderBy,
                orderDirection: $orderDirection
            );
        }

        $this->render('decks/index', [
            'paginator' => $paginator,
            'currentSort' => $sortBy
        ]);
    }

    public function show(Request $request): void
    {
        $id = $request->getParam('id');
        $returnPage = $request->getParam('page') ?? 1;
        $returnSort = $request->getParam('sort') ?? 'created_desc';

        /** @var \App\Models\Deck|null $deck */
        $deck = $this->current_user->decks()->findById($id);

        if (!$deck) {
            FlashMessage::danger('Deck não encontrado');
            $this->redirectTo('/decks');
            return;
        }

        $newCards = $deck->countNewCards();
        $dueCards = $deck->countDueCards();
        $totalCards = $deck->countTotalCards();

        $this->render('decks/show', [
            'deck' => $deck,
            'newCards' => $newCards,
            'dueCards' => $dueCards,
            'totalCards' => $totalCards,
            'returnPage' => $returnPage,
            'returnSort' => $returnSort
        ]);
    }

    public function edit(Request $request): void
    {
        $id = $request->getParam('id');
        $returnPage = $request->getParam('page') ?? 1;
        $returnSort = $request->getParam('sort') ?? 'created_desc';

        /** @var \App\Models\Deck|null $deck */
        $deck = $this->current_user->decks()->findById($id);

        if (!$deck) {
            FlashMessage::danger('Deck não encontrado');
            $this->redirectTo('/decks');
            return;
        }

        $cards = $deck->cards;

        $this->render('decks/edit', [
            'deck' => $deck,
            'cards' => $cards,
            'returnPage' => $returnPage,
            'returnSort' => $returnSort
        ]);
    }

    public function update(Request $request): void
    {
        $id = $request->getParam('id');
        $params = $request->getParam('deck');
        $returnPage = $request->getParam('page') ?? 1;
        $returnSort = $request->getParam('sort') ?? 'created_desc';

        /** @var \App\Models\Deck|null $deck */
        $deck = $this->current_user->decks()->findById($id);

        if (!$deck) {
            FlashMessage::danger('Deck não encontrado');
            $this->redirectTo('/decks');
            return;
        }

        $deck->name = $params['name'] ?? '';
        $deck->description = $params['description'] ?? '';

        if ($deck->save()) {
            FlashMessage::success('Deck atualizado com sucesso');
            $this->redirectTo('/decks?page=' . $returnPage . '&sort=' . urlencode($returnSort));
        } else {
            FlashMessage::danger('Não foi possível atualizar o deck. Verifique os dados!');
            /** @var \App\Models\Card[] $cards */
            $cards = $deck->cards;
            $this->render('decks/edit', compact('deck', 'cards', 'returnPage', 'returnSort'));
        }
    }

    public function new(Request $request): void
    {
        $returnPage = $request->getParam('page') ?? 1;
        $returnSort = $request->getParam('sort') ?? 'created_desc';

        $deck = $this->current_user->decks()->new();

        $this->render('decks/new', compact('deck', 'returnPage', 'returnSort'));
    }

    public function create(Request $request): void
    {
        $params = $request->getParam('deck');
        $returnPage = $request->getParam('page') ?? 1;
        $returnSort = $request->getParam('sort') ?? 'created_desc';

        $deck = $this->current_user->decks()->new([
            'name' => $params['name'] ?? '',
            'description' => $params['description'] ?? '',
        ]);

        if ($deck->save()) {
            FlashMessage::success('Deck criado com sucesso');
            $this->redirectTo('/decks?page=' . $returnPage . '&sort=' . urlencode($returnSort));
        } else {
            FlashMessage::danger('Não foi possível criar seu deck. Verifique os dados!');
            $this->render('decks/new', compact('deck', 'returnPage', 'returnSort'));
        }
    }

    public function destroy(Request $request): void
    {
        $id = $request->getParam('id');
        $returnPage = $request->getParam('page') ?? 1;
        $returnSort = $request->getParam('sort') ?? 'created_desc';

        /** @var \App\Models\Deck|null $deck */
        $deck = $this->current_user->decks()->findById($id);

        if (!$deck) {
            FlashMessage::danger('Deck não encontrado');
            $this->redirectTo('/decks');
            return;
        }

        if ($deck->destroy()) {
            FlashMessage::success('Deck excluído com sucesso!');
        } else {
            FlashMessage::danger('Ocorreu um erro ao excluir o deck.');
        }

        $this->redirectTo('/decks?page=' . $returnPage . '&sort=' . urlencode($returnSort));
    }
}
