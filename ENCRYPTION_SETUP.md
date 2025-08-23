# Email Encryption Setup

This application uses hybrid email encryption to secure user email addresses using PHP Sodium.

## Features

- **Deterministic Hashing**: Fast email lookups using `sodium_crypto_generichash`
- **Authenticated Encryption**: Secure email storage using `sodium_crypto_secretbox` (ChaCha20-Poly1305)
- **Key Derivation**: Separate keys for hashing and encryption derived from master key
- **Version Support**: Future-proof encryption version tracking

## Environment Configuration

Add this to your `.env` file:

```bash
# Email Encryption Key (REQUIRED)
# Generate a secure 32+ byte key for production
EMAIL_ENCRYPTION_KEY=your_32_byte_or_longer_encryption_key_here
```

### Generating a Secure Key

For production, generate a cryptographically secure key:

```bash
# Using OpenSSL (recommended)
openssl rand -base64 32

# Using PHP
php -r "echo base64_encode(random_bytes(32)) . PHP_EOL;"

# Using Sodium directly
php -r "echo sodium_bin2base64(random_bytes(32), SODIUM_BASE64_VARIANT_ORIGINAL) . PHP_EOL;"
```

## Database Schema

The migration creates these columns in the `users` table for email handling:

- `email_hash` (VARCHAR 64): Deterministic hash for fast lookups
- `email_encrypted` (TEXT): Encrypted email for communication
- `encryption_version` (TINYINT): Version tracking for future upgrades

**Note**: No plain-text email column exists in the database - all email data is either hashed or encrypted.

## Security Considerations

### Key Management
- Store the master key securely (environment variables, key management service)
- Never commit keys to version control
- Consider key rotation policies for production

### Performance
- Email hashing is optimized for fast lookups
- Decryption only occurs when the actual email address is needed

### Design Philosophy
- No plain-text email storage in the database
- Email addresses are always encrypted at rest
- Hash-based lookups provide fast query performance
- Clean separation between business logic (decrypted) and infrastructure (encrypted)

## Usage Examples

### Finding a User by Email
```php
// Service layer
$user = $userService->findByEmail('user@example.com');

// Repository layer (direct)
$user = $userRepository->findByEmail('user@example.com');
```

### Creating a User
```php
// Automatically encrypts email during creation
$user = $userRepository->createUser($email, $username, $emailVerified);
```

### Accessing Email Data
```php
// Get decrypted email (ready to use)
$email = $user->getEmail();

// Get email hash (for debugging/verification)
$hash = $user->getEmailHash();

// Get encrypted data (for storage/backup)
$encrypted = $user->getEmailEncrypted();
```

## Security Benefits

1. **Data Breach Protection**: Email addresses are encrypted at rest
2. **Privacy Compliance**: Helps meet GDPR/privacy requirements  
3. **Searchability**: Fast email lookups without exposing data
4. **Integrity**: Authenticated encryption prevents tampering
5. **Future-Proof**: Version support for encryption upgrades

## Important Notes

- Requires PHP Sodium extension (included in PHP 7.2+)
- Ensure your master key is at least 32 bytes
- Test key generation and validation before production deployment
- Consider backup strategies for encrypted data
