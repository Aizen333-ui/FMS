<?php
declare(strict_types=1);

namespace Fms;

final class Audit
{
    public static function add(
        ?string $username,
        string $action,
        string $module,
        string $description,
        string $status
    ): void {
        try {
            Database::execute(
                'INSERT INTO AuditLogs (Username, Action, Module, Description, Status, LogDate)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [
                    self::cut($username ?? '', 100),
                    self::cut($action, 100),
                    self::cut($module, 100),
                    self::cut($description, 2000),
                    self::cut($status, 200),
                    date('Y-m-d H:i:s'),
                ]
            );
        } catch (\Throwable) {
            // Logging must not block the action that was attempted.
        }
    }

    private static function cut(string $value, int $max): string
    {
        if (strlen($value) <= $max) {
            return $value;
        }
        return substr($value, 0, $max);
    }
}
