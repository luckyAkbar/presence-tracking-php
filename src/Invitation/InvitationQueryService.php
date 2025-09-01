<?php

declare(strict_types=1);

namespace App\Invitation;

use App\Support\Db;
use App\Security\EmailEncryption;
use PDO;

final class InvitationQueryService
{
    public function __construct(
        private Db $db,
        private EmailEncryption $emailEncryption,
    ) {}

    public function findByIntendedUserEmail(string $email, int $organization_id): ?Invitation
    {
       $sql = 'SELECT i.*
            FROM invitations i
            INNER JOIN users u ON i.intended_for = u.id
            WHERE u.email_hash = :email AND i.deleted_at IS NULL AND u.deleted_at IS NULL
            AND i.organization_id = :organization_id
            LIMIT 1;
        ';

        $hashedEmail = $this->emailEncryption->hashEmail($email);

        $stmt = $this->db->connection()->prepare($sql);
        $stmt->execute([
            'email' => $hashedEmail,
            'organization_id' => $organization_id,
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result === false) {
            return null;
        }

        return InvitationsRepository::fromDbResult($result);
    }
    
    public function findById(int $id, PDO|null $conn = null): ?InvitationView
    {
        $sql = 'SELECT
            i.id,
            i.organization_id,
            o.name as organization_name,
            i.created_by as inviter_id, 
            inviter.username as inviter_name,
            i.intended_for as invitee_id,
            invitee.username as invitee_name,
            i.status as invitation_status,
            i.expires_at,
            i.created_at,
            i.updated_at,
            i.deleted_at
        FROM invitations i
        INNER JOIN organizations o ON i.organization_id = o.id
        INNER JOIN users inviter ON i.created_by = inviter.id
        INNER JOIN users invitee ON i.intended_for = invitee.id
        WHERE i.deleted_at IS NULL
            AND o.deleted_at IS NULL
            AND inviter.deleted_at IS NULL
            AND invitee.deleted_at IS NULL
            AND i.id = :id
            LIMIT 1;
        ';

        $connection = $this->safeCreateConnection($conn);
        $stmt = $connection->prepare($sql);
        $stmt->execute(['id' => $id]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result === false) {
            return null;
        }

        return InvitationView::fromQueryServiceResult($result);
    }

    public function findInvitationsIntendedForUser(int $user_id, int $limit=100, int $offset=0, PDO|null $conn = null): array | null
    {
        $sql = 'SELECT
            i.id,
            i.organization_id,
            o.name as organization_name,
            i.created_by as inviter_id, 
            inviter.username as inviter_name,
            i.intended_for as invitee_id,
            invitee.username as invitee_name,
            i.status as invitation_status,
            i.expires_at,
            i.created_at,
            i.updated_at,
            i.deleted_at
        FROM invitations i
        INNER JOIN organizations o ON i.organization_id = o.id
        INNER JOIN users inviter ON i.created_by = inviter.id
        INNER JOIN users invitee ON i.intended_for = invitee.id
        WHERE i.intended_for = :user_id
            AND i.deleted_at IS NULL
            AND o.deleted_at IS NULL
            AND inviter.deleted_at IS NULL
            AND invitee.deleted_at IS NULL
        ORDER BY i.created_at DESC
        LIMIT :limit
        OFFSET :offset;';

        $connection = $this->safeCreateConnection($conn);
        $stmt = $connection->prepare($sql);
        $stmt->bindValue(':user_id', (int) $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($results === false) {
            return null;
        }

        $invitations = [];

        foreach($results as $result) {
            $invitations[] = InvitationView::fromQueryServiceResult($result);
        }

        return $invitations;
    }

    public function search(array $search_params, PDO|null $conn = null): array | null
    {
        $whereClauses = [];
        $params = [];

        if (isset($search_params['organization_id'])) {
            $whereClauses[] = 'i.organization_id = :organization_id';
            $params[':organization_id'] = (int) $search_params['organization_id'];
        }

        if (isset($search_params['intended_for'])) {
            $whereClauses[] = 'i.intended_for = :intended_for';
            $params[':intended_for'] = (int) $search_params['intended_for'];
        }

        if (isset($search_params['statuses']) && is_array($search_params['statuses'])) {
            $placeholders = [];
            $idx = 0;
            foreach (array_values($search_params['statuses']) as $status) {
                $key = ':status_' . $idx;
                $placeholders[] = 'LOWER(' . $key . ')';
                $params[$key] = strtolower((string) $status);
                $idx++;
            }
            if (!empty($placeholders)) {
                $whereClauses[] = 'LOWER(i.status) IN (' . implode(',', $placeholders) . ')';
            }
        }

        // Base soft-delete filters
        $whereClauses[] = 'i.deleted_at IS NULL';
        $whereClauses[] = 'o.deleted_at IS NULL';
        $whereClauses[] = 'inviter.deleted_at IS NULL';
        $whereClauses[] = 'invitee.deleted_at IS NULL';

        $whereSql = implode(' AND ', $whereClauses);

        $limit = isset($search_params['limit']) ? (int) $search_params['limit'] : 100;
        $offset = isset($search_params['offset']) ? (int) $search_params['offset'] : 0;

        $sql = 'SELECT
            i.id,
            i.organization_id,
            o.name as organization_name,
            i.created_by as inviter_id, 
            inviter.username as inviter_name,
            i.intended_for as invitee_id,
            invitee.username as invitee_name,
            i.status as invitation_status,
            i.expires_at,
            i.created_at,
            i.updated_at,
            i.deleted_at
        FROM invitations i
        INNER JOIN organizations o ON i.organization_id = o.id
        INNER JOIN users inviter ON i.created_by = inviter.id
        INNER JOIN users invitee ON i.intended_for = invitee.id
        WHERE ' . $whereSql . '
        ORDER BY i.created_at DESC
        LIMIT :limit
        OFFSET :offset;';

        $connection = $this->safeCreateConnection($conn);
        $stmt = $connection->prepare($sql);

        // Bind dynamic filters
        foreach ($params as $key => $value) {
            if ($key === ':organization_id' || $key === ':intended_for') {
                $stmt->bindValue($key, (int) $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }

        // Bind pagination
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);

        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($results === false) {
            return null;
        }

        $views = [];
        foreach ($results as $row) {
            $views[] = InvitationView::fromQueryServiceResult($row);
        }

        if (count($views) === 0) {
            return null;
        }

        return $views;
    }

    public function safeCreateConnection(PDO|null $conn): PDO
    {
        if ($conn === null) {
            return $this->db->connection();
        }

        return $conn;
    }
}