<?php
declare(strict_types=1);

namespace Fms;

use PDO;

final class Schema
{
    public static function apply(PDO $pdo): void
    {
        $driver = (string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $file = $driver === 'sqlite'
            ? dirname(__DIR__) . '/database/schema.sqlite.sql'
            : dirname(__DIR__) . '/database/schema.mysql.sql';
        if (!is_file($file)) {
            throw new \RuntimeException('Schema file not found: ' . $file);
        }
        if ($driver === 'sqlite') {
            $pdo->exec('PRAGMA foreign_keys = ON');
        }
        foreach (self::statements((string) file_get_contents($file)) as $statement) {
            $pdo->exec($statement);
        }
    }

    /** @return list<string> */
    public static function statements(string $sql): array
    {
        $sql = preg_replace('/--.*$/m', '', $sql) ?? $sql;
        $parts = explode(';', $sql);
        $statements = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part !== '') {
                $statements[] = $part;
            }
        }
        return $statements;
    }
}
