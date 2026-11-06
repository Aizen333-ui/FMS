<?php
declare(strict_types=1);

namespace Fms\Services;

use Fms\Audit;
use Fms\Auth;
use Fms\Catalog;
use Fms\Database;
use Fms\Http;
use Fms\ValidationException;

final class MaterialService
{
    public static function list(): array
    {
        return Database::all(
            'SELECT MaterialID, Name, Quantity, Unit, UnitPrice FROM RawMaterial ORDER BY MaterialID DESC'
        );
    }

    public static function stockByName(): array
    {
        $rows = Database::all(
            'SELECT UPPER(TRIM(Name)) AS Name, COALESCE(SUM(Quantity), 0) AS Quantity
             FROM RawMaterial GROUP BY UPPER(TRIM(Name))'
        );
        $stock = [];
        foreach (Catalog::MATERIALS as $name) {
            $stock[$name] = 0;
        }
        foreach ($rows as $row) {
            foreach (Catalog::MATERIALS as $name) {
                if (strtoupper(trim($name)) === (string) $row['Name']) {
                    $stock[$name] = (float) $row['Quantity'];
                }
            }
        }
        return $stock;
    }

    public static function add(string $name, string $quantityText, string $unitPriceText): void
    {
        if (!in_array($name, Catalog::MATERIALS, true)) {
            throw new ValidationException('Please fill all fields!');
        }
        if (!is_numeric($quantityText) || (float) $quantityText <= 0) {
            throw new ValidationException('Quantity must be a positive number.');
        }
        if (!is_numeric($unitPriceText) || (float) $unitPriceText <= 0) {
            throw new ValidationException('Unit price must be a positive number.');
        }
        $quantity = round((float) $quantityText, 2);
        $unitPrice = round((float) $unitPriceText, 2);
        $unit = Catalog::materialUnit($name);
        $total = round($quantity * $unitPrice, 2);
        try {
            Database::transaction(function () use ($name, $quantity, $unit, $unitPrice, $total): void {
                Database::execute(
                    'INSERT INTO RawMaterial (Name, Quantity, Unit, UnitPrice) VALUES (?, ?, ?, ?)',
                    [$name, $quantity, $unit, $unitPrice]
                );
                Database::execute(
                    'INSERT INTO Payments (Reason, Amount, Date) VALUES (?, ?, ?)',
                    [$name, $total, Http::now()]
                );
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Audit::add(Auth::username(), 'CREATE', 'Raw Material', 'Failed to add material: ' . $e->getMessage(), 'Failed');
            throw new ValidationException('Error adding material: ' . $e->getMessage());
        }
        Audit::add(
            Auth::username(),
            'CREATE',
            'Raw Material',
            "Added material '{$name}' quantity {$quantity} {$unit} at unit price Rs {$unitPrice}. Total expense Rs {$total}",
            'Success'
        );
    }

    /**
     * Desktop remove behaviour:
     * a selected row with no material name deletes that row;
     * a material name plus quantity reduces only the first stock line of that name.
     */
    public static function remove(?int $materialId, string $name, string $quantityText): string
    {
        $name = trim($name);
        if (!in_array($name, Catalog::MATERIALS, true)) {
            $name = '';
        }
        if ($materialId !== null && $name === '') {
            $row = Database::one('SELECT MaterialID FROM RawMaterial WHERE MaterialID = ?', [$materialId]);
            if ($row === null) {
                throw new ValidationException('Material not found.');
            }
            Database::execute('DELETE FROM RawMaterial WHERE MaterialID = ?', [$materialId]);
            Audit::add(Auth::username(), 'DELETE', 'Raw Material', "Deleted material record ID '{$materialId}'", 'Success');
            return 'Deleted successfully!';
        }
        if ($name === '') {
            throw new ValidationException('Enter material name.');
        }
        if (!is_numeric($quantityText) || (float) $quantityText <= 0) {
            throw new ValidationException('Enter valid quantity.');
        }
        $removeQty = round((float) $quantityText, 2);
        $row = Database::one(
            'SELECT MaterialID, Quantity FROM RawMaterial WHERE Name = ? ORDER BY MaterialID',
            [$name]
        );
        if ($row === null) {
            throw new ValidationException('Material not found.');
        }
        $current = (float) $row['Quantity'];
        if ($removeQty > $current + 0.00001) {
            throw new ValidationException('Not enough stock available.');
        }
        $newQty = round($current - $removeQty, 2);
        $id = (int) $row['MaterialID'];
        if ($newQty <= 0) {
            Database::execute('DELETE FROM RawMaterial WHERE MaterialID = ?', [$id]);
        } else {
            Database::execute(
                'UPDATE RawMaterial SET Quantity = ? WHERE MaterialID = ?',
                [$newQty, $id]
            );
        }
        Audit::add(
            Auth::username(),
            'UPDATE',
            'Raw Material',
            "Reduced {$removeQty} from '{$name}'. Remaining quantity: {$newQty}",
            'Success'
        );
        return 'Stock updated successfully!';
    }

    /**
     * @return list<array{0:int,1:string,2:float}>
     */
    public static function planUsage(?int $lineId, string $dropdownName, string $quantityText): array
    {
        $quantity = null;
        if ($quantityText !== '') {
            if (!is_numeric($quantityText) || (float) $quantityText <= 0) {
                throw new ValidationException('Enter a valid quantity to remove, or leave it blank when a grid row is selected to remove that whole line.');
            }
            $quantity = round((float) $quantityText, 2);
        }
        if ($lineId !== null) {
            $row = Database::one(
                'SELECT MaterialID, Name, Quantity FROM RawMaterial WHERE MaterialID = ?',
                [$lineId]
            );
            if ($row === null) {
                throw new ValidationException('Select material from the dropdown, or click a grid row — leave quantity blank to clear that row, or enter an amount.');
            }
            $rowQty = (float) $row['Quantity'];
            $toRemove = $quantity ?? $rowQty;
            if ($toRemove <= 0) {
                throw new ValidationException('Nothing to remove for this row.');
            }
            if ($toRemove > $rowQty + 0.00001) {
                throw new ValidationException(
                    "Quantity ({$toRemove}) is more than this stock line ({$rowQty}). Either select a smaller amount or use the dropdown only — removal will spread across every line that uses this material."
                );
            }
            return [[(int) $row['MaterialID'], (string) $row['Name'], $toRemove]];
        }
        if (!in_array($dropdownName, Catalog::MATERIALS, true)) {
            throw new ValidationException('Select material from the dropdown, or click a grid row — leave quantity blank to clear that row, or enter an amount.');
        }
        if ($quantity === null) {
            throw new ValidationException('Pick a grid row and leave quantity blank to remove that whole line, or enter how much to take from stock.');
        }
        $lines = Database::all(
            'SELECT MaterialID, Name, Quantity FROM RawMaterial WHERE Name = ? ORDER BY MaterialID',
            [$dropdownName]
        );
        if ($lines === []) {
            throw new ValidationException('Material not found in database.');
        }
        $remaining = $quantity;
        $deductions = [];
        foreach ($lines as $line) {
            if ($remaining <= 0) {
                break;
            }
            $lineQty = (float) $line['Quantity'];
            $take = min($lineQty, $remaining);
            if ($take > 0) {
                $deductions[] = [(int) $line['MaterialID'], (string) $line['Name'], round($take, 2)];
                $remaining = round($remaining - $take, 2);
            }
        }
        if ($remaining > 0.00001) {
            $available = round($quantity - $remaining, 2);
            throw new ValidationException(
                "Not enough quantity in stock ({$available} available for {$dropdownName}, you asked for {$quantity})."
            );
        }
        return $deductions;
    }

    public static function recordUsage(?int $lineId, string $dropdownName, string $quantityText, string $day): void
    {
        $deductions = self::planUsage($lineId, $dropdownName, $quantityText);
        $when = Http::dateTimeFromDay($day);
        $displayName = $deductions[0][1];
        $total = 0.0;
        foreach ($deductions as $deduction) {
            $total += $deduction[2];
        }
        try {
            Database::transaction(function () use ($deductions, $when): void {
                foreach ($deductions as [$id, $name, $amount]) {
                    $updated = Database::execute(
                        'UPDATE RawMaterial SET Quantity = Quantity - ? WHERE MaterialID = ? AND Quantity >= ?',
                        [$amount, $id, $amount]
                    );
                    if ($updated !== 1) {
                        throw new ValidationException('Stock changed or insufficient quantity for one of the rows.');
                    }
                    Database::execute(
                        'INSERT INTO MaterialUsage (MaterialID, MaterialName, QuantityUsed, Date) VALUES (?, ?, ?, ?)',
                        [$id, $name, $amount, $when]
                    );
                }
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new ValidationException('Error removing: ' . $e->getMessage());
        }
        $unit = Catalog::usageUnit($displayName);
        Audit::add(
            Auth::username(),
            'REMOVE',
            'Raw Material Usage',
            "Removed {$total} {$unit} of {$displayName} from raw material stock",
            'Success'
        );
    }
}
