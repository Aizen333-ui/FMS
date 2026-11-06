<?php
declare(strict_types=1);

namespace Fms\Services;

use Fms\Database;

final class DashboardService
{
    public static function owner(): array
    {
        $stock = (float) Database::value('SELECT COALESCE(SUM(Quantity), 0) FROM RawMaterial');
        $used = (float) Database::value('SELECT COALESCE(SUM(QuantityUsed), 0) FROM MaterialUsage');
        $usage = $stock == 0.0 ? 0.0 : min(100.0, $used * 100 / $stock);
        $monthStart = date('Y-m-01') . ' 00:00:00';
        $monthEnd = date('Y-m-01', strtotime('first day of next month')) . ' 00:00:00';
        return [
            'usage_percent' => $usage,
            'expenses' => PaymentService::total(),
            'production' => (float) Database::value('SELECT COALESCE(SUM(Quantity), 0) FROM Production'),
            'workers' => WorkerService::countsByRole(),
            'materials' => MaterialService::stockByName(),
            'material_chart' => self::share(
                Database::all(
                    'SELECT MaterialName AS Label, SUM(QuantityUsed) AS Total
                     FROM MaterialUsage
                     WHERE Date >= ? AND Date < ?
                     GROUP BY MaterialName',
                    [$monthStart, $monthEnd]
                )
            ),
            'expense_chart' => self::share(
                Database::all(
                    'SELECT UPPER(TRIM(Reason)) AS Label, SUM(Amount) AS Total
                     FROM Payments
                     WHERE Date >= ? AND Date < ?
                     GROUP BY UPPER(TRIM(Reason))',
                    [$monthStart, $monthEnd]
                )
            ),
        ];
    }

    public static function factory(): array
    {
        $production = (float) Database::value('SELECT COALESCE(SUM(Quantity), 0) FROM Production');
        $joined = Database::one(
            'SELECT COALESCE(SUM(mu.QuantityUsed), 0) AS Used, SUM(rm.Quantity) AS Stock
             FROM RawMaterial rm
             LEFT JOIN MaterialUsage mu ON rm.MaterialID = mu.MaterialID'
        );
        $stock = (float) ($joined['Stock'] ?? 0);
        $usage = $stock == 0.0 ? 0.0 : ((float) $joined['Used'] * 100 / $stock);
        $monthStart = date('Y-m-01') . ' 00:00:00';
        $monthEnd = date('Y-m-01', strtotime('first day of next month')) . ' 00:00:00';
        return [
            'production' => $production,
            'usage_percent' => $usage,
            'cards' => ProductionService::totalsByProduct(),
            'material_chart' => Database::all(
                'SELECT MaterialName AS Label, SUM(QuantityUsed) AS Total
                 FROM MaterialUsage
                 WHERE Date >= ? AND Date < ?
                 GROUP BY MaterialName',
                [$monthStart, $monthEnd]
            ),
            'production_chart' => Database::all(
                'SELECT ProductName AS Label, SUM(Quantity) AS Total
                 FROM Production
                 WHERE Date >= ? AND Date < ?
                 GROUP BY ProductName',
                [$monthStart, $monthEnd]
            ),
        ];
    }

    /** @param list<array<string,mixed>> $rows */
    private static function share(array $rows): array
    {
        $grand = 0.0;
        foreach ($rows as $row) {
            $grand += (float) $row['Total'];
        }
        $chart = [];
        foreach ($rows as $row) {
            $total = (float) $row['Total'];
            $chart[] = [
                'Label' => $row['Label'],
                'Total' => $grand > 0 ? ($total / $grand) * 100 : 0,
            ];
        }
        return $chart;
    }
}
