<?php

declare(strict_types=1);

namespace App\User;

use App\Support\Db;
use PDO;

final class UserRepository
{
    public function __construct(private Db $db)
    {
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
            $user['email'],
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

        if ($user === null) {
            return null;
        }

        return new User(
            $user['id'],
            $user['auth_id'],
            $user['email'],
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
        $sql = 'INSERT INTO users (
            auth_id,
            email,
            email_verified,
            username,
            created_at,
            updated_at,
            deleted_at
        ) VALUES (
            :auth_id,
            :email,
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
            'email' => $email,
            'email_verified' => (int) $emailVerified,
            'username' => $username,
        ]);

        $lastInsertId = $conn->lastInsertId();

        if ($lastInsertId === false) {
            throw new \Exception('Failed to create user');
        }

        return $this->findById((int) $lastInsertId);
    }
}