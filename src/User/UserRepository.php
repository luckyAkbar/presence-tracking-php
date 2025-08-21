<?php

declare(strict_types=1);

namespace App\User;

use App\Support\Db;
use App\Security\EmailEncryption;
use PDO;

final class UserRepository
{
    public function __construct(
        private Db $db,
        private EmailEncryption $emailEncryption
    ) {
    }

    /**
     * Find a user by their auth ID
     *
     * @param string $authId
     * @return User|null
     */
    public function findByAuthId(string $authID): ?User
    {
        $sql = 'SELECT * FROM users WHERE auth_id = :auth_id AND deleted_at IS NULL';

        $conn = $this->db->connection();
        $stmt = $conn->prepare($sql);
        $stmt->execute(['auth_id' => $authID]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user === false) {
            return null;
        }

        return new User(
            $user['id'],
            $user['auth_id'],
            $this->emailEncryption->decryptEmail($user['email_encrypted'], $user['encryption_version']),
            $user['email_hash'],
            $user['email_encrypted'],
            $user['encryption_version'],
            $user['email_verified'],
            $user['username'],
            $user['created_at'],
            $user['updated_at'],
            $user['deleted_at'],
        );
    }

    /**
     * Find a user by their ID
     *
     * @param int $id
     * @return User|null
     */
    public function findById(int $id): ?User
    {
        $sql = 'SELECT * FROM users WHERE id = :id AND deleted_at IS NULL LIMIT 1';

        $conn = $this->db->connection();
        $stmt = $conn->prepare($sql);
        $stmt->execute(['id' => $id]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user === false) {
            return null;
        }

        return new User(
            $user['id'],
            $user['auth_id'],
            $this->emailEncryption->decryptEmail($user['email_encrypted'], $user['encryption_version']),
            $user['email_hash'],
            $user['email_encrypted'],
            $user['encryption_version'],
            $user['email_verified'],
            $user['username'],
            $user['created_at'],
            $user['updated_at'],
            $user['deleted_at'],
        );
    }

    /**
     * Create a new user in the database
     *
     * @param string $authId must be unique
     * @param string $email
     * @param string $username
     * @param bool $emailVerified
     * 
     * @return User
     */
    public function createUser(string $authId, string $email, string $username, bool $emailVerified): User
    {
        // Process email for encryption and hashing
        $emailData = $this->emailEncryption->processEmail($email);
        
        $sql = 'INSERT INTO users (
            auth_id,
            email_hash,
            email_encrypted,
            encryption_version,
            email_verified,
            username,
            created_at,
            updated_at,
            deleted_at
        ) VALUES (
            :auth_id,
            :email_hash,
            :email_encrypted,
            :encryption_version,
            :email_verified,
            :username,
            NOW(),
            NOW(),
            NULL
        )';

        $conn = $this->db->connection();
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'auth_id' => $authId,
            'email_hash' => $emailData['hash'],
            'email_encrypted' => $emailData['encrypted_data'],
            'encryption_version' => $emailData['version'],
            'email_verified' => (int) $emailVerified,
            'username' => $username,
        ]);

        $lastInsertId = $conn->lastInsertId();

        if ($lastInsertId === false) {
            throw new \Exception('Failed to create user');
        }

        $user = $this->findById((int) $lastInsertId);

        if ($user === null) {
            throw new \Exception('Failed to find user after creation');
        }

        return $user;
    }
    
    /**
     * Find a user by their email address (using encrypted lookup)
     *
     * @param string $email The email address to search for
     * @return User|null
     */
    public function findByEmail(string $email): ?User
    {
        $emailHash = $this->emailEncryption->hashEmail($email);
        
        $sql = 'SELECT * FROM users WHERE email_hash = :email_hash AND deleted_at IS NULL LIMIT 1';

        $conn = $this->db->connection();
        $stmt = $conn->prepare($sql);
        $stmt->execute(['email_hash' => $emailHash]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user === false) {
            return null;
        }

        return new User(
            $user['id'],
            $user['auth_id'],
            $this->emailEncryption->decryptEmail($user['email_encrypted'], $user['encryption_version']),
            $user['email_hash'],
            $user['email_encrypted'],
            $user['encryption_version'],
            $user['email_verified'],
            $user['username'],
            $user['created_at'],
            $user['updated_at'],
            $user['deleted_at'],
        );
    }
}