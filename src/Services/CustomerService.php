<?php
declare(strict_types=1);

namespace Fms\Services;

use Fms\Audit;
use Fms\Auth;
use Fms\Database;
use Fms\Http;
use Fms\ValidationException;

final class CustomerService
{
    public static function list(?string $search = null): array
    {
        if ($search === null || $search === '') {
            return Database::all(
                'SELECT CustomerID, CustomerName, Phone, Address
                 FROM Customers ORDER BY CustomerID DESC'
            );
        }
        return Database::all(
            'SELECT CustomerID, CustomerName, Phone, Address
             FROM Customers
             WHERE CustomerName LIKE ? ESCAPE \'\\\'
                OR Phone LIKE ? ESCAPE \'\\\'
                OR Address LIKE ? ESCAPE \'\\\'
             ORDER BY CustomerID DESC',
            [$like = Http::like($search), $like, $like]
        );
    }

    public static function options(): array
    {
        return Database::all(
            'SELECT CustomerID, CustomerName FROM Customers ORDER BY CustomerName'
        );
    }

    public static function add(string $name, string $phone, string $address): void
    {
        $name = trim($name);
        $phone = trim($phone);
        $address = trim($address);
        if ($name === '') {
            throw new ValidationException('Please enter customer name.');
        }
        $count = Database::execute(
            'INSERT INTO Customers (CustomerName, Phone, Address) VALUES (?, ?, ?)',
            [$name, $phone, $address]
        );
        if ($count < 1) {
            throw new ValidationException('No rows affected. Customer was not added.');
        }
        Audit::add(Auth::username() ?? 'system', 'Customer Added', 'Customers', "Added customer '{$name}'", 'Success');
    }

    public static function update(int $id, string $name, string $phone, string $address): void
    {
        $name = trim($name);
        $phone = trim($phone);
        $address = trim($address);
        $current = Database::one(
            'SELECT CustomerName, Phone, Address FROM Customers WHERE CustomerID = ?',
            [$id]
        );
        if ($current === null) {
            throw new ValidationException('Please select a customer to edit.');
        }
        if ($name === (string) $current['CustomerName']
            && $phone === (string) $current['Phone']
            && $address === (string) $current['Address']) {
            throw new ValidationException('No changes were made.');
        }
        if ($name === '') {
            throw new ValidationException('Please enter customer name.');
        }
        $count = Database::execute(
            'UPDATE Customers SET CustomerName = ?, Phone = ?, Address = ? WHERE CustomerID = ?',
            [$name, $phone, $address, $id]
        );
        if ($count < 1) {
            throw new ValidationException('No rows affected. Customer was not updated.');
        }
        Audit::add(
            Auth::username() ?? 'system',
            'Customer Updated',
            'Customers',
            "Updated customer '{$name}' (ID={$id})",
            'Success'
        );
    }
}
