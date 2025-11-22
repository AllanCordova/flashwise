<?php

namespace Tests\Unit\Services;

use App\Services\ShareTokenService;
use Tests\TestCase;

class ShareTokenServiceTest extends TestCase
{
    private string $originalSecretKey;

    public function setUp(): void
    {
        parent::setUp();

        $this->originalSecretKey = $_ENV['FLASHWISE_SECRET_KEY'] ?? 'test-secret-key';

        if (!isset($_ENV['FLASHWISE_SECRET_KEY'])) {
            $_ENV['FLASHWISE_SECRET_KEY'] = 'test-secret-key-for-tests';
        }
    }

    public function tearDown(): void
    {
        $_ENV['FLASHWISE_SECRET_KEY'] = $this->originalSecretKey;
        parent::tearDown();
    }

    // ------------ generate ------------
    public function test_generate_should_create_valid_token(): void
    {
        $deckId = 123;
        $token = ShareTokenService::generate($deckId);

        $this->assertNotEmpty($token);
        $decoded = base64_decode($token, true);
        $this->assertNotFalse($decoded);
    }

    public function test_generate_should_create_different_tokens_for_same_deck(): void
    {
        $deckId = 456;

        $token1 = ShareTokenService::generate($deckId);
        sleep(1);
        $token2 = ShareTokenService::generate($deckId);

        $this->assertNotEquals($token1, $token2);

        $decoded1 = ShareTokenService::decode($token1);
        $decoded2 = ShareTokenService::decode($token2);

        $this->assertEquals($deckId, $decoded1);
        $this->assertEquals($deckId, $decoded2);
    }

    public function test_generate_should_create_different_tokens_for_different_decks(): void
    {
        $deckId1 = 789;
        $deckId2 = 101112;

        $token1 = ShareTokenService::generate($deckId1);
        $token2 = ShareTokenService::generate($deckId2);

        $this->assertNotEquals($token1, $token2);
    }

    // ------------ decode ------------
    public function test_decode_should_return_deck_id_for_valid_token(): void
    {
        $deckId = 123;
        $token = ShareTokenService::generate($deckId);

        $decodedDeckId = ShareTokenService::decode($token);

        $this->assertEquals($deckId, $decodedDeckId);
        $this->assertIsInt($decodedDeckId);
    }

    public function test_decode_should_return_null_for_invalid_token(): void
    {
        $invalidToken = 'invalid-token-123';

        $result = ShareTokenService::decode($invalidToken);

        $this->assertNull($result);
    }

    public function test_decode_should_return_null_for_empty_token(): void
    {
        $result = ShareTokenService::decode('');

        $this->assertNull($result);
    }

    public function test_decode_should_return_null_for_malformed_token(): void
    {
        $malformedToken = base64_encode('invalid:format');

        $result = ShareTokenService::decode($malformedToken);

        $this->assertNull($result);
    }

    public function test_decode_should_return_null_for_tampered_token(): void
    {
        $deckId = 123;
        $token = ShareTokenService::generate($deckId);

        $tamperedToken = $token . 'tampered';

        $result = ShareTokenService::decode($tamperedToken);

        $this->assertNull($result);
    }

    public function test_decode_should_return_null_for_token_with_wrong_hash(): void
    {
        $deckId = 123;
        $timestamp = time();
        $wrongHash = 'wrong-hash-value';

        $data = $deckId . ':' . $timestamp . ':' . $wrongHash;
        $invalidToken = base64_encode($data);

        $result = ShareTokenService::decode($invalidToken);

        $this->assertNull($result);
    }

    public function test_decode_should_return_null_for_token_with_different_secret_key(): void
    {
        $deckId = 123;

        $token = ShareTokenService::generate($deckId);

        $originalKey = $_ENV['FLASHWISE_SECRET_KEY'];
        $_ENV['FLASHWISE_SECRET_KEY'] = 'different-secret-key';

        $result = ShareTokenService::decode($token);

        $_ENV['FLASHWISE_SECRET_KEY'] = $originalKey;

        $this->assertNull($result);
    }

    public function test_decode_should_handle_large_deck_ids(): void
    {
        $largeDeckId = 999999999;
        $token = ShareTokenService::generate($largeDeckId);

        $decodedDeckId = ShareTokenService::decode($token);

        $this->assertEquals($largeDeckId, $decodedDeckId);
    }

    public function test_decode_should_handle_small_deck_ids(): void
    {
        $smallDeckId = 1;
        $token = ShareTokenService::generate($smallDeckId);

        $decodedDeckId = ShareTokenService::decode($token);

        $this->assertEquals($smallDeckId, $decodedDeckId);
    }

