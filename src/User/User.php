<?php
declare(strict_types=1);

namespace App\User;

/**
 * User Entity - Represents a user in the system
 */
final class User
{
    private ?\DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt;
    private ?\DateTimeImmutable $deletedAt;

    public function __construct(
        private ?int $id,
        private string $authId,
        private string $email,
        private int $emailVerified, // 0 or 1
        private string $username,
        string|\DateTimeImmutable|null $createdAt = null,
        string|\DateTimeImmutable|null $updatedAt = null,
        string|\DateTimeImmutable|null $deletedAt = null
    ) {
        $this->createdAt = $this->convertToDateTimeImmutable($createdAt);
        $this->updatedAt = $this->convertToDateTimeImmutable($updatedAt);
        $this->deletedAt = $this->convertToDateTimeImmutable($deletedAt);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuthId(): string
    {
        return $this->authId;
    }

    public function getEmail(): string
    {
        return $this->email;
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
     * Convert string or DateTimeImmutable to DateTimeImmutable
     * 
     * @param string|\DateTimeImmutable|null $value
     * @return \DateTimeImmutable|null
     * @throws \DateInvalidFormatException if string cannot be parsed as date
     */
    private function convertToDateTimeImmutable(string|\DateTimeImmutable|null $value): ?\DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeImmutable) {
            return $value;
        }

        // Handle string input - convert to DateTimeImmutable
        return new \DateTimeImmutable($value);
    }
}