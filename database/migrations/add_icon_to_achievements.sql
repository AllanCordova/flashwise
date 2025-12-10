-- Migration: Adiciona suporte a ícones Bootstrap nas conquistas
-- Esta migração torna o sistema de conquistas baseado em ícones em vez de imagens

-- Adiciona novas colunas (ignora erro se já existirem)
ALTER TABLE achievements 
ADD COLUMN icon VARCHAR(100) DEFAULT NULL AFTER title,
ADD COLUMN description TEXT DEFAULT NULL AFTER icon,
ADD COLUMN color_class VARCHAR(50) DEFAULT 'achievement-primary' AFTER description;

-- Torna as colunas de arquivo opcionais (para novas conquistas baseadas em ícones)
ALTER TABLE achievements 
MODIFY COLUMN file_path VARCHAR(255) DEFAULT NULL,
MODIFY COLUMN file_size INT DEFAULT NULL,
MODIFY COLUMN mime_type VARCHAR(100) DEFAULT NULL;

-- Atualiza conquistas existentes com ícones correspondentes
UPDATE achievements SET 
    icon = 'bi-collection-fill', 
    description = 'Criou seu primeiro deck de flashcards',
    color_class = 'achievement-primary'
WHERE title = 'Primeiro Deck';
