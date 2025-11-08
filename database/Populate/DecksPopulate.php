<?php

namespace Database\Populate;

use App\Models\Deck;
use App\Models\User;

class DecksPopulate
{
    public static function populate()
    {
        echo "Iniciando o populate de decks...\n";

        // Get admin user to associate with decks (só precisa buscar uma vez)
        $user = User::findBy(['email' => 'admin@flashwise.com']);

        if (!$user) {
            echo "✗ Nenhum usuário encontrado. Execute o populate de usuários primeiro.\n";
            return;
        }

        // Array com todos os decks que queremos criar (Agora com 25)
        $decksList = [
            // 1-11 (Os originais)
            [
                'name' => 'English',
                'description' => 'Listening and Vocabulary',
                'path_img' => 'img/decks/english.png',
                'category_id' => null
            ],
            [
                'name' => 'Español',
                'description' => 'Vocabulário Básico e Verbos',
                'path_img' => 'img/decks/spanish.png',
                'category_id' => null
            ],
            [
                'name' => 'Français',
                'description' => 'Conjugações e Frases Úteis',
                'path_img' => 'img/decks/french.png',
                'category_id' => null
            ],
            [
                'name' => '日本語 (Japonês)',
                'description' => 'Hiragana, Katakana e Kanji Básico',
                'path_img' => 'img/decks/japanese.png',
                'category_id' => null
            ],
            [
                'name' => 'Biologia',
                'description' => 'Termos de Biologia Celular',
                'path_img' => 'img/decks/biology.png',
                'category_id' => null
            ],
            [
                'name' => 'Química',
                'description' => 'Tabela Periódica e Elementos',
                'path_img' => 'img/decks/chemistry.png',
                'category_id' => null
            ],
            [
                'name' => 'História Mundial',
                'description' => 'Datas e Eventos Importantes',
                'path_img' => 'img/decks/history.png',
                'category_id' => null
            ],
            [
                'name' => 'Cálculo',
                'description' => 'Derivadas e Integrais',
                'path_img' => 'img/decks/calculus.png',
                'category_id' => null
            ],
            [
                'name' => 'Programação PHP',
                'description' => 'Funções e Conceitos de OOP',
                'path_img' => 'img/decks/php.png',
                'category_id' => null
            ],
            [
                'name' => 'Geografia',
                'description' => 'Capitais e Países',
                'path_img' => 'img/decks/geography.png',
                'category_id' => null
            ],
            [
                'name' => 'História da Arte',
                'description' => 'Movimentos e Artistas',
                'path_img' => 'img/decks/art.png',
                'category_id' => null
            ],

            // 12-25 (Novos)
            [
                'name' => 'Italiano',
                'description' => 'Frases Comuns e Gramática',
                'path_img' => 'img/decks/italian.png',
                'category_id' => null
            ],
            [
                'name' => 'Deutsch (Alemão)',
                'description' => 'Artigos (Der, Die, Das) e Casos',
                'path_img' => 'img/decks/german.png',
                'category_id' => null
            ],
            [
                'name' => '中文 (Mandarim)',
                'description' => 'Tons e Radicais Básicos',
                'path_img' => 'img/decks/mandarin.png',
                'category_id' => null
            ],
            [
                'name' => 'Curiosidades Científicas',
                'description' => 'Fatos interessantes sobre o universo',
                'path_img' => 'img/decks/science-facts.png',
                'category_id' => null
            ],
            [
                'name' => 'Anatomia Humana',
                'description' => 'Ossos, Músculos e Órgãos',
                'path_img' => 'img/decks/anatomy.png',
                'category_id' => null
            ],
            [
                'name' => 'Direito Constitucional',
                'description' => 'Princípios e Artigos Fundamentais',
                'path_img' => 'img/decks/law.png',
                'category_id' => null
            ],
            [
                'name' => 'Economia',
                'description' => 'Microeconomia e Macroeconomia',
                'path_img' => 'img/decks/economics.png',
                'category_id' => null
            ],
            [
                'name' => 'Psicologia',
                'description' => 'Termos e Teóricos Famosos',
                'path_img' => 'img/decks/psychology.png',
                'category_id' => null
            ],
            [
                'name' => 'Filosofia',
                'description' => 'Filósofos e Conceitos',
                'path_img' => 'img/decks/philosophy.png',
                'category_id' => null
            ],
            [
                'name' => 'Teoria Musical',
                'description' => 'Notas, Escalas e Acordes',
                'path_img' => 'img/decks/music.png',
                'category_id' => null
            ],
            [
                'name' => 'Primeiros Socorros',
                'description' => 'Procedimentos de Emergência',
                'path_img' => 'img/decks/first-aid.png',
                'category_id' => null
            ],
            [
                'name' => 'Astronomia',
                'description' => 'Planetas, Estrelas e Constelações',
                'path_img' => 'img/decks/astronomy.png',
                'category_id' => null
            ],
            [
                'name' => 'Culinária e Gastronomia',
                'description' => 'Termos Técnicos e Ingredientes',
                'path_img' => 'img/decks/cooking.png',
                'category_id' => null
            ],
            [
                'name' => 'JavaScript Moderno',
                'description' => 'ES6+, Promises e Async/Await',
                'path_img' => 'img/decks/javascript.png',
                'category_id' => null
            ],
        ];

        $successCount = 0;
        $failCount = 0;

        // Itera sobre a lista e cria cada deck
        foreach ($decksList as $deckData) {
            // Adiciona o user_id em cada deck
            $deckData['user_id'] = $user->id;

            // Boa prática: Verifica se um deck com o mesmo nome para este usuário já existe
            $existingDeck = Deck::findBy(['name' => $deckData['name'], 'user_id' => $user->id]);

            if ($existingDeck) {
                echo "i Deck '{$deckData['name']}' já existe. Pulando.\n";
                continue; // Pula para o próximo item do loop
            }

            // Cria e salva o novo deck
            $deck = new Deck($deckData);
            if ($deck->save()) {
                $successCount++;
            } else {
                echo "✗ Falha ao criar o deck '{$deckData['name']}'.\n";
                $failCount++;
            }
        }

        echo "\nPopulate de decks concluído.\n";
        echo "{$successCount} decks criados com sucesso.\n";
        echo "{$failCount} falhas ao criar decks.\n";
    }
}
