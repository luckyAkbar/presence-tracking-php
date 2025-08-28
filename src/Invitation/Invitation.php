<?php

declare(strict_types=1);

namespace App\Invitation;

use App\Support\Helper;

final class Invitation implements \JsonSerializable
{
    public function __construct(
        private int $id,
        private int $organization_id,
        private int $created_by_id,
        private int $intended_for_id,
        private string $status,
        private string|\DateTimeImmutable|null $expires_at,
        private string|\DateTimeImmutable|null $created_at,
        private string|\DateTimeImmutable|null $updated_at,
        private string|\DateTimeImmutable|null $deleted_at,
    ){
        $this->expires_at = Helper::convertToDateTimeImmutable($expires_at);
        $this->created_at = Helper::convertToDateTimeImmutable($created_at);
        $this->updated_at = Helper::convertToDateTimeImmutable($updated_at);
        $this->deleted_at = Helper::convertToDateTimeImmutable($deleted_at);
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'organizationId' => $this->organization_id,
            'intendedForId' => $this->intended_for_id,
            'createdById' => $this->created_by_id,
            'status' => $this->status,
            'expiresAt' => $this->expires_at,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
            'deletedAt' => $this->deleted_at,
        ];
    }
}