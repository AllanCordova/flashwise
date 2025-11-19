<?php

namespace App\Services;

class ShareTokenService
{
    private const SECRET_KEY = 'flashwise_secret_key'; // TODO: Move to .env

    /**
     * Generate a share token for a deck
     */
    public static function generate(int $deckId): string
    {
        $timestamp = time();
        $hash = hash_hmac('sha256', $deckId . ':' . $timestamp, self::SECRET_KEY);

        $data = $deckId . ':' . $timestamp . ':' . $hash;
        return base64_encode($data);
    }

    /**
     * Decode a share token to get deck_id
     */
    public static function decode(string $token): ?int
    {
        try {
            $data = base64_decode($token);
            $parts = explode(':', $data);

            if (count($parts) !== 3) {
                return null;
            }

            [$deckId, $timestamp, $hash] = $parts;

            // Verify hash
            $expectedHash = hash_hmac('sha256', $deckId . ':' . $timestamp, self::SECRET_KEY);

            if (!hash_equals($expectedHash, $hash)) {
                return null;
            }

            // Optional: Check if token is expired (e.g., 7 days)
            // $maxAge = 7 * 24 * 60 * 60; // 7 days
            // if (time() - $timestamp > $maxAge) {
            //     return null;
            // }

            return (int)$deckId;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Generate the full share URL
     */
    public static function generateShareUrl(int $deckId): string
    {
        $token = self::generate($deckId);
        $baseUrl = self::getBaseUrl();

        return $baseUrl . '/shared-decks/accept/' . $token;
    }

    /**
     * Get the base URL of the application
     */
    private static function getBaseUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host;
    }
}
