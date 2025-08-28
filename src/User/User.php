<?php
declare(strict_types=1);

namespace App\User;

use App\Support\Helper;

/**
 * User Entity - Represents a user in the system
 * Focused on business data only, auth concerns handled separately
 */
final class User implements \JsonSerializable
{
    private ?\DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt;
    private ?\DateTimeImmutable $deletedAt;

    public function __construct(
        private ?int $id,
        private string $email, // Decrypted email for business logic use
        private string $emailHash, // Hash for database lookups
        private string $emailEncrypted, // Encrypted email for storage
        private int $encryptionVersion, // Encryption version for upgrades
        private int $emailVerified, // 0 or 1
        private string $username,
        string|\DateTimeImmutable|null $createdAt = null,
        string|\DateTimeImmutable|null $updatedAt = null,
        string|\DateTimeImmutable|null $deletedAt = null
    ) {
        $this->createdAt = Helper::convertToDateTimeImmutable($createdAt);
        $this->updatedAt = Helper::convertToDateTimeImmutable($updatedAt);
        $this->deletedAt = Helper::convertToDateTimeImmutable($deletedAt);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
    
    public function getEmailHash(): string
    {
        return $this->emailHash;
    }
    
    public function getEmailEncrypted(): string
    {
        return $this->emailEncrypted;
    }
    
    public function getEncryptionVersion(): int
    {
        return $this->encryptionVersion;
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerified === 1;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    /**
     * Specify data which should be serialized to JSON
     *
     * Exposes safe public fields only, with dates formatted as ISO-8601 strings.
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'email_verified' => $this->isEmailVerified(),
            'username' => $this->username,
            'created_at' => $this->createdAt?->format(DATE_ATOM),
            'updated_at' => $this->updatedAt?->format(DATE_ATOM),
            'deleted_at' => $this->deletedAt?->format(DATE_ATOM),
        ];
    }
}