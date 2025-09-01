<?php

declare(strict_types=1);

namespace App\Invitation;

use App\Support\Helper;
use DateTime;

final class InvitationView implements \JsonSerializable
{
    private int $id;
    private int $organization_id;
    private string $organization_name;
    private int $inviter_id;
    private string $inviter_name;
    private int $invitee_id;
    private string $invitee_name;
    private string $invitation_status;

    private string|\DateTimeImmutable|null $expires_at;
    private string|\DateTimeImmutable|null $created_at;
    private string|\DateTimeImmutable|null $updated_at;
    private string|\DateTimeImmutable|null $deleted_at;

    public function __construct(
        int $id,
        int $organization_id,
        string $organization_name,
        int $inviter_id,
        string $inviter_name,
        int $invitee_id,
        string $invitee_name,
        string $invitation_status,
        string|\DateTimeImmutable|null $expires_at,
        string|\DateTimeImmutable|null $created_at,
        string|\DateTimeImmutable|null $updated_at,
        string|\DateTimeImmutable|null $deleted_at,
    ) {
        $this->id = $id;
        $this->organization_id = $organization_id;
        $this->organization_name = $organization_name;
        $this->inviter_id = $inviter_id;
        $this->inviter_name = $inviter_name;
        $this->invitee_id = $invitee_id;
        $this->invitee_name = $invitee_name;
        $this->invitation_status = $invitation_status;
        $this->expires_at = Helper::convertToDateTimeImmutable($expires_at);
        $this->created_at = Helper::convertToDateTimeImmutable($created_at);
        $this->updated_at = Helper::convertToDateTimeImmutable($updated_at);
        $this->deleted_at = Helper::convertToDateTimeImmutable($deleted_at);
    }

    public static function fromQueryServiceResult(array $result): self
    {
        return new self(
            $result['id'],
            $result['organization_id'],
            $result['organization_name'],
            $result['inviter_id'],
            $result['inviter_name'],
            $result['invitee_id'],
            $result['invitee_name'],
            $result['invitation_status'],
            $result['expires_at'],
            $result['created_at'],
            $result['updated_at'],
            $result['deleted_at'],
        );

        return $invitation;
    }

    public function jsonSerialize(): array
    {
        $deleted_at = null;
        if ($this->deleted_at !== null) {
            $deleted_at = $this->deleted_at->format(DateTime::RFC3339);
        }

        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'organization_name' => $this->organization_name,
            'inviter_id' => $this->inviter_id,
            'inviter_name' => $this->inviter_name,
            'invitee_id' => $this->invitee_id,
            'invitee_name' => $this->invitee_name,
            'invitation_status' => $this->invitation_status,
            'expires_at' => $this->expires_at->format(DateTime::RFC3339),
            'created_at' => $this->created_at->format(DateTime::RFC3339),
            'updated_at' => $this->updated_at->format(DateTime::RFC3339),
            'deleted_at' => $deleted_at,
        ];
    }
}