<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\Card;
use App\Models\CardUserProgress;
use App\Models\Deck;
use App\Models\DeckUserShared;
use App\Models\Material;
use App\Models\User;

class AchievementService
{
    /**
     * Definição de todas as conquistas disponíveis
     * Cada conquista tem: title, icon, description, color_class
     */
    public const ACHIEVEMENTS = [
        // Conquistas de Decks
        'first_deck' => [
            'title' => 'Primeiro Deck',
            'icon' => 'bi-collection-fill',
            'description' => 'Criou seu primeiro deck de flashcards',
            'color_class' => 'achievement-primary'
        ],
        'deck_master_5' => [
            'title' => 'Organizador',
            'icon' => 'bi-folder2-open',
            'description' => 'Criou 5 decks diferentes',
            'color_class' => 'achievement-info'
        ],
        'deck_master_10' => [
            'title' => 'Colecionador',
            'icon' => 'bi-archive-fill',
            'description' => 'Criou 10 decks diferentes',
            'color_class' => 'achievement-success'
        ],
        'deck_master_25' => [
            'title' => 'Mestre dos Decks',
            'icon' => 'bi-stack',
            'description' => 'Criou 25 decks diferentes',
            'color_class' => 'achievement-warning'
        ],

        // Conquistas de Cards
        'first_card' => [
            'title' => 'Primeiro Card',
            'icon' => 'bi-card-text',
            'description' => 'Criou seu primeiro flashcard',
            'color_class' => 'achievement-primary'
        ],
        'card_creator_10' => [
            'title' => 'Aprendiz',
            'icon' => 'bi-layers',
            'description' => 'Criou 10 flashcards',
            'color_class' => 'achievement-info'
        ],
        'card_creator_50' => [
            'title' => 'Estudante Dedicado',
            'icon' => 'bi-layers-half',
            'description' => 'Criou 50 flashcards',
            'color_class' => 'achievement-success'
        ],
        'card_creator_100' => [
            'title' => 'Criador Prolífico',
            'icon' => 'bi-layers-fill',
            'description' => 'Criou 100 flashcards',
            'color_class' => 'achievement-warning'
        ],
        'card_creator_500' => [
            'title' => 'Enciclopédia Ambulante',
            'icon' => 'bi-book-fill',
            'description' => 'Criou 500 flashcards',
            'color_class' => 'achievement-danger'
        ],

        // Conquistas de Estudo
        'first_study' => [
            'title' => 'Primeira Sessão',
            'icon' => 'bi-play-circle-fill',
            'description' => 'Completou sua primeira sessão de estudo',
            'color_class' => 'achievement-primary'
        ],
        'study_streak_3' => [
            'title' => 'Consistente',
            'icon' => 'bi-fire',
            'description' => 'Estudou por 3 dias seguidos',
            'color_class' => 'achievement-warning'
        ],
        'study_streak_7' => [
            'title' => 'Semana Perfeita',
            'icon' => 'bi-calendar-check-fill',
            'description' => 'Estudou por 7 dias seguidos',
            'color_class' => 'achievement-success'
        ],
        'study_streak_30' => [
            'title' => 'Maratonista',
            'icon' => 'bi-trophy-fill',
            'description' => 'Estudou por 30 dias seguidos',
            'color_class' => 'achievement-danger'
        ],
        'study_sessions_10' => [
            'title' => 'Estudante Regular',
            'icon' => 'bi-mortarboard',
            'description' => 'Completou 10 sessões de estudo',
            'color_class' => 'achievement-info'
        ],
        'study_sessions_50' => [
            'title' => 'Estudante Avançado',
            'icon' => 'bi-mortarboard-fill',
            'description' => 'Completou 50 sessões de estudo',
            'color_class' => 'achievement-success'
        ],
        'study_sessions_100' => [
            'title' => 'Mestre do Estudo',
            'icon' => 'bi-award-fill',
            'description' => 'Completou 100 sessões de estudo',
            'color_class' => 'achievement-warning'
        ],

        // Conquistas de Compartilhamento
        'first_share' => [
            'title' => 'Compartilhador',
            'icon' => 'bi-share-fill',
            'description' => 'Compartilhou seu primeiro deck com alguém',
            'color_class' => 'achievement-primary'
        ],
        'share_master_5' => [
            'title' => 'Colaborador',
            'icon' => 'bi-people-fill',
            'description' => 'Compartilhou decks com 5 pessoas diferentes',
            'color_class' => 'achievement-info'
        ],
        'first_received' => [
            'title' => 'Bem Conectado',
            'icon' => 'bi-inbox-fill',
            'description' => 'Recebeu acesso ao primeiro deck compartilhado',
            'color_class' => 'achievement-success'
        ],

        // Conquistas de Materiais
        'first_material' => [
            'title' => 'Primeiro Material',
            'icon' => 'bi-file-earmark-plus-fill',
            'description' => 'Adicionou seu primeiro material de estudo',
            'color_class' => 'achievement-primary'
        ],
        'material_collector_10' => [
            'title' => 'Pesquisador',
            'icon' => 'bi-file-earmark-richtext-fill',
            'description' => 'Adicionou 10 materiais de estudo',
            'color_class' => 'achievement-info'
        ],

        // Conquistas de Perfil
        'profile_complete' => [
            'title' => 'Perfil Completo',
            'icon' => 'bi-person-check-fill',
            'description' => 'Completou todas as informações do perfil',
            'color_class' => 'achievement-success'
        ],
        'avatar_uploaded' => [
            'title' => 'Com Estilo',
            'icon' => 'bi-camera-fill',
            'description' => 'Adicionou uma foto de perfil',
            'color_class' => 'achievement-info'
        ],

        // Conquistas Especiais
        'early_bird' => [
            'title' => 'Madrugador',
            'icon' => 'bi-sunrise-fill',
            'description' => 'Estudou antes das 6h da manhã',
            'color_class' => 'achievement-warning'
        ],
        'night_owl' => [
            'title' => 'Coruja Noturna',
            'icon' => 'bi-moon-stars-fill',
            'description' => 'Estudou depois da meia-noite',
            'color_class' => 'achievement-purple'
        ],
        'perfect_session' => [
            'title' => 'Sessão Perfeita',
            'icon' => 'bi-star-fill',
            'description' => 'Acertou todos os cards em uma sessão',
            'color_class' => 'achievement-warning'
        ],
        'speed_learner' => [
            'title' => 'Mente Rápida',
            'icon' => 'bi-lightning-charge-fill',
            'description' => 'Completou uma sessão em menos de 1 minuto',
            'color_class' => 'achievement-danger'
        ]
    ];

