<?php

namespace Database\Populate;

use App\Models\Material;
use App\Models\Deck;

class MaterialsPopulate
{
    public static function populate(): void
    {
        echo "Iniciando o populate de materiais...\n";

        // Caminho do arquivo de teste
        $sampleFilePath = __DIR__ . '/../../tests/files/sample.pdf';

        if (!file_exists($sampleFilePath)) {
            echo "✗ Arquivo de teste não encontrado em: {$sampleFilePath}\n";
            echo "  Por favor, certifique-se de que o arquivo sample.pdf existe em tests/files/\n";
            return;
        }

        // Criar diretório de uploads se não existir
        $uploadDir = __DIR__ . '/../../public/assets/uploads/materials';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
            echo "✓ Diretório de uploads criado.\n";
        }

        // Decks que terão materiais (apenas aqueles que têm cards)
        $decksWithMaterials = [
            'English' => [
                ['title' => 'English Grammar Guide', 'description' => 'Comprehensive guide to English grammar'],
                ['title' => 'Common Phrases Reference', 'description' => 'List of commonly used English phrases'],
            ],
            'Español' => [
                ['title' => 'Spanish Verb Conjugation', 'description' => 'Guide to Spanish verb conjugations'],
            ],
            'Programação PHP' => [
                ['title' => 'PHP OOP Best Practices', 'description' => 'Best practices for object-oriented PHP'],
                ['title' => 'PSR Standards Reference', 'description' => 'PHP Standards Recommendations guide'],
                ['title' => 'Design Patterns in PHP', 'description' => 'Common design patterns implementation'],
            ],
            'Biologia' => [
                ['title' => 'Cell Biology Summary', 'description' => 'Summary of cellular biology concepts'],
                ['title' => 'DNA Structure Diagram', 'description' => 'Visual guide to DNA structure'],
            ],
            'Cálculo' => [
                ['title' => 'Derivatives Formulas', 'description' => 'Common derivative formulas reference'],
                ['title' => 'Integration Techniques', 'description' => 'Guide to integration methods'],
            ],
            'Geografia' => [
                ['title' => 'World Capitals List', 'description' => 'Complete list of world capitals'],
            ],
            'JavaScript Moderno' => [
                ['title' => 'ES6+ Features Guide', 'description' => 'Overview of modern JavaScript features'],
                ['title' => 'Async Programming Tutorial', 'description' => 'Guide to async/await and Promises'],
            ],
        ];

        $successCount = 0;
        $failCount = 0;
        $skippedDecks = 0;

        foreach ($decksWithMaterials as $deckName => $materials) {
            // Buscar o deck
            $deck = Deck::findBy(['name' => $deckName]);

            if (!$deck) {
                echo "⚠ Deck '{$deckName}' não encontrado. Pulando.\n";
                $skippedDecks++;
                continue;
            }

            echo "\nAdicionando materiais ao deck '{$deckName}'...\n";

            foreach ($materials as $materialData) {
                // Verificar se o material já existe
                $existingMaterial = Material::findBy([
                    'deck_id' => $deck->id,
                    'title' => $materialData['title']
                ]);

                if ($existingMaterial) {
                    echo "  i Material '{$materialData['title']}' já existe. Pulando.\n";
                    continue;
                }

                // Copiar arquivo de teste com nome único
                $timestamp = time();
                $randomId = uniqid();
                $fileName = "material_{$deck->id}_{$timestamp}_{$randomId}.pdf";
                $destinationPath = $uploadDir . '/' . $fileName;

                if (!copy($sampleFilePath, $destinationPath)) {
                    echo "  ✗ Falha ao copiar arquivo para '{$materialData['title']}'.\n";
                    $failCount++;
                    continue;
                }

                // Obter tamanho do arquivo
                $fileSize = filesize($destinationPath);

                // Criar o material
                $material = new Material([
                    'deck_id' => $deck->id,
                    'title' => $materialData['title'],
                    'file_path' => 'assets/uploads/materials/' . $fileName,
                    'mime_type' => 'application/pdf',
                    'file_size' => $fileSize,
                ]);

                if ($material->save()) {
                    $successCount++;
                    echo "  ✓ Material '{$materialData['title']}' criado.\n";
                } else {
                    $failCount++;
                    // Remover arquivo copiado se falhar ao salvar no banco
                    unlink($destinationPath);
                    echo "  ✗ Falha ao criar material '{$materialData['title']}'.\n";
                }
            }
        }

        echo "\n" . str_repeat('=', 50) . "\n";
        echo "Populate de materiais concluído.\n";
        echo "{$successCount} materiais criados com sucesso.\n";
        echo "{$failCount} falhas ao criar materiais.\n";
        echo "{$skippedDecks} decks não encontrados.\n";
        echo "Decks com materiais: " . (count($decksWithMaterials) - $skippedDecks) . "\n";
    }
}
