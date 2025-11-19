<?php

namespace App\Controllers;

use App\Models\Deck;
use App\Models\DeckUserShared;
use App\Services\ShareTokenService;
use Core\Http\Controllers\Controller;
use Core\Http\Request;
use Lib\FlashMessage;
use Lib\CustomPaginator;

class SharedDecksController extends Controller
{
    /**
     * Display a list of decks shared with the current user
     */
    public function index(): void
    {
        $perPage = 10;
        $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;

        // Get all shared decks for the current user
        $allSharedDecks = $this->current_user->shared_decks;

        // Use CustomPaginator for in-memory pagination
        $paginator = CustomPaginator::fromArray(
            allData: $allSharedDecks,
            currentPage: $currentPage,
            perPage: $perPage,
            routeName: 'shared.decks.index'
        );

        $this->render('shared_decks/index', [
            'paginator' => $paginator,
        ]);
    }

    /**
     * Generate a share link for a deck (token-based)
     */
    public function share(Request $request): void
    {
        $deckId = $request->getParam('id');

        $deck = $this->current_user->decks()->findById($deckId);

        if (!$deck) {
            FlashMessage::danger('Deck não encontrado');
            $this->redirectTo('/decks');
            return;
        }

        $shareUrl = ShareTokenService::generateShareUrl($deckId);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'shareUrl' => $shareUrl,
            'message' => 'Link de compartilhamento gerado com sucesso!'
        ]);
        exit;
    }

    /**
     * Accept a shared deck via token
     */
    public function accept(Request $request): void
    {
        $token = $request->getParam('token');
        $deckId = ShareTokenService::decode($token);

        if (!$deckId) {
            FlashMessage::danger('Link de compartilhamento inválido ou expirado');
            $this->redirectTo('/decks');
            return;
        }

        $deck = Deck::findById($deckId);

        if (!$deck) {
            FlashMessage::danger('Deck não encontrado');
            $this->redirectTo('/decks');
            return;
        }

        if ($deck->isSharedWithUser($this->current_user)) {
            FlashMessage::info('Este deck já está compartilhado com você');
            $this->redirectTo('/shared-decks');
            return;
        }

        if ($deck->user_id === $this->current_user->id) {
            FlashMessage::info('Você não pode compartilhar um deck com você mesmo');
            $this->redirectTo('/decks');
            return;
        }

        $deckUserShared = new DeckUserShared([
            'deck_id' => $deckId,
            'user_id' => $this->current_user->id
        ]);

        if ($deckUserShared->save()) {
            FlashMessage::success('Deck compartilhado com sucesso! Você pode acessá-lo em "Decks Compartilhados"');
        } else {
            FlashMessage::danger('Erro ao compartilhar deck: ' . $deckUserShared->errors('deck_id'));
        }

        $this->redirectTo('/shared-decks');
    }

    /**
     * Remove a shared deck (unshare)
     */
    public function remove(Request $request): void
    {
        $deckId = $request->getParam('id');
        $returnPage = $request->getParam('page') ?? 1;

        $deckUserShared = DeckUserShared::findBy([
            'deck_id' => $deckId,
            'user_id' => $this->current_user->id
        ]);

        if ($deckUserShared === null) {
            FlashMessage::danger('Erro ao remover compartilhamento: você não tem acesso a este deck');
        } else {
            $deckUserShared->destroy();
            FlashMessage::success('Compartilhamento removido com sucesso');
        }

        $this->redirectTo('/shared-decks?page=' . $returnPage);
    }
}
