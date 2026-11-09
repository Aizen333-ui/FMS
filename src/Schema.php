<?php
declare(strict_types=1);

namespace Fms;

use PDO;

final class Schema
{
    public static function ensureMaterialCatalog(PDO $pdo): void
    {
        $driver = (string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $definition = $driver === 'sqlite'
            ? 'Name TEXT PRIMARY KEY COLLATE NOCASE, Unit TEXT NOT NULL'
            : 'Name VARCHAR(100) NOT NULL PRIMARY KEY, Unit VARCHAR(30) NOT NULL';
        $engine = $driver === 'sqlite' ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS MaterialCatalog (' . $definition . ')' . $engine
        );

        $rows = $pdo->query(
            "SELECT MIN(TRIM(Name)) AS Name, MAX(TRIM(Unit)) AS Unit
             FROM RawMaterial
             WHERE TRIM(Name) <> ''
             GROUP BY LOWER(TRIM(Name))"
        )->fetchAll(PDO::FETCH_ASSOC);
        $insert = $pdo->prepare(
            'INSERT INTO MaterialCatalog (Name, Unit)
             SELECT ?, ?
             WHERE NOT EXISTS (
                 SELECT 1 FROM MaterialCatalog WHERE LOWER(TRIM(Name)) = LOWER(TRIM(?))
             )'
        );
        foreach ($rows as $row) {
            $insert->execute([$row['Name'], $row['Unit'], $row['Name']]);
        }
    }

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
