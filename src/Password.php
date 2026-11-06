<?php
declare(strict_types=1);

namespace Fms;

/**
 * Passwords are hashed for the web application.
 * A stored value that is not a password hash is compared as the desktop
 * application's plaintext password so an imported row can still sign in.
 */
final class Password
{
    public static function hash(string $plain): string
    {
        return password_hash($plain, PASSWORD_DEFAULT);
    }

    public static function verify(string $plain, ?string $stored): bool
    {
        if ($stored === null || $stored === '') {
            return false;
        }
        $info = password_get_info($stored);
        if (($info['algo'] ?? 0) !== 0) {
            return password_verify($plain, $stored);
        }
        return hash_equals($stored, $plain);
    }
}
