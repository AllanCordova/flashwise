<?php

namespace Database\Populate;

use App\Models\User;

class UserPopulate
{
    public static function populate()
    {
        echo "Iniciando o populate de usuários...\n";
        $amount = 10;
        $plainPassword = 'password123';

        for ($i = 0; $i < $amount; $i++) {
            $email = "allan{$i}@gmail.com";
            $userData = [
                'name' => 'Allan User ' . $i,
                'email' => $email,
                'password' => $plainPassword,
                'password_confirmation' => $plainPassword,
                'avatar_name' => 'default.png'
            ];

            $user = new User($userData);
            if ($user->save()) {
                echo "Usuário {$email} criado com sucesso!\n";
            } else {
                echo "Falha ao criar usuário {$email}.\n";
            }
        }
        echo "Populate de usuários concluído!\n";
    }
}