    /**
     * Cria uma conquista para o usuário se ele ainda não a possui
     *
     * @param User $user Usuário que receberá a conquista
     * @param string $achievementKey Chave da conquista no array ACHIEVEMENTS
     * @return Achievement|null Retorna a conquista criada ou null se já existir
     */
    public static function createIfNotExists(User $user, string $achievementKey): ?Achievement
    {
        // Verifica se a conquista existe nas definições
        if (!isset(self::ACHIEVEMENTS[$achievementKey])) {
            return null;
        }

        $achievementData = self::ACHIEVEMENTS[$achievementKey];

        // Verifica se o usuário já possui esta conquista
        $existingAchievement = Achievement::findBy([
            'user_id' => $user->id,
            'title' => $achievementData['title']
        ]);

        if ($existingAchievement) {
            return null;
        }

        // Cria a conquista com ícone
        $achievement = new Achievement([
            'user_id' => $user->id,
            'title' => $achievementData['title'],
            'icon' => $achievementData['icon'],
            'description' => $achievementData['description'],
            'color_class' => $achievementData['color_class']
        ]);

        if ($achievement->save()) {
            return $achievement;
        }

        return null;
    }

    /**
     * Verifica e concede conquistas relacionadas a decks
     */
    public static function checkDeckAchievements(User $user): array
    {
        $achievements = [];

        // Conta decks do usuário
        $deckCount = count(Deck::where(['user_id' => $user->id]));

        if ($deckCount >= 1) {
            $achievement = self::createIfNotExists($user, 'first_deck');
            if ($achievement) {
                $achievements[] = $achievement;
            }
        }

        if ($deckCount >= 5) {
            $achievement = self::createIfNotExists($user, 'deck_master_5');
            if ($achievement) {
                $achievements[] = $achievement;
            }
        }

        if ($deckCount >= 10) {
            $achievement = self::createIfNotExists($user, 'deck_master_10');
            if ($achievement) {
                $achievements[] = $achievement;
            }
        }

        if ($deckCount >= 25) {
            $achievement = self::createIfNotExists($user, 'deck_master_25');
            if ($achievement) {
                $achievements[] = $achievement;
            }
        }

        return $achievements;
    }

