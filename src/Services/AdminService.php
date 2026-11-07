<?php
declare(strict_types=1);

namespace Fms\Services;

use Fms\Audit;
use Fms\Auth;
use Fms\Database;
use Fms\Password;
use Fms\ValidationException;
use PDOException;

final class AdminService
{
    public static function count(): int
    {
        return (int) Database::value('SELECT COUNT(*) FROM SystemAdmins');
    }

    public static function list(): array
    {
        return Database::all('SELECT AdminID, Username FROM SystemAdmins ORDER BY AdminID DESC');
    }

    public static function createFirst(string $username, string $password, string $confirm): void
    {
        $username = trim($username);
        if ($username === '' || $password === '') {
            throw new ValidationException('Please fill all fields');
        }
        if ($password !== $confirm) {
            throw new ValidationException('Passwords do not match');
        }
        if (self::count() > 0) {
            throw new ValidationException('An administrator account already exists.');
        }
        self::insert($username, $password, 'First Admin Setup', "Created first administrator account '{$username}'");
    }

    public static function add(string $username, string $password, string $confirm): void
    {
        $username = trim($username);
        if ($username === '' || $password === '') {
            throw new ValidationException('Enter username and password.');
        }
        if ($password !== $confirm) {
            throw new ValidationException('Passwords do not match.');
        }
        $exists = (int) Database::value('SELECT COUNT(*) FROM SystemAdmins WHERE Username = ?', [$username]);
        if ($exists > 0) {
            throw new ValidationException('Username already exists.');
        }
        self::insert($username, $password, 'Admin Accounts', "Created admin account '{$username}'");
    }

    public static function update(int $id, string $username, string $password, string $confirm): void
    {
        $username = trim($username);
        if ($username === '') {
            throw new ValidationException('Username is required.');
        }
        $current = Database::one('SELECT Username FROM SystemAdmins WHERE AdminID = ?', [$id]);
        if ($current === null) {
            throw new ValidationException('Select an admin first.');
        }
        $usernameChanged = (string) $current['Username'] !== $username;
        $passwordChanged = $password !== '';
        if (!$usernameChanged && !$passwordChanged) {
            throw new ValidationException('No changes were made.');
        }
        if ($passwordChanged && $password !== $confirm) {
            throw new ValidationException('Passwords do not match.');
        }
        if ($usernameChanged) {
            $exists = (int) Database::value(
                'SELECT COUNT(*) FROM SystemAdmins WHERE Username = ? AND AdminID <> ?',
                [$username, $id]
            );
            if ($exists > 0) {
                throw new ValidationException('Username already exists.');
            }
        }
        if ($passwordChanged) {
            $hash = Password::hash($password);
            Database::execute(
                'UPDATE SystemAdmins SET Username = ?, Password = ?, PasswordHash = ? WHERE AdminID = ?',
                [$username, $hash, $hash, $id]
            );
        } else {
            Database::execute(
                'UPDATE SystemAdmins SET Username = ? WHERE AdminID = ?',
                [$username, $id]
            );
        }
        if (Auth::user()['kind'] ?? '' === 'admin' && (int) (Auth::user()['id'] ?? 0) === $id) {
            $_SESSION['user']['username'] = $username;
            $_SESSION['user']['name'] = $username;
        }
        Audit::add(Auth::username(), 'UPDATE', 'Admin Accounts', "Updated admin account '{$username}'", 'Success');
    }

    public static function delete(int $id): void
    {
        if (self::count() <= 1) {
            throw new ValidationException('Cannot delete the last admin account.');
        }
        $admin = Database::one('SELECT Username FROM SystemAdmins WHERE AdminID = ?', [$id]);
        if ($admin === null) {
            throw new ValidationException('Select an admin first.');
        }
        Database::execute('DELETE FROM SystemAdmins WHERE AdminID = ?', [$id]);
        Audit::add(Auth::username(), 'DELETE', 'Admin Accounts', "Deleted admin account '{$admin['Username']}'", 'Success');
    }

    public static function logs(): array
    {
        return Database::all(
            'SELECT Username, Action, Module, Description, Status, LogDate
             FROM AuditLogs ORDER BY LogID DESC'
        );
    }

    public static function logExport(): string
    {
        $logs = self::logs();
        if ($logs === []) {
            throw new ValidationException('No logs available to export.');
        }
        $lines = [
            '====================================',
            ' Factory Management System Logs',
            ' Generated: ' . date('Y-m-d H:i:s'),
            '====================================',
            '',
        ];
        foreach ($logs as $log) {
            $lines[] = 'Date: ' . $log['LogDate']
                . ' | User: ' . $log['Username']
                . ' | Action: ' . $log['Action']
                . ' | Status: ' . $log['Status'];
            $lines[] = '------------------------------------';
        }
        Audit::add(Auth::username(), 'Export Logs', 'Reports', 'System logs exported successfully', 'Success');
        return implode("\n", $lines) . "\n";
    }

    private static function insert(string $username, string $password, string $module, string $description): void
    {
        $hash = Password::hash($password);
        try {
            Database::execute(
                'INSERT INTO SystemAdmins (Username, Password, PasswordHash) VALUES (?, ?, ?)',
                [$username, $hash, $hash]
            );
        } catch (PDOException $e) {
            $duplicate = ($e->errorInfo[0] ?? '') === '23000' || str_contains(strtolower($e->getMessage()), 'unique');
            throw new ValidationException($duplicate ? 'Username already exists.' : $e->getMessage());
        }
        Audit::add(Auth::username() ?? $username, 'CREATE', $module, $description, 'Success');
    }

    private static function redact(string $dsn): string
    {
        if ($dsn === '') {
            return '';
        }
        return (string) preg_replace('/(password=)[^;]*/i', '$1********', $dsn);
    }
}
