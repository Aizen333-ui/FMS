<?php
declare(strict_types=1);

namespace Fms\Services;

use Fms\Audit;
use Fms\Auth;
use Fms\Database;
use Fms\ValidationException;
use PDOException;

final class ProductService
{
    public static function list(): array
    {
        return Database::all('SELECT ProductID, Name, Unit FROM Products ORDER BY Name');
    }

    public static function find(string $name): ?array
    {
        return Database::one(
            'SELECT ProductID, Name, Unit FROM Products WHERE Name = ?',
            [$name]
        );
    }

    public static function add(string $name, string $unit): void
    {
        $name = self::name($name);
        $unit = self::unit($unit);
        self::assertUnique($name, null);
        try {
            Database::execute('INSERT INTO Products (Name, Unit) VALUES (?, ?)', [$name, $unit]);
        } catch (PDOException $e) {
            throw new ValidationException(self::duplicate($e) ? 'A product with that name already exists.' : 'Error adding product: ' . $e->getMessage());
        }
        Audit::add(Auth::username(), 'CREATE', 'Products', "Added product '{$name}' ({$unit})", 'Success');
    }

    public static function remove(int $id): void
    {
        $current = Database::one('SELECT ProductID, Name FROM Products WHERE ProductID = ?', [$id]);
        if ($current === null) {
            throw new ValidationException('Select a product first.');
        }
        $name = (string) $current['Name'];
        $used = (int) Database::value('SELECT COUNT(*) FROM Production WHERE ProductName = ?', [$name]);
        if ($used > 0) {
            throw new ValidationException('This product has production records and cannot be removed.');
        }
        Database::execute('DELETE FROM Products WHERE ProductID = ?', [$id]);
        Audit::add(Auth::username(), 'DELETE', 'Products', "Removed product '{$name}'", 'Success');
    }

    private static function name(string $name): string
    {
        $name = trim($name);
        if ($name === '' || strlen($name) > 100) {
            throw new ValidationException('Enter a product name (100 characters or fewer).');
        }
        return $name;
    }

    private static function unit(string $unit): string
    {
        $unit = trim($unit);
        if ($unit === '' || strlen($unit) > 30) {
            throw new ValidationException('Enter a unit, such as Piece or Sqft.');
        }
        return $unit;
    }

    private static function assertUnique(string $name, ?int $exceptId): void
    {
        $row = Database::one('SELECT ProductID FROM Products WHERE LOWER(Name) = LOWER(?)', [$name]);
        if ($row !== null && (int) $row['ProductID'] !== (int) $exceptId) {
            throw new ValidationException('A product with that name already exists.');
        }
    }

    private static function duplicate(PDOException $e): bool
    {
        $state = $e->errorInfo[0] ?? '';
        return $state === '23000' || str_contains(strtolower($e->getMessage()), 'unique');
    }
}
