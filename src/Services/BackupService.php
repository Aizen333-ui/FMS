<?php
declare(strict_types=1);

namespace Fms\Services;

use Fms\Audit;
use Fms\Auth;
use Fms\Database;
use Fms\ValidationException;

/**
 * SQL Server BACKUP DATABASE is not available to the PHP application.
 * This writes a logical SQL snapshot of every application table.
 */
final class BackupService
{
    private const TABLES = [
        'Users',
        'SystemAdmins',
        'Workers',
        'Products',
        'Customers',
        'RawMaterial',
        'Payments',
        'Production',
        'MaterialUsage',
        'Sales',
        'SaleItems',
        'SalesPayment',
        'Returns',
        'AuditLogs',
    ];

    public static function sql(): string
    {
        $pdo = Database::pdo();
        $chunks = [
            '-- Factory Management System logical backup',
            '-- Generated: ' . date('c'),
            'BEGIN;',
        ];
        foreach (self::TABLES as $table) {
            if (!preg_match('/^[A-Za-z]+$/', $table)) {
                throw new ValidationException('Unknown table.');
            }
            $rows = $pdo->query('SELECT * FROM ' . $table)->fetchAll();
            if ($rows === []) {
                $chunks[] = '-- ' . $table . ' (empty)';
                continue;
            }
            $columns = array_keys($rows[0]);
            $columnList = implode(', ', $columns);
            foreach ($rows as $row) {
                $values = [];
                foreach ($columns as $column) {
                    $values[] = self::literal($row[$column]);
                }
                $chunks[] = 'INSERT INTO ' . $table . ' (' . $columnList . ') VALUES (' . implode(', ', $values) . ');';
            }
        }
        $chunks[] = 'COMMIT;';
        return implode("\n", $chunks) . "\n";
    }

    public static function store(): string
    {
        $sql = self::sql();
        $directory = dirname(__DIR__, 2) . '/storage/backups';
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new ValidationException('Backup folder could not be created.');
        }
        $name = 'FactoryDB_Backup_' . date('Ymd_His') . '.sql';
        $path = $directory . '/' . $name;
        if (file_put_contents($path, $sql) === false) {
            Audit::add(Auth::username(), 'BACKUP', 'Database Backup', 'Database backup failed: could not write file', 'Failed');
            throw new ValidationException('Backup failed: could not write the backup file.');
        }
        Audit::add(Auth::username(), 'BACKUP', 'Database Backup', "Database backup created successfully at '{$name}'", 'Success');
        return $name;
    }

    private static function literal(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }
        return "'" . str_replace("'", "''", (string) $value) . "'";
    }
}
