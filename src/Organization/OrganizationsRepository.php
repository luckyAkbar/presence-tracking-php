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

    public function create(string $name, string $description, int $createdBy, PDO|null $pdo): Organization|null
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
        
        $conn = $this->safeCreateConnection($pdo);
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

        return $this->findById((int)$lastInsertId, $conn);
    }

    public function createOrganizationAdmin(int $organizationId, int $userId, PDO|null $pdo): int
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

        $conn = $this->safeCreateConnection($pdo);
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

    public function findById(int $id, PDO|null $pdo): ?Organization
    {
        $sql = 'SELECT * FROM organizations WHERE id = :id AND deleted_at IS NULL LIMIT 1';
        $conn = $this->safeCreateConnection($pdo);
        $stmt = $conn->prepare($sql);
        $stmt->execute(['id' => $id]);
        
        $result = $stmt->fetch();
        if ($result === false) {
            return null;
        }

        return self::fromDbResult($result);
    }

    public function safeCreateConnection(PDO|null $pdo): \PDO
    {
        if ($pdo === null) {
            return $this->db->connection();
        }

        return $pdo;
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