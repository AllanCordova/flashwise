<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\User;
use Core\Constants\Constants;

class AchievementService
{
    /**
     * Cria uma conquista para o usuário se ele ainda não a possui
     *
     * @param User $user Usuário que receberá a conquista
     * @param string $title Título da conquista
     * @param string $imagePath Caminho da imagem da conquista (relativo ao public)
     * @return Achievement|null Retorna a conquista criada ou null se já existir
     */
    public static function createIfNotExists(User $user, string $title, string $imagePath): ?Achievement
    {
        // Verifica se o usuário já possui esta conquista
        $existingAchievement = Achievement::findBy([
            'user_id' => $user->id,
            'title' => $title
        ]);

        if ($existingAchievement) {
            return null;
        }

        // Verifica se o arquivo de imagem existe, caso contrário usa imagem padrão
        $absoluteImagePath = Constants::rootPath()->join('public' . $imagePath);
        $defaultImagePath = '/assets/images/defaults/avatar.png';
        $absoluteDefaultImagePath = Constants::rootPath()->join('public' . $defaultImagePath);

        // Converte StringPath para string
        $absoluteImagePathString = (string) $absoluteImagePath;
        $absoluteDefaultImagePathString = (string) $absoluteDefaultImagePath;

        if (!file_exists($absoluteImagePathString)) {
            // Se a imagem específica não existe, usa a imagem padrão
            if (!file_exists($absoluteDefaultImagePathString)) {
                return null;
            }
            $imagePath = $defaultImagePath;
            $absoluteImagePathString = $absoluteDefaultImagePathString;
        }

        // Obtém informações do arquivo
        $fileSize = filesize($absoluteImagePathString);
        $mimeType = mime_content_type($absoluteImagePathString);

        // Valida se conseguiu obter as informações do arquivo
        if ($fileSize === false || $mimeType === false) {
            return null;
        }

        // Cria a conquista
        $achievement = new Achievement([
            'user_id' => $user->id,
            'title' => $title,
            'file_path' => $imagePath,
            'file_size' => $fileSize,
            'mime_type' => $mimeType
        ]);

        if ($achievement->save()) {
            return $achievement;
        }

        // Se falhou ao salvar, retorna null (pode ter erros de validação)
        return null;
    }

    /**
     * Verifica e cria a conquista "Primeiro Deck" se o usuário criou um deck
     *
     * @param User $user Usuário que criou o deck
     * @return Achievement|null Retorna a conquista criada ou null
     */
    public static function checkFirstDeckAchievement(User $user): ?Achievement
    {
        // Cria a conquista "Primeiro Deck"
        $imagePath = '/assets/images/achievements/first_deck.png';
        return self::createIfNotExists($user, 'Primeiro Deck', $imagePath);
    }
}
