<?php
declare(strict_types=1);

namespace Fms;

final class Auth
{
    public static function attemptStaff(string $username, string $password): array
    {
        $username = trim($username);
        $password = trim($password);
        if ($username === '' || $password === '') {
            throw new ValidationException('Please enter username and password.');
        }
        $user = Database::one(
            'SELECT UserID, FullName, Username, Password, PasswordHash, Role, IsActive
             FROM Users WHERE Username = ?',
            [$username]
        );
        $stored = $user['Password'] ?? null;
        if ($user === null || !Password::verify($password, is_string($stored) ? $stored : null)) {
            $hash = $user['PasswordHash'] ?? null;
            if ($user === null || !Password::verify($password, is_string($hash) ? $hash : null)) {
                Audit::add($username, 'LOGIN', 'Security', 'Failed login attempt', 'Failed');
                throw new ValidationException('Invalid username or password!');
            }
        }
        if ((int) $user['IsActive'] !== 1) {
            Audit::add($username, 'LOGIN', 'Security', 'Disabled account login attempt', 'Failed');
            throw new ValidationException('This account is disabled.');
        }
        if (!in_array($user['Role'], Catalog::STAFF_ROLES, true)) {
            Audit::add($username, 'LOGIN', 'Security', 'Login rejected for unknown role', 'Failed');
            throw new ValidationException('Invalid username or password!');
        }
        self::login([
            'kind' => 'staff',
            'id' => (int) $user['UserID'],
            'username' => (string) $user['Username'],
            'name' => (string) $user['FullName'],
            'role' => (string) $user['Role'],
        ]);
        Audit::add($username, 'LOGIN', 'Security', 'User logged into the system', 'Success');
        return self::user();
    }

    public static function attemptAdmin(string $username, string $password): array
    {
        $username = trim($username);
        if ($username === '' || $password === '') {
            throw new ValidationException('Please enter username and password');
        }
        $admin = Database::one(
            'SELECT AdminID, Username, Password, PasswordHash FROM SystemAdmins WHERE Username = ?',
            [$username]
        );
        $ok = false;
        if ($admin !== null) {
            $ok = Password::verify($password, (string) $admin['Password'])
                || Password::verify($password, isset($admin['PasswordHash']) ? (string) $admin['PasswordHash'] : null);
        }
        if (!$ok) {
            Audit::add($username, 'LOGIN', 'Security', 'Invalid login attempt', 'Failed');
            throw new ValidationException('Invalid username or password');
        }
        self::login([
            'kind' => 'admin',
            'id' => (int) $admin['AdminID'],
            'username' => (string) $admin['Username'],
            'name' => (string) $admin['Username'],
            'role' => 'Administrator',
        ]);
        Audit::add(self::username(), 'LOGIN', 'Security', 'Admin logged into the system', 'Success');
        return self::user();
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user'] = $user;
        unset($_SESSION['cart']);
    }

    public static function logout(): void
    {
        $username = self::username() ?? 'Unknown';
        $kind = self::user()['kind'] ?? 'staff';
        $description = $kind === 'admin'
            ? 'Admin logged out from the system'
            : 'User logged out from the system';
        Audit::add($username, 'LOGOUT', 'Security', $description, 'Success');
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], (bool) $params['httponly']);
        }
        session_destroy();
        session_start();
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']['role'], $_SESSION['user']['username']);
    }

    public static function user(): array
    {
        return $_SESSION['user'] ?? [];
    }

    public static function username(): ?string
    {
        $name = self::user()['username'] ?? null;
        return is_string($name) ? $name : null;
    }

    public static function role(): ?string
    {
        $role = self::user()['role'] ?? null;
        return is_string($role) ? $role : null;
    }

    public static function home(): string
    {
        return match (self::role()) {
            'Owner' => '/owner',
            'Manager' => '/factory',
            'Sales Person' => '/sales',
            'Administrator' => '/admin',
            default => '/',
        };
    }
}