    /**
     * Verifica e concede conquistas relacionadas a cards
     */
    public static function checkCardAchievements(User $user): array
    {
        $achievements = [];

        // Conta cards do usuário (via decks)
        $userDecks = Deck::where(['user_id' => $user->id]);
        $cardCount = 0;
        
        foreach ($userDecks as $deck) {
            $cardCount += count(Card::where(['deck_id' => $deck->id]));
        }

        if ($cardCount >= 1) {
            $achievement = self::createIfNotExists($user, 'first_card');
            if ($achievement) {
                $achievements[] = $achievement;
            }
        }

        if ($cardCount >= 10) {
            $achievement = self::createIfNotExists($user, 'card_creator_10');
            if ($achievement) {
                $achievements[] = $achievement;
            }
        }

        if ($cardCount >= 50) {
            $achievement = self::createIfNotExists($user, 'card_creator_50');
            if ($achievement) {
                $achievements[] = $achievement;
            }
        }

        if ($cardCount >= 100) {
            $achievement = self::createIfNotExists($user, 'card_creator_100');
            if ($achievement) {
                $achievements[] = $achievement;
            }
        }

        if ($cardCount >= 500) {
            $achievement = self::createIfNotExists($user, 'card_creator_500');
            if ($achievement) {
                $achievements[] = $achievement;
            }
        }

        return $achievements;
    }

    /**
     * Verifica e concede conquistas relacionadas a sessões de estudo
     */
    public static function checkStudyAchievements(User $user): array
    {
        $achievements = [];

        // Conta cards revisados pelo usuário (baseado em card_user_progress)
        $studyCount = count(CardUserProgress::where(['user_id' => $user->id]));

        if ($studyCount >= 1) {
            $achievement = self::createIfNotExists($user, 'first_study');
            if ($achievement) {
                $achievements[] = $achievement;
            }
        }

        if ($studyCount >= 50) {
            $achievement = self::createIfNotExists($user, 'study_sessions_10');
            if ($achievement) {
                $achievements[] = $achievement;
            }
        }

        if ($studyCount >= 200) {
            $achievement = self::createIfNotExists($user, 'study_sessions_50');
            if ($achievement) {
                $achievements[] = $achievement;
            }
        }

        if ($studyCount >= 500) {
            $achievement = self::createIfNotExists($user, 'study_sessions_100');
            if ($achievement) {
                $achievements[] = $achievement;
            }
        }

        return $achievements;
    }

    /**
     * Verifica e concede conquistas relacionadas a compartilhamento
     */
    public static function checkShareAchievements(User $user): array
    {
        $achievements = [];

        // Conta compartilhamentos feitos pelo usuário
        $userDecks = Deck::where(['user_id' => $user->id]);
        $shareCount = 0;
        
        foreach ($userDecks as $deck) {
            $shareCount += count(DeckUserShared::where(['deck_id' => $deck->id]));
        }

        if ($shareCount >= 1) {
            $achievement = self::createIfNotExists($user, 'first_share');
            if ($achievement) {
                $achievements[] = $achievement;
            }
        }

        if ($shareCount >= 5) {
            $achievement = self::createIfNotExists($user, 'share_master_5');
            if ($achievement) {
                $achievements[] = $achievement;
            }
        }

        // Verifica se recebeu decks compartilhados
        $receivedCount = count(DeckUserShared::where(['user_id' => $user->id]));
        
        if ($receivedCount >= 1) {
            $achievement = self::createIfNotExists($user, 'first_received');
            if ($achievement) {
                $achievements[] = $achievement;
            }
        }

        return $achievements;
    }

