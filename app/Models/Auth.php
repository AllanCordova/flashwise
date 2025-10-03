<?php

namespace App\Models;

// A classe Auth usa o modelo User para encontrar e validar o usuário.
use App\Models\User;

class Auth
{
    /**
     * Tenta autenticar um usuário com base no e-mail e senha.
     *
     * @param string $email O e-mail fornecido pelo usuário.
     * @param string $password A senha fornecida pelo usuário.
     * @return bool Retorna true se o login for bem-sucedido, false caso contrário.
     */
    public static function attemptLogin(string $email, string $password): bool
    {
        // 1. Encontra o usuário pelo e-mail usando o método que já existe em User.
        $user = User::findByEmail($email);

        // 2. Se nenhum usuário for encontrado com esse e-mail, o login falha.
        if (!$user) {
            return false;
        }

        // 3. Usa o método authenticate() do próprio objeto User para verificar a senha.
        //    Isso é ótimo, pois a lógica de password_verify fica encapsulada no User model.
        if (!$user->authenticate($password)) {
            return false;
        }

        // 4. SUCESSO! A senha está correta. Agora, iniciamos a sessão.
        //    É essencial registrar que o usuário está logado.
        self::startUserSession($user);

        return true;
    }

    /**
     * Inicia a sessão do usuário, armazenando seu ID.
     * Esta é a parte que "lembra" que o usuário está logado.
     */
    protected static function startUserSession(User $user): void
    {
        // Garante que a sessão PHP está iniciada.
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Armazena o ID do usuário na sessão.
        // Em outras páginas, você pode verificar se $_SESSION['user_id'] existe
        // para saber se o usuário está logado.
        $_SESSION['user_id'] = $user->id;
    }

    /**
     * Realiza o logout do usuário, destruindo sua sessão.
     */
    public static function logout(): void
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        session_destroy();
    }
}
