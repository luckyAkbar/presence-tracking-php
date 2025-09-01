<?php

declare(strict_types=1);

namespace App\Organization;

use App\Support\Db;
use PDO;

final class OrganizationMemberRepository
{
    public function __construct(private Db $db) {}

    public function create(int $organization_id, int $user_id, \PDO $pdo): OrganizationMember
    {
        $sql = 'INSERT INTO organization_members (
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
            'organization_id' => $organization_id,
            'user_id' => $user_id,
        ]);

        $lastInsertId = $conn->lastInsertId();
        if ($lastInsertId === false) {
            throw new \Exception('Failed to create organization member');
        }

        return $this->findById((int)$lastInsertId, $conn);
    }

    public function findById(int $id, PDO|null $pdo = null): ?OrganizationMember
    {
        $sql = 'SELECT * FROM organization_members WHERE id = :id AND deleted_at IS NULL LIMIT 1;';

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

    public static function fromDbResult(array $result): OrganizationMember
    {
        return new OrganizationMember(
            $result['id'],
            $result['organization_id'],
            $result['user_id'],
            $result['created_at'],
            $result['updated_at'],
            $result['deleted_at'],
        );
    }
}