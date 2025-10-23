<?php

namespace Database\Populate;

use App\Models\Deck;
use App\Models\User;

class DecksPopulate
{
    public static function populate()
    {
        echo "Iniciando o populate de decks...\n";

        // Get admin user to associate with deck
        $user = User::findBy(['email' => 'admin@flashwise.com']);

        if (!$user) {
            echo "✗ Nenhum usuário encontrado. Execute o populate de usuários primeiro.\n";
            return;
        }

        $deckData = [
            'name' => 'English',
            'description' => 'Listening and Vocabulary',
            'path_img' => 'teste',
            'category_id' => null,
            'user_id' => $user->id
        ];

        $deck = new Deck($deckData);
        if ($deck->save()) {
            echo "✓ deck de teste criado com sucesso para o usuário {$user->name}!\n";
        } else {
            echo "✗ Falha ao criar deck de teste.\n";
        }
    }
}
