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
}