    // ------------ generateShareUrl ------------
    public function test_generateShareUrl_should_create_valid_url(): void
    {
        $deckId = 123;

        $_SERVER['HTTP_HOST'] = 'localhost';
        unset($_SERVER['HTTPS']);

        $url = ShareTokenService::generateShareUrl($deckId);

        $this->assertNotEmpty($url);
        $this->assertStringContainsString('http://localhost', $url);
        $this->assertStringContainsString('/shared-decks/accept/', $url);

        $parts = explode('/shared-decks/accept/', $url);
        $this->assertCount(2, $parts);
        $token = $parts[1];

        $decodedDeckId = ShareTokenService::decode($token);
        $this->assertEquals($deckId, $decodedDeckId);
    }

    public function test_generateShareUrl_should_use_https_when_https_is_set(): void
    {
        $deckId = 456;

        $_SERVER['HTTPS'] = 'on';
        $_SERVER['HTTP_HOST'] = 'example.com';

        $url = ShareTokenService::generateShareUrl($deckId);

        $this->assertStringContainsString('https://example.com', $url);
        $this->assertStringContainsString('/shared-decks/accept/', $url);

        unset($_SERVER['HTTPS']);
    }

    public function test_generateShareUrl_should_use_http_when_https_is_not_set(): void
    {
        $deckId = 789;

        unset($_SERVER['HTTPS']);
        $_SERVER['HTTP_HOST'] = 'example.com';

        $url = ShareTokenService::generateShareUrl($deckId);

        $this->assertStringContainsString('http://example.com', $url);
        $this->assertStringNotContainsString('https://', $url);
    }

    public function test_generateShareUrl_should_use_localhost_as_default_host(): void
    {
        $deckId = 101112;

        unset($_SERVER['HTTP_HOST']);
        unset($_SERVER['HTTPS']);

        $url = ShareTokenService::generateShareUrl($deckId);

        $this->assertStringContainsString('http://localhost', $url);
        $this->assertStringContainsString('/shared-decks/accept/', $url);
    }

    public function test_generateShareUrl_should_include_valid_token_in_url(): void
    {
        $deckId = 123;
        $_SERVER['HTTP_HOST'] = 'test.com';
        unset($_SERVER['HTTPS']);

        $url = ShareTokenService::generateShareUrl($deckId);

        preg_match('/\/shared-decks\/accept\/(.+)$/', $url, $matches);
        $this->assertNotEmpty($matches[1]);

        $token = $matches[1];
        $decodedDeckId = ShareTokenService::decode($token);

        $this->assertEquals($deckId, $decodedDeckId);
    }

    // ------------ round-trip tests ------------
    public function test_generate_and_decode_should_work_together(): void
    {
        $originalDeckId = 12345;

        $token = ShareTokenService::generate($originalDeckId);

        $decodedDeckId = ShareTokenService::decode($token);

        $this->assertEquals($originalDeckId, $decodedDeckId);
    }

    public function test_generateShareUrl_and_decode_should_work_together(): void
    {
        $originalDeckId = 67890;
        $_SERVER['HTTP_HOST'] = 'test.com';
        unset($_SERVER['HTTPS']);

        $url = ShareTokenService::generateShareUrl($originalDeckId);

        preg_match('/\/shared-decks\/accept\/(.+)$/', $url, $matches);
        $token = $matches[1];

        $decodedDeckId = ShareTokenService::decode($token);

        $this->assertEquals($originalDeckId, $decodedDeckId);
    }

    // ------------ expiration tests ------------
    public function test_decode_should_return_null_for_expired_token(): void
    {
        $deckId = 123;

        $oldTimestamp = time() - (8 * 24 * 60 * 60);
        $hash = hash_hmac('sha256', $deckId . ':' . $oldTimestamp, $_ENV['FLASHWISE_SECRET_KEY']);
        $data = $deckId . ':' . $oldTimestamp . ':' . $hash;
        $expiredToken = base64_encode($data);

        $result = ShareTokenService::decode($expiredToken);

        $this->assertNull($result, 'Token expirado deve retornar null');
    }

    public function test_decode_should_accept_valid_non_expired_token(): void
    {
        $deckId = 123;

        $recentTimestamp = time() - (1 * 24 * 60 * 60);
        $hash = hash_hmac('sha256', $deckId . ':' . $recentTimestamp, $_ENV['FLASHWISE_SECRET_KEY']);
        $data = $deckId . ':' . $recentTimestamp . ':' . $hash;
        $validToken = base64_encode($data);

        $result = ShareTokenService::decode($validToken);

        $this->assertEquals($deckId, $result, 'Token válido e não expirado deve retornar deckId');
    }

