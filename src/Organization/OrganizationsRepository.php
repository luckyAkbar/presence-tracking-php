<?php

declare(strict_types=1);

namespace App\Organization;

use App\Support\Db;
use PDO;

final class OrganizationsRepository
{
    public function __construct(
        private Db $db,
    ) {}

    private function _create(PDO $conn, string $name, string $description, int $createdBy): int
    {
        $sql = 'INSERT INTO organizations (
            name,
            description,
            is_active,
            created_by,
            created_at,
            updated_at,
            deleted_at
        ) VALUES (
            :name,
            :description,
            TRUE,
            :created_by,
            NOW(),
            NOW(),
            NULL
        )';
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'name' => $name,
            'description' => $description,
            'created_by' => $createdBy,
        ]);

        $lastInsertId = $conn->lastInsertId();

        if ($lastInsertId === false) {
            throw new \Exception('Failed to create organization');
        }

        return (int) $lastInsertId;
    }

    private function _createOrganizationAdmin(PDO $conn, int $organizationId, int $userId): int
    {
        $sql = 'INSERT INTO organization_admins (
            organization_id,
            user_id,
            created_at,
            updated_at,
            deleted_at
        ) VALUES (
            :organization_id,
            :user_id,
            NOW(),
            NOW(),
            NULL
        )';

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'organization_id' => $organizationId,
            'user_id' => $userId,
        ]);

        $lastInsertId = $conn->lastInsertId();

        if ($lastInsertId === false) {
            throw new \Exception('Failed to register organization admin');
        }

        return (int) $lastInsertId;
    }

    public function create(string $name, string $description, int $createdBy): Organization
    {
        $result = $this->db->transaction(function (PDO $conn) use ($name, $description, $createdBy) {
            $organizationId = $this->_create($conn, $name, $description, $createdBy);
            $this->_createOrganizationAdmin($conn, $organizationId, $createdBy);
            
            return $this->findById($organizationId);
        });

        return $result;
    }

    public function findById(int $id): ?Organization
    {
        $sql = 'SELECT * FROM organizations WHERE id = :id AND deleted_at IS NULL LIMIT 1';
        $stmt = $this->db->connection()->prepare($sql);
        $stmt->execute(['id' => $id]);
        
        $result = $stmt->fetch();
        if ($result === false) {
            return null;
        }

        return self::fromDbResult($result);
    }

    public static function fromDbResult(array $result): Organization
    {
        return new Organization(
            $result['id'],
            $result['name'],
            $result['description'],
            $result['is_active'],
            $result['created_by'],
            new \DateTimeImmutable($result['created_at']),
            new \DateTimeImmutable($result['updated_at']),
            $result['deleted_at'] ? new \DateTimeImmutable($result['deleted_at']) : null,
        );
    }
}