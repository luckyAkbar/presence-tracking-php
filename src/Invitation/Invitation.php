<?php

declare(strict_types=1);

namespace App\Invitation;

use App\Support\Helper;

final class Invitation implements \JsonSerializable
{
    // list of all known invitation status
    public const statusAccepted = 'accepted';
    public const statusRejected = 'rejected';
    public const statusCancelled = 'cancelled';
    public const statusPending = 'pending';

    // list of all available invitation operations
    public const operationAcceptInvitation = 'accept';
    public const operationRejectInvitation = 'reject';
    public const operationCancelInvitation = 'cancel';

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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getOrganizationId(): int
    {
        return $this->organization_id;
    }

    public function getIntendedForId(): int
    {
        return $this->intended_for_id;
    }

    public function isExpired(): bool
    {
        // If no expiration date is set, consider it expired for safety
        if ($this->expires_at === null) {
            return true;
        }
        
        $now = new \DateTimeImmutable();
            return $this->expires_at < $now;
    }


    /**
     * Ensure given operation is a valid operation to perform on this invitation
     * 
     * @param string $operation
     * @return bool
     */
    public function isAcceptanceOperationValid(string $operation): bool
    {
        switch ($operation) {
            case self::operationAcceptInvitation:
                if ($this->status === self::statusPending) {
                    return true;
                }
                break;
            case self::operationRejectInvitation:
                if ($this->status === self::statusPending) {
                    return true;
                }
                break;
            case self::operationCancelInvitation:
                if ($this->status === self::statusPending) {
                    return true;
                }
                break;
            default:
                return false;
        }

        return false;
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

    public static function getAllValidStatuses(): array
    {
        return [
            self::statusPending,
            self::statusAccepted,
            self::statusRejected,
            self::statusCancelled,
        ];
    }

    public static function isAllValidStatus(array $statuses): bool
    {
        $valid_statuses = self::getAllValidStatuses();

        $elemCount = count($statuses);
        if ($elemCount <= 0 || $elemCount > count(self::getAllValidStatuses())) {
            return false;
        }

        foreach ($statuses as $status) {
            if (!in_array($status, $valid_statuses)) {
                return false;
            }
        }

        return true;
    }

    public static function getAllValidOperations(): array
    {
        return [
            self::operationAcceptInvitation,
            self::operationRejectInvitation,
            self::operationCancelInvitation,
        ];
    }

    public static function isValidAcceptanceOperation(string $operation): bool
    {
        $valid_operations = self::getAllValidOperations();

        if (!in_array($operation, $valid_operations)) {
            return false;
        }

        return true;
    }
}