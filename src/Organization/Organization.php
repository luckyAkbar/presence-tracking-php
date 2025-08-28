<?php

declare(strict_types=1);

namespace App\Organization;

use App\Support\Helper;

final class Organization implements \JsonSerializable
{
    private ?\DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt;
    private ?\DateTimeImmutable $deletedAt;

    public function __construct(
        private int $id,
        private string $name,
        private string $description,
        private int $isActive,
        private int $createdBy,
        string|\DateTimeImmutable|null $createdAt = null,
        string|\DateTimeImmutable|null $updatedAt = null,
        string|\DateTimeImmutable|null $deletedAt = null,
    ) {
        $this->createdAt = Helper::convertToDateTimeImmutable($createdAt);
        $this->updatedAt = Helper::convertToDateTimeImmutable($updatedAt);
        $this->deletedAt = Helper::convertToDateTimeImmutable($deletedAt);
    }    

    public function getId(): int
    {
        return $this->id;
    }

    public function isActive(): bool
    {
        return $this->isActive === 1;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->isActive,
            'created_by' => $this->createdBy,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'deleted_at' => $this->deletedAt,
        ];
    }
}