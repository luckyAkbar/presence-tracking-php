<?php

declare(strict_types=1);

namespace App\Organization;

use App\Support\Helper;

final class OrganizationMember implements \JsonSerializable
{

    private ?\DateTimeImmutable $created_at;
    private ?\DateTimeImmutable $updated_at;
    private ?\DateTimeImmutable $deleted_at;

    public function __construct(
        private int $id,
        private int $organization_id,
        private int $user_id,
        string|\DateTimeImmutable|null $created_at = null,
        string|\DateTimeImmutable|null $updated_at = null,
        string|\DateTimeImmutable|null $deleted_at = null,
    ) {
        $this->created_at = Helper::convertToDateTimeImmutable($created_at);
        $this->updated_at = Helper::convertToDateTimeImmutable($updated_at);
        $this->deleted_at = Helper::convertToDateTimeImmutable($deleted_at);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}