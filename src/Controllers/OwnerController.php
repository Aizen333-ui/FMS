<?php
declare(strict_types=1);

namespace Fms\Controllers;

use Fms\Guard;
use Fms\Http;
use Fms\Services\DashboardService;
use Fms\Services\MaterialService;
use Fms\Services\PaymentService;
use Fms\Services\ProductService;
use Fms\Services\ReportService;
use Fms\Services\WorkerService;

final class OwnerController
{
    public static function dashboard(): void
    {
        Guard::database();
        Http::render('owner/dashboard', [
            'title' => 'Owner dashboard',
            'data' => DashboardService::owner(),
        ]);
    }

    public static function materials(): void
    {
        Guard::database();
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Guard::handle(function (): string {
                $action = Http::post('action');
                if ($action === 'add') {
                    MaterialService::add(
                        Http::post('name'),
                        Http::post('unit'),
                        Http::post('quantity'),
                        Http::post('unit_price')
                    );
                    return 'Material added successfully!';
                }
                if ($action === 'remove') {
                    $id = Http::post('material_id');
                    MaterialService::remove($id === '' ? null : (int) $id, Http::post('name'), Http::post('quantity'));
                    Http::flash('deleted', 'Stock removed successfully.');
                    return '';
                }
                throw new \Fms\ValidationException('Unknown action.');
            }, '/owner/materials');
        }
        Http::render('owner/materials', [
            'title' => 'Raw material',
            'rows' => MaterialService::list(),
            'materials' => MaterialService::materials(),
        ]);
    }

    public static function products(): void
    {
        Guard::database();
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Guard::handle(function (): string {
                $action = Http::post('action');
                $id = (int) Http::post('product_id');
                if ($action === 'add') {
                    ProductService::add(Http::post('name'), Http::post('unit'));
                    return 'Product added.';
                }
                if ($action === 'delete') {
                    ProductService::remove($id);
                    return 'Product removed.';
                }
                throw new \Fms\ValidationException('Unknown action.');
            }, '/owner/products');
        }
        Http::render('owner/products', [
            'title' => 'Products',
            'rows' => ProductService::list(),
        ]);
    }

    public static function payments(): void
    {
        Guard::database();
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Guard::handle(function (): string {
                $action = Http::post('action');
                if ($action === 'add') {
                    PaymentService::add(Http::post('reason'), Http::post('worker'), Http::post('amount'), Http::post('date'));
                    return 'Payment added!';
                }
                if ($action === 'adjust') {
                    $id = (int) Http::post('payment_id');
                    if ($id <= 0) {
                        throw new \Fms\ValidationException('Select a payment row first!');
                    }
                    return PaymentService::adjust($id, Http::post('reason'), Http::post('amount'));
                }
                throw new \Fms\ValidationException('Unknown action.');
            }, '/owner/payments');
        }
        Http::render('owner/payments', [
            'title' => 'Payments',
            'rows' => PaymentService::list(),
            'workers' => WorkerService::names(),
            'materials' => MaterialService::materials(),
        ]);
    }

    public static function workers(): void
    {
        Guard::database();
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Guard::handle(function (): string {
                $action = Http::post('action');
                if ($action === 'add') {
                    WorkerService::add(Http::post('name'), Http::post('role'), Http::post('salary'));
                    return 'Worker added successfully!';
                }
                if ($action === 'update') {
                    WorkerService::update(
                        (int) Http::post('worker_id'),
                        Http::post('name'),
                        Http::post('role'),
                        Http::post('salary')
                    );
                    return 'Worker updated successfully!';
                }
                if ($action === 'remove') {
                    WorkerService::remove((int) Http::post('worker_id'));
                    Http::flash('deleted', 'Worker removed successfully.');
                    return '';
                }
                throw new \Fms\ValidationException('Unknown action.');
            }, '/owner/workers');
        }
        Http::render('owner/workers', [
            'title' => 'Workers',
            'rows' => WorkerService::list(),
        ]);
    }

    public static function reports(): void
    {
        Guard::database();
        $from = Http::query('from', Http::today());
        $to = Http::query('to', Http::today());
        $type = Http::query('type', 'All');
        $rows = [];
        $loaded = isset($_GET['from']);
        if ($loaded) {
            try {
                $rows = ReportService::owner($from, $to, $type === '' ? 'All' : $type);
            } catch (\Fms\ValidationException $e) {
                Http::flash('error', $e->getMessage());
            }
        }
        Http::render('owner/reports', [
            'title' => 'Reports',
            'from' => $from,
            'to' => $to,
            'type' => $type === '' ? 'All' : $type,
            'rows' => $rows,
            'loaded' => $loaded,
        ]);
    }

}
