<?php

namespace App\Controllers;

use App\Models\Material;
use App\Services\AchievementService;
use App\Services\MaterialService;
use Core\Http\Controllers\Controller;
use Core\Http\Request;
use Lib\FlashMessage;

class MaterialsController extends Controller
{
    public function show(Request $request): void
    {
        $deck_id = (int) $request->getParam('deck_id');
        /** @var \App\Models\Deck|null $deck */
        $deck = $this->current_user->decks()->findById($deck_id);

        if (!$deck) {
            FlashMessage::danger('Deck não encontrado');
            $this->redirectTo('/decks');
            return;
        }

        $materials = $deck->materials;
        $returnPage = $request->getParam('page', 1);
        $returnSort = $request->getParam('sort', 'created_desc');

        $this->render('materials/index', compact(
            'deck',
            'materials',
            'returnPage',
            'returnSort'
        ));
    }

    public function new(Request $request): void
    {
        $deck_id = (int) $request->getParam('deck_id');
        /** @var \App\Models\Deck|null $deck */
        $deck = $this->current_user->decks()->findById($deck_id);

        if (!$deck) {
            FlashMessage::danger('Deck não encontrado');
            $this->redirectTo('/decks');
            return;
        }

        $material = new Material();

        $returnPage = $request->getParam('page', 1);
        $returnSort = $request->getParam('sort', 'created_desc');

        $this->render('materials/new', compact(
            'material',
            'deck',
            'returnPage',
            'returnSort'
        ));
    }

    public function create(Request $request): void
    {
        $deck_id = (int) $request->getParam('deck_id');
        $returnPage = $request->getParam('page', 1);
        $returnSort = $request->getParam('sort', 'created_desc');

        /** @var \App\Models\Deck|null $deck */
        $deck = $this->current_user->decks()->findById($deck_id);

        if (!$deck) {
            FlashMessage::danger('Deck não encontrado');
            $this->redirectTo('/decks');
            return;
        }

        $file = $_FILES['deck_material'] ?? [];

        $material = new Material(['deck_id' => $deck_id]);

        $service = new MaterialService($file, $material);
        $material = $service->upload();

        if ($material->hasErrors()) {
            FlashMessage::danger('Não foi possível fazer upload do material. Verifique os erros abaixo.');
            $this->render('materials/new', compact('material', 'deck', 'returnPage', 'returnSort'));
        } else {
            // Verifica e concede conquistas de materiais
            AchievementService::checkMaterialAchievements($this->current_user);
            
            FlashMessage::success('Material adicionado com sucesso!');
            $this->redirectTo(route('materials.index', [
                'deck_id' => $deck_id,
                'page' => $returnPage,
                'sort' => $returnSort
            ]));
        }
    }

    public function destroy(Request $request): void
    {
        $id = (int) $request->getParam('id');
        $returnPage = $request->getParam('page', 1);
        $returnSort = $request->getParam('sort', 'created_desc');

        $material = Material::findById($id);

        if (!$material) {
            FlashMessage::danger('Material não encontrado');
            $this->redirectTo('/decks');
            return;
        }

        $deckId = $material->deck_id;

        $service = new MaterialService([], $material);
        if ($service->destroy()) {
            FlashMessage::success('Material excluído com sucesso!');
        } else {
            FlashMessage::danger('Ocorreu um erro ao excluir o material.');
        }

        $this->redirectTo(route('materials.index', [
            'deck_id' => $deckId,
            'page' => $returnPage,
            'sort' => $returnSort
        ]));
    }
}
