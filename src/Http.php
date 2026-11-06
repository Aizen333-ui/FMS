<?php
declare(strict_types=1);

namespace Fms;

final class Http
{
    public static function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    public static function basePath(): string
    {
        $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');
        if ($dir === '/' || str_ends_with($dir, '/public')) {
            $dir = preg_replace('#/public$#', '', $dir) ?? $dir;
        }
        return $dir === '/' ? '' : $dir;
    }

    public static function url(string $path): string
    {
        if ($path === '') {
            $path = '/';
        }
        if ($path[0] !== '/') {
            $path = '/' . $path;
        }
        return self::basePath() . $path;
    }

    public static function redirect(string $path): never
    {
        header('Location: ' . self::url($path));
        exit;
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    public static function csrfField(): string
    {
        return '<input type="hidden" name="_csrf" value="' . self::e(self::csrfToken()) . '">';
    }

    public static function verifyCsrf(): void
    {
        $sent = (string) ($_POST['_csrf'] ?? '');
        $known = (string) ($_SESSION['csrf'] ?? '');
        if ($known === '' || !hash_equals($known, $sent)) {
            http_response_code(419);
            echo 'The form token is invalid or expired. Go back and submit the form again.';
            exit;
        }
    }

    public static function flash(string $type, string $message): void
    {
        $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
    }

    /** @return list<array{type:string,message:string}> */
    public static function flashes(): array
    {
        $flashes = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return is_array($flashes) ? $flashes : [];
    }

    public static function remember(array $input): void
    {
        $_SESSION['old'] = $input;
    }

    public static function old(string $key, string $default = ''): string
    {
        $old = $_SESSION['old'] ?? [];
        if (!is_array($old) || !array_key_exists($key, $old)) {
            return $default;
        }
        return (string) $old[$key];
    }

    public static function clearOld(): void
    {
        unset($_SESSION['old']);
    }

    public static function money(float|int|string $amount): string
    {
        return 'Rs ' . number_format((float) $amount, 2);
    }

    public static function moneyWhole(float|int|string $amount): string
    {
        return 'Rs ' . number_format((float) $amount, 0);
    }

    public static function qty(float|int|string $amount): string
    {
        $formatted = number_format((float) $amount, 2, '.', '');
        $formatted = rtrim(rtrim($formatted, '0'), '.');
        return $formatted === '' ? '0' : $formatted;
    }

    public static function percent(float $value): string
    {
        $formatted = number_format($value, 2, '.', '');
        $formatted = rtrim(rtrim($formatted, '0'), '.');
        if ($formatted === '' || $formatted === '-0') {
            $formatted = '0';
        }
        return $formatted . '%';
    }

    public static function like(string $term): string
    {
        $term = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);
        return '%' . $term . '%';
    }

    public static function post(string $key, string $default = ''): string
    {
        return trim((string) ($_POST[$key] ?? $default));
    }

    public static function query(string $key, string $default = ''): string
    {
        return trim((string) ($_GET[$key] ?? $default));
    }

    public static function render(string $view, array $data = [], ?string $layout = 'layout'): void
    {
        $app = require dirname(__DIR__) . '/config/app.php';
        $data['appName'] = $app['name'];
        $data['companyName'] = $app['company_name'];
        $render = static function () use ($view, $data, $layout): void {
            $vars = $data;
            unset($data);
            extract($vars, EXTR_SKIP);
            ob_start();
            require dirname(__DIR__) . '/templates/' . $view . '.php';
            $content = ob_get_clean();
            if ($layout === null) {
                echo $content;
                return;
            }
            require dirname(__DIR__) . '/templates/' . $layout . '.php';
        };
        $render();
    }

    public static function today(): string
    {
        return date('Y-m-d');
    }

    public static function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    public static function dateTimeFromDay(string $day): string
    {
        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $day);
        if ($parsed === false || $parsed->format('Y-m-d') !== $day) {
            throw new ValidationException('Enter a valid date.');
        }
        if ($day > self::today()) {
            throw new ValidationException('Future date not allowed.');
        }
        return $day . ' ' . date('H:i:s');
    }

    public static function nextDay(string $day): string
    {
        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $day);
        if ($parsed === false) {
            throw new ValidationException('Enter a valid date.');
        }
        return $parsed->modify('+1 day')->format('Y-m-d') . ' 00:00:00';
    }
}
