<?php
declare(strict_types=1);

namespace App\Security;

/**
 * EmailEncryption - Handles email encryption and hashing using PHP Sodium
 * 
 * This service provides:
 * 1. Deterministic hashing for fast email lookups
 * 2. Authenticated encryption for secure email storage
 * 3. Key derivation from master key for different purposes
 * 
 * Security features:
 * - Uses ChaCha20-Poly1305 authenticated encryption
 * - Constant-time operations to prevent timing attacks
 * - Key derivation for separation of concerns
 * - Version support for future encryption upgrades
 */
final class EmailEncryption
{
    private const HASH_KEY_CONTEXT = 'emlhash1'; // Exactly 8 bytes
    private const ENCRYPT_KEY_CONTEXT = 'emlencr1'; // Exactly 8 bytes
    private const ENCRYPTION_VERSION = 1;
    
    private readonly string $hashKey;
    private readonly string $encryptKey;
    
    public function __construct(string $masterKey)
    {
        // Decode base64 key if needed
        $decodedKey = base64_decode($masterKey);
        if ($decodedKey === false || strlen($decodedKey) < SODIUM_CRYPTO_KDF_KEYBYTES) {
            throw new \InvalidArgumentException(
                'Master key must be a valid base64-encoded key of at least ' . 
                SODIUM_CRYPTO_KDF_KEYBYTES . ' bytes when decoded'
            );
        }
        
        // Ensure key is exactly the right length for KDF
        $kdfKey = substr($decodedKey, 0, SODIUM_CRYPTO_KDF_KEYBYTES);
        
        // Derive separate keys for hashing and encryption
        $this->hashKey = sodium_crypto_kdf_derive_from_key(
            32, // 32 bytes for generic hash key
            1,  // Subkey ID for hash operations
            self::HASH_KEY_CONTEXT,
            $kdfKey
        );
        
        $this->encryptKey = sodium_crypto_kdf_derive_from_key(
            SODIUM_CRYPTO_SECRETBOX_KEYBYTES, // Key size for secretbox
            2,  // Subkey ID for encryption operations
            self::ENCRYPT_KEY_CONTEXT,
            $kdfKey
        );
    }
    
    /**
     * Generate deterministic hash of email for database lookups
     * 
     * Uses sodium_crypto_generichash for fast, secure hashing.
     * The hash is deterministic (same email = same hash) but computationally
     * infeasible to reverse without knowing the original email.
     * 
     * @param string $email The email address to hash
     * @return string Hex-encoded hash (64 characters)
     */
    public function hashEmail(string $email): string
    {
        $normalizedEmail = $this->normalizeEmail($email);
        
        $hash = sodium_crypto_generichash(
            $normalizedEmail,
            $this->hashKey,
            32 // 32 bytes = 256 bits
        );
        
        return sodium_bin2hex($hash);
    }
    
    /**
     * Encrypt email address for secure storage
     * 
     * Uses sodium_crypto_secretbox (ChaCha20-Poly1305) which provides:
     * - Confidentiality: Email content is hidden
     * - Integrity: Tampering is detected
     * - Authenticity: Confirms data hasn't been modified
     * 
     * @param string $email The email address to encrypt
     * @return array{data: string, version: int} Encrypted data and version
     */
    public function encryptEmail(string $email): array
    {
        $normalizedEmail = $this->normalizeEmail($email);
        
        // Generate random nonce (required for secretbox)
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        
        // Encrypt with authenticated encryption
        $ciphertext = sodium_crypto_secretbox($normalizedEmail, $nonce, $this->encryptKey);
        
        // Combine nonce + ciphertext for storage
        $encryptedData = $nonce . $ciphertext;
        
        return [
            'data' => base64_encode($encryptedData),
            'version' => self::ENCRYPTION_VERSION
        ];
    }
    
    /**
     * Decrypt email address from storage
     * 
     * @param string $encryptedData Base64-encoded encrypted email
     * @param int $version Encryption version (for future compatibility)
     * @return string Decrypted email address
     * @throws \RuntimeException If decryption fails
     */
    public function decryptEmail(string $encryptedData, int $version = self::ENCRYPTION_VERSION): string
    {
        if ($version !== self::ENCRYPTION_VERSION) {
            throw new \RuntimeException("Unsupported encryption version: {$version}");
        }
        
        $data = base64_decode($encryptedData);
        if ($data === false) {
            throw new \RuntimeException('Invalid base64 encrypted data');
        }
        
        // Extract nonce and ciphertext
        $nonce = substr($data, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = substr($data, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        
        if (strlen($nonce) !== SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            throw new \RuntimeException('Invalid nonce length');
        }
        
        // Decrypt and verify
        $decrypted = sodium_crypto_secretbox_open($ciphertext, $nonce, $this->encryptKey);
        
        if ($decrypted === false) {
            throw new \RuntimeException('Decryption failed - data may be corrupted or tampered');
        }
        
        return $decrypted;
    }
    
    /**
     * Process email for both hashing and encryption
     * 
     * Convenience method that returns both hash and encrypted data
     * for storing in the database.
     * 
     * @param string $email The email address to process
     * @return array{hash: string, encrypted_data: string, version: int}
     */
    public function processEmail(string $email): array
    {
        $hash = $this->hashEmail($email);
        $encrypted = $this->encryptEmail($email);
        
        return [
            'hash' => $hash,
            'encrypted_data' => $encrypted['data'],
            'version' => $encrypted['version']
        ];
    }
    
    /**
     * Normalize email address for consistent processing
     * 
     * Applies standard normalization rules:
     * - Convert to lowercase
     * - Trim whitespace
     * 
     * @param string $email Raw email address
     * @return string Normalized email address
     */
    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }
    
    /**
     * Securely clear sensitive data from memory
     */
    public function __destruct()
    {
        // Sodium automatically handles secure memory clearing for keys
        // but we can explicitly clear if needed
        sodium_memzero($this->hashKey);
        sodium_memzero($this->encryptKey);
    }
}
