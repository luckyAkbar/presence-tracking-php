<?php

declare(strict_types=1);

namespace App\Support;

final class Transaction
{
    public function __construct(
        private Db $db
    ) {}

    public function executeInTransaction(callable $callback)
    {
        $pdo = $this->db->connection();
        
        try {
            $pdo->beginTransaction();
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}