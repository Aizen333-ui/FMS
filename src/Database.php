<?php
declare(strict_types=1);

namespace Fms;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function config(): array
    {
        $config = require dirname(__DIR__) . '/config/database.php';
        $override = getenv('FMS_CONNECTION_STRING');
        if (is_string($override) && $override !== '') {
            $config['connection_string'] = $override;
            $user = getenv('FMS_DB_USER');
            $password = getenv('FMS_DB_PASSWORD');
            $config['username'] = is_string($user) ? $user : '';
            $config['password'] = is_string($password) ? $password : '';
        }
        return $config;
    }

    public static function configured(): bool
    {
        $dsn = trim((string) (self::config()['connection_string'] ?? ''));
        return $dsn !== '';
    }

    public static function reset(): void
    {
        self::$pdo = null;
    }

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }
        if (!self::configured()) {
            throw new RuntimeException('Database connection string is empty. Set it in config/database.php.');
        }
        $config = self::config();
        try {
            $pdo = new PDO(
                (string) $config['connection_string'],
                (string) ($config['username'] ?? ''),
                (string) ($config['password'] ?? ''),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            throw new RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
        }
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $pdo->exec('PRAGMA foreign_keys = ON');
        }
        self::$pdo = $pdo;
        return $pdo;
    }

    public static function driver(): string
    {
        return (string) self::pdo()->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    public static function all(string $sql, array $params = []): array
    {
        $statement = self::pdo()->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public static function one(string $sql, array $params = []): ?array
    {
        $statement = self::pdo()->prepare($sql);
        $statement->execute($params);
        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

    public static function value(string $sql, array $params = []): mixed
    {
        $statement = self::pdo()->prepare($sql);
        $statement->execute($params);
        return $statement->fetchColumn();
    }

    public static function execute(string $sql, array $params = []): int
    {
        $statement = self::pdo()->prepare($sql);
        $statement->execute($params);
        return $statement->rowCount();
    }

    public static function transaction(callable $callback): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
