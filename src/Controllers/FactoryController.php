<?php
declare(strict_types=1);

namespace Fms\Controllers;

use Fms\Guard;
use Fms\Http;
use Fms\Services\DashboardService;
use Fms\Services\MaterialService;
use Fms\Services\ProductService;
use Fms\Services\ProductionService;

final class FactoryController
{
    public static function dashboard(): void
    {
        Guard::database();
        Http::render('factory/dashboard', [
            'title' => 'Factory dashboard',
            'data' => DashboardService::factory(),
        ]);
    }

    public static function production(): void
    {
        Guard::database();
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Guard::handle(function (): string {
                ProductionService::record(Http::post('product'), Http::post('quantity'), Http::post('date'));
                return 'Production record saved successfully!';
            }, '/factory/production');
        }
        Http::render('factory/production', [
            'title' => 'Record production',
            'rows' => ProductionService::list(),
            'products' => ProductService::list(),
        ]);
    }

    public static function usage(): void
    {
        Guard::database();
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Guard::handle(function (): string {
                if (Http::post('confirm') !== 'yes') {
                    throw new \Fms\ValidationException('Confirm the action before continuing.');
                }
                $line = Http::post('material_id');
                MaterialService::recordUsage(
                    $line === '' ? null : (int) $line,
                    Http::post('name'),
                    Http::post('quantity'),
                    Http::post('date')
                );
                return 'Raw material deduction saved and usage recorded.';
            }, '/factory/usage');
        }
        Http::render('factory/usage', [
            'title' => 'Raw material usage',
            'rows' => MaterialService::list(),
        ]);
    }
}
