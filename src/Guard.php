<?php
declare(strict_types=1);

namespace Fms;

use Throwable;

final class Guard
{
    public static function database(): void
    {
        if (!Database::configured()) {
            http_response_code(503);
            Http::render('errors/database', [
                'title' => 'Database not configured',
                'error' => 'The database connection string in config/database.php is empty.',
            ]);
            exit;
        }
        try {
            Database::pdo();
        } catch (Throwable $e) {
            http_response_code(503);
            Http::render('errors/database', [
                'title' => 'Database unavailable',
                'error' => $e->getMessage(),
            ]);
            exit;
        }
    }

    public static function handle(callable $action, string $redirect): void
    {
        try {
            $message = $action();
            if (is_string($message) && $message !== '') {
                Http::flash('success', $message);
            }
            Http::clearOld();
        } catch (ValidationException $e) {
            Http::flash('error', $e->getMessage());
            Http::remember($_POST);
        } catch (Throwable $e) {
            Http::flash('error', $e->getMessage());
            Http::remember($_POST);
        }
        Http::redirect($redirect);
    }
}
