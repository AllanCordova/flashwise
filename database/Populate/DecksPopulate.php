<?php

namespace Database\Populate;

use App\Models\Deck;

class DecksPopulate
{
    public static function populate()
    {
        echo "Iniciando o populate de decks...\n";

        $deckData = [
            'name' => 'English',
            'description' => 'Listening and Vocabulary',
            'path_img' => 'teste',
            'category_id' => null
        ];

        $deck = new Deck($deckData);
        if ($deck->save()) {
            echo "✓ deck de teste criado com sucesso!\n";
        } else {
            echo "✗ Falha ao criar deck de teste.\n";
        }
    }
}
