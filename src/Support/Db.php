<?php
declare(strict_types=1);

namespace App\Support;

use App\Config\Config;
use PDO;
use PDOException;

final class Db
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }
        $host = Config::getString('db_host', 'mysql');
        $port = Config::getInt('db_port', 3306);
        $db = Config::getString('db_name', 'presence');
        $user = Config::getString('db_user', 'presence');
        $pass = Config::getString('db_pass', 'presence_pass');

        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $db);
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        self::$pdo = new PDO($dsn, $user, $pass, $options);
        return self::$pdo;
    }

    public static function ping(): bool
    {
        try {
            $pdo = self::connection();
            $stmt = $pdo->query('SELECT 1');
            $value = $stmt !== false ? $stmt->fetchColumn() : false;
            return $value == 1; // deliberate loose compare for string/int
        } catch (PDOException $e) {
            return false;
        }
    }

    public static function transaction(callable $callback)
    {
        $pdo = self::connection();
        
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

    public static function lastInsertId(): string
    {
        return self::connection()->lastInsertId();
    }
}


