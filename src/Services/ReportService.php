<?php
declare(strict_types=1);

namespace Fms\Services;

use Fms\Catalog;
use Fms\Database;
use Fms\Http;
use Fms\ValidationException;

final class ReportService
{
    public static function owner(string $from, string $to, string $type = 'All'): array
    {
        self::assertRange($from, $to);
        $start = $from . ' 00:00:00';
        $end = Http::nextDay($to);
        $rows = [];
        foreach (Database::all(
            'SELECT MaterialName, QuantityUsed, Date FROM MaterialUsage
             WHERE Date >= ? AND Date < ? ORDER BY Date',
            [$start, $end]
        ) as $row) {
            $rows[] = [
                'Type' => 'Material Usage',
                'Name' => $row['MaterialName'],
                'Amount' => $row['QuantityUsed'],
                'Date' => $row['Date'],
            ];
        }
        foreach (Database::all(
            'SELECT ProductName, Quantity, Date FROM Production
             WHERE Date >= ? AND Date < ? ORDER BY Date',
            [$start, $end]
        ) as $row) {
            $rows[] = [
                'Type' => 'Production',
                'Name' => $row['ProductName'],
                'Amount' => $row['Quantity'],
                'Date' => $row['Date'],
            ];
        }
        foreach (Database::all(
            'SELECT c.CustomerName, s.SaleDate, s.GrandTotal
             FROM Sales s
             INNER JOIN Customers c ON s.CustomerID = c.CustomerID
             WHERE s.SaleDate >= ? AND s.SaleDate < ?
             ORDER BY s.SaleDate',
            [$start, $end]
        ) as $row) {
            $rows[] = [
                'Type' => 'Sale',
                'Name' => $row['CustomerName'],
                'Amount' => $row['GrandTotal'],
                'Date' => $row['SaleDate'],
            ];
        }
        foreach (Database::all(
            'SELECT c.CustomerName, r.RefundAmount, r.ReturnDate
             FROM Returns r
             INNER JOIN Sales s ON r.SaleID = s.SaleID
             INNER JOIN Customers c ON s.CustomerID = c.CustomerID
             INNER JOIN Production p ON r.ProductionID = p.ProductionID
             WHERE r.ReturnDate >= ? AND r.ReturnDate < ?
             ORDER BY r.ReturnDate',
            [$start, $end]
        ) as $row) {
            $rows[] = [
                'Type' => 'Return',
                'Name' => $row['CustomerName'],
                'Amount' => $row['RefundAmount'],
                'Date' => $row['ReturnDate'],
            ];
        }
        if ($type !== 'All') {
            if (!in_array($type, Catalog::REPORT_TYPES, true)) {
                throw new ValidationException('Unknown report filter.');
            }
            $rows = array_values(array_filter($rows, static fn (array $row): bool => $row['Type'] === $type));
        }
        return $rows;
    }

    public static function admin(): array
    {
        return [
            'total_users' => (int) Database::value('SELECT COUNT(*) FROM Users'),
            'active_users' => (int) Database::value('SELECT COUNT(*) FROM Users WHERE IsActive = 1'),
            'disabled_users' => (int) Database::value('SELECT COUNT(*) FROM Users WHERE IsActive = 0'),
            'admins' => (int) Database::value('SELECT COUNT(*) FROM SystemAdmins'),
            'by_role' => Database::all(
                'SELECT Role,
                        COUNT(*) AS Total,
                        SUM(CASE WHEN IsActive = 1 THEN 1 ELSE 0 END) AS Active,
                        SUM(CASE WHEN IsActive = 0 THEN 1 ELSE 0 END) AS Disabled
                 FROM Users
                 GROUP BY Role
                 ORDER BY Role'
            ),
            'recent' => Database::all(
                'SELECT UserID, FullName, Username, Role, IsActive
                 FROM Users ORDER BY UserID DESC LIMIT 20'
            ),
        ];
    }

    private static function assertRange(string $from, string $to): void
    {
        $fromDate = \DateTimeImmutable::createFromFormat('Y-m-d', $from);
        $toDate = \DateTimeImmutable::createFromFormat('Y-m-d', $to);
        if ($fromDate === false || $fromDate->format('Y-m-d') !== $from
            || $toDate === false || $toDate->format('Y-m-d') !== $to) {
            throw new ValidationException('Enter a valid date range.');
        }
        if ($from > $to) {
            throw new ValidationException('The start date must be on or before the end date.');
        }
    }
}
