<?php

namespace Database\Populate;

use App\Models\User;

class UserPopulate
{
    public static function populate()
    {
        echo "Iniciando o populate de usuários...\n";

        // Criar usuário administrador
        $adminData = [
            'name' => 'Administrador',
            'email' => 'admin@flashwise.com',
            'password' => 'admin123',
            'password_confirmation' => 'admin123',
            'role' => 'admin',
            'avatar_name' => 'default.png'
        ];

        $admin = new User($adminData);
        if ($admin->save()) {
            echo "✓ Usuário administrador criado com sucesso!\n";
            echo "  Email: admin@flashwise.com | Senha: admin123\n";
        } else {
            echo "✗ Falha ao criar usuário administrador.\n";
        }

        // Criar usuários normais
        $amount = 5;
        $plainPassword = 'password123';

        for ($i = 1; $i <= $amount; $i++) {
            $email = "user{$i}@flashwise.com";
            $userData = [
                'name' => 'Usuário ' . $i,
                'email' => $email,
                'password' => $plainPassword,
                'password_confirmation' => $plainPassword,
                'role' => 'user',
                'avatar_name' => 'default.png'
            ];

            $user = new User($userData);
            if ($user->save()) {
                echo "✓ Usuário {$email} criado com sucesso!\n";
            } else {
                echo "✗ Falha ao criar usuário {$email}.\n";
            }
        }

        echo "\n========================================\n";
        echo "Populate de usuários concluído!\n";
        echo "========================================\n";
        echo "ADMIN: admin@flashwise.com | admin123\n";
        echo "USERS: user1@flashwise.com até user5@flashwise.com | password123\n";
        echo "========================================\n\n";
    }
}
