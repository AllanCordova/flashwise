<?php

namespace App\Controllers;

use App\Models\DeckUserShared;
use App\Services\ShareTokenService;
use Core\Http\Controllers\Controller;
use Core\Http\Request;
use Lib\FlashMessage;
use Lib\CustomPaginator;

class SharedDecksController extends Controller
{
    public function index(): void
    {
        $perPage = 10;
        $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $allSharedDecks = $this->current_user->shared_decks;

        $paginator = CustomPaginator::fromArray(
            allData: $allSharedDecks,
            currentPage: $currentPage,
            perPage: $perPage,
            routeName: 'shared.decks.index'
        );

        $this->render('shared_decks/index', [
            'paginator' => $paginator
        ]);
    }

    public function new(Request $request): void
    {
        $token = $request->getParam('token');

        if (ShareTokenService::isTokenExpired($token)) {
            FlashMessage::danger('Link de compartilhamento expirado. Por favor, solicite um novo link.');
            $this->redirectTo('/decks');
            return;
        }

        $sharedDeck = ShareTokenService::decode($token);

        if ($sharedDeck === null) {
            FlashMessage::danger('Link de compartilhamento inválido ou expirado.');
            $this->redirectTo('/decks');
            return;
        }

        $deckUserShared = new DeckUserShared([
            'deck_id' => $sharedDeck,
            'user_id' => $this->current_user->id
        ]);

        $owner = $deckUserShared->deck->user_id;

        if ($owner === $deckUserShared->user_id) {
            FlashMessage::danger('Você não pode compartilhar o deck com você mesmo!');
            $this->redirectTo('/decks');
            return;
        }

        if ($deckUserShared->save()) {
            FlashMessage::success('Deck compartilhado com sucesso!');
        } else {
            FlashMessage::danger('Erro ao compartilhar deck: ' . $deckUserShared->errors('deck_id'));
        }

        $this->redirectTo('/shared-decks');
    }

    public function create(Request $request): void
    {
        $deckId = $request->getParam('id');

        $shareUrl = ShareTokenService::generateShareUrl($deckId);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'shareUrl' => $shareUrl,
            'message' => 'Link de compartilhamento gerado com sucesso!'
        ]);
    }

    public function destroy(Request $request): void
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
