<?php

declare(strict_types=1);

namespace App\Invitation;

use App\Support\Db;
use PDO;

final class InvitationsRepository
{
    public function __construct(
        private Db $db,
    ) {
    }

    /**
     * Create a new invitation. If the invitation already exists for the same user
     * and for the same organization, the same invitation will be returned.
     * 
     * @param string $user_email
     * @param int $organization_id
     * @param int $requester_id
     * @return Invitation|null
     */
    public function create(int $intended_for_id, int $organization_id, int $created_by_id, string $status, \DateTime $expires_at): int
    {
        $sql = 'INSERT INTO invitations (
            organization_id,
            created_by,
            intended_for,
            expires_at,
            status
        ) VALUES (
            :organization_id,
            :created_by,
            :intended_for,
            :expires_at,
            :status
        ) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)';

        $stmt = $this->db->connection()->prepare($sql);
        $stmt->execute([
            'organization_id' => $organization_id,
            'created_by' => $created_by_id,
            'intended_for' => $intended_for_id,
            'expires_at' => $expires_at->format('Y-m-d H:i:s'),
            'status' => $status,
        ]);

        $lastInsertId = $this->db->connection()->lastInsertId();
        if ($lastInsertId === false) {
            throw new \Exception('Failed to create invitation');
        }

        return (int)$lastInsertId;
    }

    /**
     * Find an invitation by its ID
     * 
     * @param int $id
     * @return Invitation|null
     */
    public function findById(int $id): ?Invitation
    {
        $sql = 'SELECT * FROM invitations WHERE id = :id AND deleted_at IS NULL';
        $stmt = $this->db->connection()->prepare($sql);
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        if ($result === false) {
            return null;
        }

        return self::fromDbResult($result);
    }

    public function findByIntendedForId(int $user_id): array | null
    {
        $sql = 'SELECT * FROM invitations WHERE intended_for = :user_id AND deleted_at IS NULL';
        $stmt = $this->db->connection()->prepare($sql);
        $stmt->execute(['user_id' => $user_id]);
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if ($result === false) {
            return null;
        }

        $invitations = [];
        foreach ($result as $row) {
            $invitations[] = self::fromDbResult($row);
        }

        if (count($invitations) === 0) {
            return null;
        }

        return $invitations;
    }

    public function search(array $search_params): array | null
    {
        $where_clauses = [];
        $where_params = [];

        if (isset($search_params['organization_id'])) {
            $where_clauses[] = 'organization_id = :organization_id';
            $where_params['organization_id'] = $search_params['organization_id'];
        }

        if (isset($search_params['intended_for'])) {
            $where_clauses[] = 'intended_for = :intended_for';
            $where_params['intended_for'] = $search_params['intended_for'];
        }

        if (isset($search_params['statuses']) && is_array($search_params['statuses'])) {
            $placeholders = [];
            foreach (array_values($search_params['statuses']) as $idx => $status) {
                $key = 'status_' . $idx;
                $placeholders[] = 'LOWER(:' . $key . ')';
                $where_params[$key] = strtolower((string) $status);
            }
            $where_clauses[] = 'LOWER(status) IN (' . implode(',', $placeholders) . ')';
        }

        // Add deleted_at filter
        $where_clauses[] = 'deleted_at IS NULL';

        $where_clause = implode(' AND ', $where_clauses);
        
        // Add pagination
        $limit = isset($search_params['limit']) ? (int) $search_params['limit'] : 100;
        $offset = isset($search_params['offset']) ? (int) $search_params['offset'] : 0;
        
        $sql = 'SELECT * FROM invitations WHERE ' . $where_clause . ' ORDER BY created_at DESC LIMIT :limit OFFSET :offset';
        $stmt = $this->db->connection()->prepare($sql);
        
        // Bind all parameters including limit and offset
        foreach ($where_params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if ($result === false) {
            return null;
        }

        $invitations = [];
        foreach ($result as $row) {
            $invitations[] = self::fromDbResult($row);
        }

        if (count($invitations) === 0) {
            return null;
        }

        return $invitations;
    }

    public function updateStatus(int $invitation_id, string $status, \PDO|null $pdo): void
    {
        $sql = 'UPDATE invitations SET status = :status, updated_at = NOW() WHERE id = :id';

        $conn = $this->safeCreateConnection($pdo);
        $stmt = $conn->prepare($sql);
        $stmt->execute(['id' => $invitation_id, 'status' => $status]);

        return;
    }

    public function safeCreateConnection(PDO|null $pdo): \PDO
    {
        if ($pdo === null) {
            return $this->db->connection();
        }

        return $pdo;
    }

    /**
     * Create an Invitation object from a database result
     * 
     * @param array $result
     * @return Invitation
     */
    public static function fromDbResult(array $result): Invitation
    {
        return new Invitation(
            $result['id'],
            $result['organization_id'],
            $result['created_by'],
            $result['intended_for'],
            $result['status'],
            $result['expires_at'],
            $result['created_at'],
            $result['updated_at'],
            $result['deleted_at'],
        );
    }
}