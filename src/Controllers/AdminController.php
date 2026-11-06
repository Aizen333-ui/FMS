<?php
declare(strict_types=1);

namespace Fms\Controllers;

use Fms\Guard;
use Fms\Http;
use Fms\Services\AdminService;
use Fms\Services\BackupService;
use Fms\Services\ReportService;
use Fms\Services\UserService;
use Fms\ValidationException;

final class AdminController
{
    public static function dashboard(): void
    {
        Guard::database();
        Http::render('admin/dashboard', [
            'title' => 'Admin dashboard',
            'data' => ReportService::admin(),
        ]);
    }

    public static function users(): void
    {
        Guard::database();
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Guard::handle(function (): string {
                $action = Http::post('action');
                return match ($action) {
                    'add' => self::added(),
                    'update' => self::updated(),
                    'delete' => self::deleted(),
                    'reset' => self::reset(),
                    'toggle' => self::toggled(),
                    default => throw new ValidationException('Unknown action.'),
                };
            }, '/admin/users' . (Http::query('q') !== '' ? '' : ''));
        }
        $search = Http::query('q');
        Http::render('admin/users', [
            'title' => 'Manage users',
            'rows' => UserService::list($search),
            'search' => $search,
        ]);
    }

    public static function accounts(): void
    {
        Guard::database();
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Guard::handle(function (): string {
                $action = Http::post('action');
                if ($action === 'add') {
                    AdminService::add(Http::post('username'), (string) ($_POST['password'] ?? ''), (string) ($_POST['confirm'] ?? ''));
                    return 'Admin account created.';
                }
                if ($action === 'update') {
                    AdminService::update(
                        (int) Http::post('admin_id'),
                        Http::post('username'),
                        (string) ($_POST['password'] ?? ''),
                        (string) ($_POST['confirm'] ?? '')
                    );
                    return 'Admin updated successfully.';
                }
                if ($action === 'delete') {
                    if (Http::post('confirm') !== 'yes') {
                        throw new ValidationException('Confirm the action before continuing.');
                    }
                    AdminService::delete((int) Http::post('admin_id'));
                    return 'Admin deleted.';
                }
                throw new ValidationException('Unknown action.');
            }, '/admin/accounts');
        }
        Http::render('admin/accounts', [
            'title' => 'Admin accounts',
            'rows' => AdminService::list(),
        ]);
    }

    public static function settings(): void
    {
        Guard::database();
        Http::render('admin/settings', [
            'title' => 'System settings',
            'status' => AdminService::connectionStatus(),
            'logs' => AdminService::logs(),
        ]);
    }

    public static function exportLogs(): void
    {
        Guard::database();
        try {
            $body = AdminService::logExport();
        } catch (ValidationException $e) {
            Http::flash('error', $e->getMessage());
            Http::redirect('/admin/settings');
        }
        $name = 'FactoryManagement_Logs_' . date('Ymd_His') . '.txt';
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        echo $body;
    }

    public static function reports(): void
    {
        Guard::database();
        Http::render('admin/reports', [
            'title' => 'Admin reports',
            'data' => ReportService::admin(),
        ]);
    }

    public static function backup(): void
    {
        Guard::database();
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            try {
                $name = BackupService::store();
                Http::flash('success', 'Backup completed successfully: ' . $name);
            } catch (ValidationException $e) {
                Http::flash('error', $e->getMessage());
            } catch (\Throwable $e) {
                Http::flash('error', 'Backup failed: ' . $e->getMessage());
            }
            Http::redirect('/admin/backup');
        }
        $files = glob(dirname(__DIR__, 2) . '/storage/backups/*.sql') ?: [];
        rsort($files);
        Http::render('admin/backup', [
            'title' => 'Database backup',
            'files' => array_map('basename', $files),
        ]);
    }

    public static function downloadBackup(): void
    {
        Guard::database();
        $name = basename(Http::query('file'));
        if (!preg_match('/^FactoryDB_Backup_\d{8}_\d{6}\.sql$/', $name)) {
            http_response_code(404);
            echo 'Backup not found.';
            return;
        }
        $path = dirname(__DIR__, 2) . '/storage/backups/' . $name;
        if (!is_file($path)) {
            http_response_code(404);
            echo 'Backup not found.';
            return;
        }
        header('Content-Type: application/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        readfile($path);
    }

    private static function added(): string
    {
        UserService::add(Http::post('full_name'), Http::post('username'), (string) ($_POST['password'] ?? ''), Http::post('role'));
        return 'User added successfully.';
    }

    private static function updated(): string
    {
        UserService::update(
            (int) Http::post('user_id'),
            Http::post('full_name'),
            Http::post('username'),
            (string) ($_POST['password'] ?? ''),
            Http::post('role')
        );
        return 'User updated successfully.';
    }

    private static function deleted(): string
    {
        if (Http::post('confirm') !== 'yes') {
            throw new ValidationException('Confirm the action before continuing.');
        }
        UserService::delete((int) Http::post('user_id'));
        return 'User deleted successfully.';
    }

    private static function reset(): string
    {
        UserService::resetPassword(
            (int) Http::post('user_id'),
            (string) ($_POST['password'] ?? ''),
            (string) ($_POST['confirm'] ?? '')
        );
        return 'Password reset successfully.';
    }

    private static function toggled(): string
    {
        $enabled = UserService::toggle((int) Http::post('user_id'));
        return $enabled ? 'User enabled successfully.' : 'User disabled successfully.';
    }
}
