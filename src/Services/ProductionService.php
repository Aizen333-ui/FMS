<?php
declare(strict_types=1);

namespace Fms\Services;

use Fms\Audit;
use Fms\Auth;
use Fms\Catalog;
use Fms\Database;
use Fms\Http;
use Fms\ValidationException;

final class ProductionService
{
    public static function list(): array
    {
        return Database::all(
            'SELECT ProductionID, ProductName, Quantity, Date FROM Production ORDER BY ProductionID DESC'
        );
    }

    public static function search(string $search, string $filter): array
    {
        $sql = 'SELECT ProductName, Quantity, Date FROM Production WHERE 1 = 1';
        $params = [];
        if ($search !== '') {
            $sql .= ' AND ProductName LIKE ? ESCAPE \'\\\'';
            $params[] = Http::like($search);
        }
        if ($filter === 'In Stock') {
            $sql .= ' AND Quantity > 0';
        } elseif ($filter === 'Low Stock') {
            $sql .= ' AND Quantity <= ? AND Quantity > 0';
            $params[] = Catalog::PRODUCT_LOW_STOCK;
        } elseif ($filter !== '' && $filter !== 'All Products') {
            throw new ValidationException('Unknown stock filter.');
        }
        $sql .= ' ORDER BY ProductName';
        return Database::all($sql, $params);
    }

    public static function record(string $product, string $quantityText, string $day): void
    {
        $known = ProductService::find($product);
        if ($known === null) {
            throw new ValidationException('Please select a product');
        }
        if ($quantityText === '') {
            throw new ValidationException('Please enter quantity');
        }
        if (!is_numeric($quantityText)) {
            throw new ValidationException('Quantity must be numeric');
        }
        $quantity = (float) $quantityText;
        if ($quantity <= 0 || $quantity > Catalog::MAX_PRODUCTION_QTY) {
            throw new ValidationException('Quantity must be between 1 and 10000');
        }
        $when = Http::dateTimeFromDay($day);
        try {
            Database::execute(
                'INSERT INTO Production (ProductName, Quantity, Date) VALUES (?, ?, ?)',
                [$product, $quantity, $when]
            );
        } catch (\Throwable $e) {
            Audit::add(
                Auth::username(),
                'CREATE',
                'Production',
                "Failed to record production for '{$product}'. Error: {$e->getMessage()}",
                'Failed'
            );
            throw new ValidationException('Error saving production: ' . $e->getMessage());
        }
        $unit = (string) $known['Unit'];
        Audit::add(
            Auth::username(),
            'CREATE',
            'Production',
            "Recorded production of {$quantity} {$unit} for product '{$product}'",
            'Success'
        );
    }

    public static function available(string $product): float
    {
        return (float) Database::value(
            'SELECT COALESCE(SUM(Quantity), 0) FROM Production WHERE ProductName = ?',
            [$product]
        );
    }

    public static function totalsByProduct(): array
    {
        return Database::all(
            'SELECT ProductName, SUM(Quantity) AS TotalQty FROM Production GROUP BY ProductName ORDER BY ProductName'
        );
    }
}
