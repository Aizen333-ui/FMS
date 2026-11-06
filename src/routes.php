<?php
declare(strict_types=1);

use Fms\Controllers\AdminController;
use Fms\Controllers\AuthController;
use Fms\Controllers\FactoryController;
use Fms\Controllers\OwnerController;
use Fms\Controllers\SalesController;
use Fms\Router;

function register_routes(Router $router): void
{
    $router->map('GET', '/', [AuthController::class, 'home']);
    $router->map('POST', '/login', [AuthController::class, 'login']);
    $router->map('POST', '/logout', [AuthController::class, 'logout'], ['Owner', 'Manager', 'Sales Person', 'Administrator']);

    $router->map('GET', '/admin/login', [AuthController::class, 'adminLoginForm']);
    $router->map('POST', '/admin/login', [AuthController::class, 'adminLogin']);
    $router->map('GET', '/admin/setup', [AuthController::class, 'setupForm']);
    $router->map('POST', '/admin/setup', [AuthController::class, 'setup']);

    $owner = ['Owner'];
    $router->map('GET', '/owner', [OwnerController::class, 'dashboard'], $owner);
    $router->map('GET', '/owner/materials', [OwnerController::class, 'materials'], $owner);
    $router->map('POST', '/owner/materials', [OwnerController::class, 'materials'], $owner);
    $router->map('GET', '/owner/products', [OwnerController::class, 'products'], $owner);
    $router->map('POST', '/owner/products', [OwnerController::class, 'products'], $owner);
    $router->map('GET', '/owner/payments', [OwnerController::class, 'payments'], $owner);
    $router->map('POST', '/owner/payments', [OwnerController::class, 'payments'], $owner);
    $router->map('GET', '/owner/workers', [OwnerController::class, 'workers'], $owner);
    $router->map('POST', '/owner/workers', [OwnerController::class, 'workers'], $owner);
    $router->map('GET', '/owner/reports', [OwnerController::class, 'reports'], $owner);

    $manager = ['Manager'];
    $router->map('GET', '/factory', [FactoryController::class, 'dashboard'], $manager);
    $router->map('GET', '/factory/production', [FactoryController::class, 'production'], $manager);
    $router->map('POST', '/factory/production', [FactoryController::class, 'production'], $manager);
    $router->map('GET', '/factory/usage', [FactoryController::class, 'usage'], $manager);
    $router->map('POST', '/factory/usage', [FactoryController::class, 'usage'], $manager);

    $sales = ['Sales Person'];
    $router->map('GET', '/sales', [SalesController::class, 'dashboard'], $sales);
    $router->map('GET', '/sales/new', [SalesController::class, 'sale'], $sales);
    $router->map('POST', '/sales/new', [SalesController::class, 'sale'], $sales);
    $router->map('GET', '/sales/products', [SalesController::class, 'products'], $sales);
    $router->map('GET', '/sales/customers', [SalesController::class, 'customers'], $sales);
    $router->map('POST', '/sales/customers', [SalesController::class, 'customers'], $sales);
    $router->map('GET', '/sales/history', [SalesController::class, 'history'], $sales);
    $router->map('GET', '/sales/returns', [SalesController::class, 'returns'], $sales);
    $router->map('POST', '/sales/returns', [SalesController::class, 'returns'], $sales);
    $router->map('GET', '/sales/invoice', [SalesController::class, 'invoice'], $sales);

    $admin = ['Administrator'];
    $router->map('GET', '/admin', [AdminController::class, 'dashboard'], $admin);
    $router->map('GET', '/admin/users', [AdminController::class, 'users'], $admin);
    $router->map('POST', '/admin/users', [AdminController::class, 'users'], $admin);
    $router->map('GET', '/admin/accounts', [AdminController::class, 'accounts'], $admin);
    $router->map('POST', '/admin/accounts', [AdminController::class, 'accounts'], $admin);
    $router->map('GET', '/admin/settings', [AdminController::class, 'settings'], $admin);
    $router->map('GET', '/admin/logs/export', [AdminController::class, 'exportLogs'], $admin);
    $router->map('GET', '/admin/reports', [AdminController::class, 'reports'], $admin);
    $router->map('GET', '/admin/backup', [AdminController::class, 'backup'], $admin);
    $router->map('POST', '/admin/backup', [AdminController::class, 'backup'], $admin);
    $router->map('GET', '/admin/backup/download', [AdminController::class, 'downloadBackup'], $admin);
}
