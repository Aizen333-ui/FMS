<?php
declare(strict_types=1);

use Fms\Http;
use Fms\Router;

require dirname(__DIR__) . '/src/bootstrap.php';
require dirname(__DIR__) . '/src/routes.php';

set_exception_handler(static function (Throwable $e): void {
    http_response_code(500);
    Http::render('errors/database', [
        'title' => 'Something went wrong',
        'error' => $e->getMessage(),
    ]);
});

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$base = Http::basePath();
if ($base !== '' && str_starts_with($path, $base)) {
    $path = substr($path, strlen($base)) ?: '/';
}
$router = new Router();
register_routes($router);
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $path);
