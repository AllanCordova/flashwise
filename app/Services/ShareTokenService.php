<?php

namespace App\Services;

class ShareTokenService
{
    // Tempo de expiração padrão: 7 dias (604800 segundos)
    private const DEFAULT_EXPIRATION_SECONDS = 604800;

    private static function getSecretKey(): string
    {
        return $_ENV['FLASHWISE_SECRET_KEY'] ?? 'default-secret-key-for-development';
    }

    public static function decode(string $token, int $expirationSeconds = self::DEFAULT_EXPIRATION_SECONDS): ?int
    {
        $data = base64_decode($token, true);
        if ($data === false) {
            return null;
        }

        $parts = explode(':', $data);

        if (count($parts) !== 3) {
            return null;
        }

        [$deckId, $timestamp, $hash] = $parts;

        $currentTime = time();
        $tokenAge = $currentTime - (int)$timestamp;

        if ($tokenAge > $expirationSeconds) {
            return null;
        }

        $expectedHash = hash_hmac('sha256', $deckId . ':' . $timestamp, self::getSecretKey());

        if (!hash_equals($expectedHash, $hash)) {
            return null;
        }

        return (int)$deckId;
    }

    public static function isTokenExpired(string $token, int $expirationSeconds = self::DEFAULT_EXPIRATION_SECONDS): bool
    {
        $data = base64_decode($token, true);
        if ($data === false) {
            return true;
        }

        $parts = explode(':', $data);
        if (count($parts) !== 3) {
            return true;
        }

        [, $timestamp, ] = $parts;
        $currentTime = time();
        $tokenAge = $currentTime - (int)$timestamp;

        return $tokenAge > $expirationSeconds;
    }

    public static function generateShareUrl(int $deckId): string
    {
        $token = self::generate($deckId);
        $baseUrl = self::getBaseUrl();

        return $baseUrl . '/shared-decks/accept/' . $token;
    }

    public static function generate(int $deckId): string
    {
        $timestamp = time();
        $hash = hash_hmac('sha256', $deckId . ':' . $timestamp, self::getSecretKey());

        $data = $deckId . ':' . $timestamp . ':' . $hash;
        return base64_encode($data);
    }

    private static function getBaseUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host;
    }
}
