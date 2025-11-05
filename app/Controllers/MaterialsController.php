<?php

namespace App\Controllers;

use App\Models\Material;
use App\Models\Deck;
use Core\Http\Controllers\Controller;
use Core\Http\Request;
use Lib\FlashMessage;

class MaterialsController extends Controller
{
    /**
     * List all materials for a specific deck
     */
    public function index(Request $request): void
    {
        $deckId = $request->getParam('deck_id');
        $returnPage = $request->getParam('page') ?? 1;
        $returnSort = $request->getParam('sort') ?? 'created_desc';

        if (!$deckId) {
            FlashMessage::danger('Deck não especificado.');
            $this->redirectTo('/decks');
            return;
        }

        /** @var \App\Models\Deck|null $deck */
        $deck = $this->current_user->decks()->findById($deckId);

        if (!$deck) {
            FlashMessage::danger('Deck não encontrado.');
            $this->redirectTo('/decks');
            return;
        }

        $materials = Material::findByDeck($deckId);

        $this->render('materials/index', [
            'deck' => $deck,
            'materials' => $materials,
            'returnPage' => $returnPage,
            'returnSort' => $returnSort
        ]);
    }

    /**
     * Show form to create a new material
     */
    public function new(Request $request): void
    {
        $deckId = $request->getParam('deck_id');
        $returnPage = $request->getParam('page') ?? 1;
        $returnSort = $request->getParam('sort') ?? 'created_desc';

        if (!$deckId) {
            FlashMessage::danger('Deck não especificado.');
            $this->redirectTo('/decks');
            return;
        }

        /** @var \App\Models\Deck|null $deck */
        $deck = $this->current_user->decks()->findById($deckId);

        if (!$deck) {
            FlashMessage::danger('Deck não encontrado.');
            $this->redirectTo('/decks');
            return;
        }

        $material = new Material();
        $material->deck_id = $deckId;

        $this->render('materials/new', [
            'deck' => $deck,
            'material' => $material,
            'returnPage' => $returnPage,
            'returnSort' => $returnSort
        ]);
    }

    /**
     * Store a new material
     */
    public function create(Request $request): void
    {
        $deckId = $request->getParam('deck_id');
        $params = $request->getParam('material');
        $returnPage = $request->getParam('page') ?? 1;
        $returnSort = $request->getParam('sort') ?? 'created_desc';

        if (!$deckId) {
            FlashMessage::danger('Deck não especificado.');
            $this->redirectTo('/decks');
            return;
        }

        /** @var \App\Models\Deck|null $deck */
        $deck = $this->current_user->decks()->findById($deckId);

        if (!$deck) {
            FlashMessage::danger('Deck não encontrado.');
            $this->redirectTo('/decks');
            return;
        }

        // Create material instance
        $material = new Material();
        $material->deck_id = $deckId;
        $material->title = trim($params['title'] ?? '');

        // Validate material data (title, deck_id)
        if (!$material->isValid()) {
            $this->render('materials/new', [
                'deck' => $deck,
                'material' => $material,
                'returnPage' => $returnPage,
                'returnSort' => $returnSort
            ]);
            return;
        }

        // Validate and upload file using the service
        $file = $_FILES['material_file'] ?? [];

        if (!$material->fileUpload()->upload($file)) {
            // File validation failed, render form with errors
            $this->render('materials/new', [
                'deck' => $deck,
                'material' => $material,
                'returnPage' => $returnPage,
                'returnSort' => $returnSort
            ]);
            return;
        }

        // File uploaded successfully and properties set, now save the material
        if ($material->save()) {
            FlashMessage::success('Material adicionado com sucesso!');
            $this->redirectTo("/materials?deck_id={$deckId}&page={$returnPage}&sort=" . urlencode($returnSort));
        } else {
            // If save fails, delete the uploaded file
            $material->fileUpload()->delete();

            FlashMessage::danger('Não foi possível salvar o material.');
            $this->render('materials/new', [
                'deck' => $deck,
                'material' => $material,
                'returnPage' => $returnPage,
                'returnSort' => $returnSort
            ]);
        }
    }

    /**
     * Delete a material
     */
    public function destroy(Request $request): void
    {
        $id = $request->getParam('id');
        $returnPage = $request->getParam('page') ?? 1;
        $returnSort = $request->getParam('sort') ?? 'created_desc';

        $material = Material::findById($id);

        if (!$material) {
            FlashMessage::danger('Material não encontrado.');
            $this->redirectTo('/decks');
            return;
        }

        /** @var \App\Models\Deck $deck */
        $deck = $material->deck;

        // Verify ownership
        if ($deck->user_id !== $this->current_user->id) {
            FlashMessage::danger('Acesso negado.');
            $this->redirectTo('/decks');
            return;
        }

        $deckId = $material->deck_id;

        // Delete physical file
        $material->deleteFile();

        // Delete database record
        if ($material->destroy()) {
            FlashMessage::success('Material removido com sucesso!');
        } else {
            FlashMessage::danger('Erro ao remover o material.');
        }

        $this->redirectTo("/materials?deck_id={$deckId}&page={$returnPage}&sort=" . urlencode($returnSort));
    }
}
