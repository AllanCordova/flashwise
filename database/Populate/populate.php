<?php

require __DIR__ . '/../../config/bootstrap.php';

use Core\Database\Database;
use Database\Populate\UserPopulate;
use Database\Populate\DecksPopulate;
use Database\Populate\CardsPopulate;
use Database\Populate\MaterialsPopulate;
use Database\Populate\AchievementsPopulate;

echo "\n";
echo str_repeat('=', 60) . "\n";
echo "   FLASHWISE - POPULATE DATABASE\n";
echo str_repeat('=', 60) . "\n\n";

// Migrar banco de dados
echo "→ Migrando banco de dados...\n\n";
Database::migrate();

// Populando usuários
echo "\n" . str_repeat('-', 60) . "\n";
UserPopulate::populate();

// Populando decks
echo "\n" . str_repeat('-', 60) . "\n";
DecksPopulate::populate();

// Populando cards
echo "\n" . str_repeat('-', 60) . "\n";
CardsPopulate::populate();

// Populando materiais
echo "\n" . str_repeat('-', 60) . "\n";
MaterialsPopulate::populate();

// Populando conquistas
echo "\n" . str_repeat('-', 60) . "\n";
AchievementsPopulate::populate();

echo "\n" . str_repeat('=', 60) . "\n";
echo "   POPULATE CONCLUÍDO COM SUCESSO!\n";
echo str_repeat('=', 60) . "\n\n";