    /**
     * Verifica conquistas de horário especial (madrugador/coruja)
     */
    public static function checkTimeAchievements(User $user): array
    {
        $achievements = [];
        $currentHour = (int) date('H');

        // Madrugador: antes das 6h
        if ($currentHour >= 0 && $currentHour < 6) {
            $achievement = self::createIfNotExists($user, 'early_bird');
            if ($achievement) {
                $achievements[] = $achievement;
            }
        }

        // Coruja: depois da meia-noite (0h-3h)
        if ($currentHour >= 0 && $currentHour < 3) {
            $achievement = self::createIfNotExists($user, 'night_owl');
            if ($achievement) {
                $achievements[] = $achievement;
            }
        }

        return $achievements;
    }

    /**
     * Verifica conquistas de sessão perfeita
     */
    public static function checkPerfectSession(User $user, int $correctCount, int $totalCount): ?Achievement
    {
        if ($totalCount > 0 && $correctCount === $totalCount) {
            return self::createIfNotExists($user, 'perfect_session');
        }
        return null;
    }

    /**
     * Verifica conquista de velocidade
     */
    public static function checkSpeedLearner(User $user, int $sessionDurationSeconds): ?Achievement
    {
        if ($sessionDurationSeconds < 60) {
            return self::createIfNotExists($user, 'speed_learner');
        }
        return null;
    }

    /**
     * Verifica conquistas de perfil
     */
    public static function checkProfileAchievements(User $user): array
    {
        $achievements = [];

        // Verifica se tem avatar
        if (!empty($user->avatar_path)) {
            $achievement = self::createIfNotExists($user, 'avatar_uploaded');
            if ($achievement) {
                $achievements[] = $achievement;
            }
        }

        // Verifica se perfil está completo
        if (!empty($user->name) && !empty($user->email) && !empty($user->avatar_path)) {
            $achievement = self::createIfNotExists($user, 'profile_complete');
            if ($achievement) {
                $achievements[] = $achievement;
            }
        }

        return $achievements;
    }

    /**
     * Verifica e concede conquistas relacionadas a materiais
     */
    public static function checkMaterialAchievements(User $user): array
    {
        $achievements = [];

        // Conta materiais em decks do usuário
        $userDecks = Deck::where(['user_id' => $user->id]);
        $materialCount = 0;
        
        foreach ($userDecks as $deck) {
            $materialCount += count(Material::where(['deck_id' => $deck->id]));
        }

        if ($materialCount >= 1) {
            $achievement = self::createIfNotExists($user, 'first_material');
            if ($achievement) {
                $achievements[] = $achievement;
            }
        }

        if ($materialCount >= 10) {
            $achievement = self::createIfNotExists($user, 'material_collector_10');
            if ($achievement) {
                $achievements[] = $achievement;
            }
        }

        return $achievements;
    }

    /**
     * Verifica todas as conquistas para um usuário
     */
    public static function checkAllAchievements(User $user): array
    {
        $allAchievements = [];

        $allAchievements = array_merge($allAchievements, self::checkDeckAchievements($user));
        $allAchievements = array_merge($allAchievements, self::checkCardAchievements($user));
        $allAchievements = array_merge($allAchievements, self::checkStudyAchievements($user));
        $allAchievements = array_merge($allAchievements, self::checkShareAchievements($user));
        $allAchievements = array_merge($allAchievements, self::checkMaterialAchievements($user));
        $allAchievements = array_merge($allAchievements, self::checkProfileAchievements($user));
        $allAchievements = array_merge($allAchievements, self::checkTimeAchievements($user));

        return $allAchievements;
    }

    /**
     * Método legado para manter compatibilidade
     * @deprecated Use createIfNotExists com achievement key
     */
    public static function checkFirstDeckAchievement(User $user): ?Achievement
    {
        return self::createIfNotExists($user, 'first_deck');
    }
}
