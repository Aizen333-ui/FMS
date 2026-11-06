<?php
declare(strict_types=1);

namespace Fms\Services;

use Fms\Audit;
use Fms\Auth;
use Fms\Catalog;
use Fms\Database;
use Fms\Http;
use Fms\Password;
use Fms\ValidationException;
use PDOException;

final class UserService
{
    public static function list(?string $search = null): array
    {
        if ($search === null || trim($search) === '') {
            return Database::all(
                'SELECT UserID, FullName, Username, Role, IsActive FROM Users ORDER BY UserID DESC'
            );
        }
        $like = Http::like(trim($search));
        return Database::all(
            'SELECT UserID, FullName, Username, Role, IsActive
             FROM Users
             WHERE Username LIKE ? ESCAPE \'\\\'
                OR FullName LIKE ? ESCAPE \'\\\'
                OR Role LIKE ? ESCAPE \'\\\'
             ORDER BY UserID DESC',
            [$like, $like, $like]
        );
    }

    public static function add(string $fullName, string $username, string $password, string $role): void
    {
        $fullName = trim($fullName);
        $username = trim($username);
        if ($fullName === '' || $username === '' || $password === '' || !in_array($role, Catalog::STAFF_ROLES, true)) {
            throw new ValidationException('Please fill all fields including role.');
        }
        $hash = Password::hash($password);
        try {
            Database::execute(
                'INSERT INTO Users (FullName, Username, Password, PasswordHash, Role, IsActive)
                 VALUES (?, ?, ?, ?, ?, 1)',
                [$fullName, $username, $hash, $hash, $role]
            );
        } catch (PDOException $e) {
            throw new ValidationException(self::duplicate($e) ? 'Username already exists.' : 'Error adding user: ' . $e->getMessage());
        }
        Audit::add(Auth::username(), 'CREATE', 'Manage Users', "Created user account '{$username}'", 'Success');
    }

    public static function update(int $id, string $fullName, string $username, string $password, string $role): void
    {
        $fullName = trim($fullName);
        $username = trim($username);
        if ($fullName === '' || $username === '' || !in_array($role, Catalog::STAFF_ROLES, true)) {
            throw new ValidationException('Please fill name, username, and role.');
        }
        $current = Database::one(
            'SELECT FullName, Username, Role, IsActive FROM Users WHERE UserID = ?',
            [$id]
        );
        if ($current === null) {
            throw new ValidationException('Select a user first.');
        }
        $passwordChanged = $password !== '';
        $changed = $current['FullName'] !== $fullName
            || $current['Username'] !== $username
            || $current['Role'] !== $role;
        if (!$changed && !$passwordChanged) {
            throw new ValidationException('No changes were made.');
        }
        try {
            if ($passwordChanged) {
                $hash = Password::hash($password);
                Database::execute(
                    'UPDATE Users SET FullName = ?, Username = ?, Password = ?, PasswordHash = ?, Role = ? WHERE UserID = ?',
                    [$fullName, $username, $hash, $hash, $role, $id]
                );
            } else {
                Database::execute(
                    'UPDATE Users SET FullName = ?, Username = ?, Role = ? WHERE UserID = ?',
                    [$fullName, $username, $role, $id]
                );
            }
        } catch (PDOException $e) {
            throw new ValidationException(self::duplicate($e) ? 'Username already exists.' : 'Error updating user: ' . $e->getMessage());
        }
        Audit::add(Auth::username(), 'UPDATE', 'Manage Users', "Updated user account '{$username}'", 'Success');
    }

    public static function delete(int $id): void
    {
        $user = Database::one('SELECT UserID, Username FROM Users WHERE UserID = ?', [$id]);
        if ($user === null) {
            throw new ValidationException('Select a user first.');
        }
        Database::execute('DELETE FROM Users WHERE UserID = ?', [$id]);
        Audit::add(Auth::username(), 'DELETE', 'Manage Users', "Deleted user account with ID '{$id}'", 'Success');
    }

    public static function resetPassword(int $id, string $password, string $confirm): void
    {
        if (trim($password) === '') {
            throw new ValidationException('Enter a new password.');
        }
        if ($password !== $confirm) {
            throw new ValidationException('Passwords do not match.');
        }
        $user = Database::one('SELECT Username FROM Users WHERE UserID = ?', [$id]);
        if ($user === null) {
            throw new ValidationException('User not found.');
        }
        try {
            $hash = Password::hash($password);
            $count = Database::execute(
                'UPDATE Users SET Password = ?, PasswordHash = ? WHERE UserID = ?',
                [$hash, $hash, $id]
            );
        } catch (\Throwable $e) {
            Audit::add(Auth::username(), 'RESET PASSWORD', 'Manage Users', 'Failed to reset password: ' . $e->getMessage(), 'Failed');
            throw new ValidationException('Error resetting password: ' . $e->getMessage());
        }
        if ($count < 1) {
            throw new ValidationException('User not found.');
        }
        Audit::add(Auth::username(), 'RESET PASSWORD', 'Manage Users', "Reset password for user '{$user['Username']}'", 'Success');
    }

    public static function toggle(int $id): bool
    {
        $user = Database::one('SELECT IsActive FROM Users WHERE UserID = ?', [$id]);
        if ($user === null) {
            throw new ValidationException('Please select a user.');
        }
        $newStatus = (int) $user['IsActive'] === 1 ? 0 : 1;
        Database::execute('UPDATE Users SET IsActive = ? WHERE UserID = ?', [$newStatus, $id]);
        Audit::add(
            Auth::username(),
            $newStatus === 1 ? 'ENABLE USER' : 'DISABLE USER',
            'Manage Users',
            ($newStatus === 1 ? 'Enabled' : 'Disabled') . " user account with ID '{$id}'",
            'Success'
        );
        return $newStatus === 1;
    }

    private static function duplicate(PDOException $e): bool
    {
        $state = $e->errorInfo[0] ?? '';
        return $state === '23000' || str_contains(strtolower($e->getMessage()), 'unique');
    }
}
