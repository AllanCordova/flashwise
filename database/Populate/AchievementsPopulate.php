<?php

namespace Database\Populate;

use App\Models\Achievement;
use App\Models\User;
use Core\Constants\Constants;

class AchievementsPopulate
{
    public static function populate(): void
    {
        $imagePath = '/assets/images/achievements/first_deck.png';
        $user = User::findBy(['email' => 'admin@flashwise.com']);

        $achievement = new Achievement([
            'user_id' => $user->id,
            'title' => 'Primeiro Deck',
            'file_path' => $imagePath,
            'file_size' => 0,
            'mime_type' => 'image/png'
        ]);

        if ($achievement->save()) {
            echo "Conquista 'Primeiro Deck' criada para usuário {$user->email}.\n";
        }
    }
}