    public function test_decode_should_accept_custom_expiration_time(): void
    {
        $deckId = 123;

        $oldTimestamp = time() - (2 * 24 * 60 * 60);
        $hash = hash_hmac('sha256', $deckId . ':' . $oldTimestamp, $_ENV['FLASHWISE_SECRET_KEY']);
        $data = $deckId . ':' . $oldTimestamp . ':' . $hash;
        $token = base64_encode($data);

        $result = ShareTokenService::decode($token, 3 * 24 * 60 * 60);

        $this->assertEquals($deckId, $result, 'Token dentro do tempo de expiração customizado deve ser aceito');
    }

    public function test_decode_should_reject_token_with_custom_expiration_time(): void
    {
        $deckId = 123;

        $oldTimestamp = time() - (2 * 24 * 60 * 60);
        $hash = hash_hmac('sha256', $deckId . ':' . $oldTimestamp, $_ENV['FLASHWISE_SECRET_KEY']);
        $data = $deckId . ':' . $oldTimestamp . ':' . $hash;
        $token = base64_encode($data);

        $result = ShareTokenService::decode($token, 1 * 24 * 60 * 60);

        $this->assertNull($result, 'Token fora do tempo de expiração customizado deve ser rejeitado');
    }

    public function test_isTokenExpired_should_return_true_for_expired_token(): void
    {
        $deckId = 123;

        $oldTimestamp = time() - (8 * 24 * 60 * 60);
        $hash = hash_hmac('sha256', $deckId . ':' . $oldTimestamp, $_ENV['FLASHWISE_SECRET_KEY']);
        $data = $deckId . ':' . $oldTimestamp . ':' . $hash;
        $expiredToken = base64_encode($data);

        $isExpired = ShareTokenService::isTokenExpired($expiredToken);

        $this->assertTrue($isExpired, 'Token expirado deve retornar true');
    }

    public function test_isTokenExpired_should_return_false_for_valid_token(): void
    {
        $deckId = 123;
        $token = ShareTokenService::generate($deckId);

        $isExpired = ShareTokenService::isTokenExpired($token);

        $this->assertFalse($isExpired, 'Token válido e não expirado deve retornar false');
    }

    public function test_isTokenExpired_should_return_true_for_invalid_token(): void
    {
        $invalidToken = 'invalid-token-123';

        $isExpired = ShareTokenService::isTokenExpired($invalidToken);

        $this->assertTrue($isExpired, 'Token inválido deve ser considerado expirado');
    }

    public function test_isTokenExpired_should_accept_custom_expiration_time(): void
    {
        $deckId = 123;

        $oldTimestamp = time() - (2 * 24 * 60 * 60);
        $hash = hash_hmac('sha256', $deckId . ':' . $oldTimestamp, $_ENV['FLASHWISE_SECRET_KEY']);
        $data = $deckId . ':' . $oldTimestamp . ':' . $hash;
        $token = base64_encode($data);

        $isExpired = ShareTokenService::isTokenExpired($token, 1 * 24 * 60 * 60);
        $this->assertTrue($isExpired, 'Token com 2 dias deve estar expirado com limite de 1 dia');

        $isExpired = ShareTokenService::isTokenExpired($token, 3 * 24 * 60 * 60);
        $this->assertFalse($isExpired, 'Token com 2 dias não deve estar expirado com limite de 3 dias');
    }

    public function test_decode_should_accept_fresh_token(): void
    {
        $deckId = 123;
        $token = ShareTokenService::generate($deckId);

        $result = ShareTokenService::decode($token);

        $this->assertEquals($deckId, $result, 'Token recém-gerado deve ser válido');
    }

    // ------------ security tests ------------
    public function test_token_should_be_secure_against_tampering(): void
    {
        $deckId = 123;
        $token = ShareTokenService::generate($deckId);

        $decoded = base64_decode($token);
        $parts = explode(':', $decoded);

        $parts[0] = '999';
        $tamperedData = implode(':', $parts);
        $tamperedToken = base64_encode($tamperedData);

        $result = ShareTokenService::decode($tamperedToken);

        $this->assertNull($result);
    }

    public function test_token_should_use_hmac_for_security(): void
    {
        $deckId = 123;
        $token = ShareTokenService::generate($deckId);

        // Decodificar token
        $decoded = base64_decode($token);
        $parts = explode(':', $decoded);

        $this->assertCount(3, $parts);
        [$tokenDeckId, $timestamp, $hash] = $parts;

        $this->assertEquals(64, strlen($hash));
        $this->assertTrue(ctype_xdigit($hash));
    }
}
