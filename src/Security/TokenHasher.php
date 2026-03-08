<?php

declare(strict_types=1);

namespace LaravelLocalLlm\Security;

final class TokenHasher
{
    private const ALGORITHM = 'sha256';
    private const SALT_LENGTH = 32;

    public function hash(string $token): string
    {
        $salt = $this->generateSalt();
        $hash = hash_hmac(self::ALGORITHM, $token, $salt);
        
        return $salt . ':' . $hash;
    }

    public function verify(string $token, string $hashedToken): bool
    {
        $parts = explode(':', $hashedToken, 2);
        
        if (count($parts) !== 2) {
            return false;
        }

        [$salt, $originalHash] = $parts;

        $computedHash = hash_hmac(self::ALGORITHM, $token, $salt);

        return hash_equals($originalHash, $computedHash);
    }

    public function needsRehash(string $hashedToken): bool
    {
        return true;
    }

    private function generateSalt(): string
    {
        return bin2hex(random_bytes(self::SALT_LENGTH));
    }

    public static function make(string $token): string
    {
        $hasher = new self();
        return $hasher->hash($token);
    }

    public static function verifyToken(string $token, string $hashedToken): bool
    {
        $hasher = new self();
        return $hasher->verify($token, $hashedToken);
    }
}
