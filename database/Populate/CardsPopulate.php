<?php

namespace Database\Populate;

use App\Models\Card;
use App\Models\Deck;

class CardsPopulate
{
    public static function populate(): void
    {
        echo "Iniciando o populate de cards...\n";

        // Buscar alguns decks para adicionar cards
        // Vamos adicionar cards apenas para alguns decks específicos
        $decksWithCards = [
            'English' => [
                ['front' => 'Hello', 'back' => 'Olá'],
                ['front' => 'Goodbye', 'back' => 'Adeus'],
                ['front' => 'Thank you', 'back' => 'Obrigado'],
                ['front' => 'Please', 'back' => 'Por favor'],
                ['front' => 'Good morning', 'back' => 'Bom dia'],
            ],
            'Español' => [
                ['front' => 'Hola', 'back' => 'Olá'],
                ['front' => 'Adiós', 'back' => 'Adeus'],
                ['front' => 'Gracias', 'back' => 'Obrigado'],
                ['front' => 'Por favor', 'back' => 'Por favor'],
            ],
            'Programação PHP' => [
                ['front' => 'O que significa OOP?', 'back' => 'Object-Oriented Programming (Programação Orientada a Objetos)'],
                ['front' => 'O que é uma classe?', 'back' => 'Um modelo/template para criar objetos'],
                ['front' => 'O que é herança?', 'back' => 'Mecanismo que permite criar novas classes baseadas em classes existentes'],
                ['front' => 'O que é polimorfismo?', 'back' => 'Capacidade de objetos de diferentes classes responderem ao mesmo método'],
                ['front' => 'O que é encapsulamento?', 'back' => 'Ocultar detalhes internos e expor apenas o necessário'],
            ],
            'Biologia' => [
                ['front' => 'O que é mitocôndria?', 'back' => 'Organela responsável pela produção de energia (ATP)'],
                ['front' => 'O que é DNA?', 'back' => 'Ácido desoxirribonucleico - material genético'],
                ['front' => 'O que é fotossíntese?', 'back' => 'Processo de conversão de luz solar em energia química'],
            ],
            'Cálculo' => [
                ['front' => 'Derivada de x²', 'back' => '2x'],
                ['front' => 'Derivada de sen(x)', 'back' => 'cos(x)'],
                ['front' => 'Derivada de cos(x)', 'back' => '-sen(x)'],
                ['front' => 'Derivada de eˣ', 'back' => 'eˣ'],
            ],
            'Geografia' => [
                ['front' => 'Capital do Brasil', 'back' => 'Brasília'],
                ['front' => 'Capital da França', 'back' => 'Paris'],
                ['front' => 'Capital do Japão', 'back' => 'Tóquio'],
                ['front' => 'Capital dos EUA', 'back' => 'Washington D.C.'],
            ],
            'JavaScript Moderno' => [
                ['front' => 'O que é uma Promise?', 'back' => 'Objeto que representa a eventual conclusão ou falha de uma operação assíncrona'],
                ['front' => 'O que faz async/await?', 'back' => 'Sintaxe para trabalhar com Promises de forma síncrona'],
                ['front' => 'O que é arrow function?', 'back' => 'Sintaxe curta para escrever funções (=>) introduzida no ES6'],
            ],
        ];

        $successCount = 0;
        $failCount = 0;
        $skippedDecks = 0;

        foreach ($decksWithCards as $deckName => $cards) {
            // Buscar o deck
            $deck = Deck::findBy(['name' => $deckName]);

            if (!$deck) {
                echo "⚠ Deck '{$deckName}' não encontrado. Pulando.\n";
                $skippedDecks++;
                continue;
            }

            echo "\nAdicionando cards ao deck '{$deckName}'...\n";

            foreach ($cards as $cardData) {
                // Verificar se o card já existe
                $existingCard = Card::findBy([
                    'deck_id' => $deck->id,
                    'front' => $cardData['front']
                ]);

                if ($existingCard) {
                    echo "  i Card '{$cardData['front']}' já existe. Pulando.\n";
                    continue;
                }

                // Criar o card
                $card = new Card([
                    'deck_id' => $deck->id,
                    'front' => $cardData['front'],
                    'back' => $cardData['back'],
                    'card_type' => 'new',
                    'ease_factor' => 2.50,
                    'review_interval' => 0,
                    'repetitions' => 0,
                    'next_review' => null,
                    'last_reviewed' => null,
                ]);

                if ($card->save()) {
                    $successCount++;
                    echo "  ✓ Card '{$cardData['front']}' criado.\n";
                } else {
                    $failCount++;
                    echo "  ✗ Falha ao criar card '{$cardData['front']}'.\n";
                }
            }
        }

        echo "\n" . str_repeat('=', 50) . "\n";
        echo "Populate de cards concluído.\n";
        echo "{$successCount} cards criados com sucesso.\n";
        echo "{$failCount} falhas ao criar cards.\n";
        echo "{$skippedDecks} decks não encontrados.\n";
        echo "Decks com cards: " . (count($decksWithCards) - $skippedDecks) . "\n";
        echo "Decks sem cards: " . (25 - count($decksWithCards) + $skippedDecks) . "\n";
    }
}
