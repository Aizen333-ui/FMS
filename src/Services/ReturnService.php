<?php
declare(strict_types=1);

namespace Fms\Services;

use Fms\Audit;
use Fms\Auth;
use Fms\Catalog;
use Fms\Database;
use Fms\ValidationException;

final class ReturnService
{
    public static function history(): array
    {
        return Database::all(
            'SELECT c.CustomerName, p.ProductName, r.Quantity, r.RefundAmount, r.RefundMethod,
                    r.Reason, r.ReturnDate
             FROM Returns r
             INNER JOIN Sales s ON r.SaleID = s.SaleID
             INNER JOIN Customers c ON s.CustomerID = c.CustomerID
             INNER JOIN Production p ON r.ProductionID = p.ProductionID
             ORDER BY r.ReturnDate DESC'
        );
    }

    public static function findInvoice(string $invoice): ?array
    {
        $invoice = trim($invoice);
        if ($invoice === '') {
            throw new ValidationException('Enter invoice number.');
        }
        $sale = Database::one(
            'SELECT s.SaleID, s.InvoiceNo, s.SaleDate, s.PaymentMethod, s.GrandTotal,
                    c.CustomerID, c.CustomerName
             FROM Sales s
             INNER JOIN Customers c ON s.CustomerID = c.CustomerID
             WHERE s.InvoiceNo = ?',
            [$invoice]
        );
        if ($sale === null) {
            return null;
        }
        $sale['items'] = Database::all(
            'SELECT si.SaleItemID, si.ProductionID, si.ProductName, si.Quantity, si.UnitPrice, si.TotalAmount,
                    COALESCE((SELECT SUM(r.Quantity) FROM Returns r WHERE r.SaleItemID = si.SaleItemID), 0) AS ReturnedQty
             FROM SaleItems si
             WHERE si.SaleID = ?',
            [(int) $sale['SaleID']]
        );
        return $sale;
    }

    public static function process(
        int $saleItemId,
        string $quantityText,
        string $reason,
        string $refundMethod,
        string $notes
    ): void {
        if (!in_array($reason, Catalog::RETURN_REASONS, true)) {
            throw new ValidationException('Please select return reason.');
        }
        if (!in_array($refundMethod, Catalog::REFUND_METHODS, true)) {
            throw new ValidationException('Please select refund method.');
        }
        if (!is_numeric($quantityText) || (float) $quantityText <= 0) {
            throw new ValidationException('Enter valid return quantity.');
        }
        $returnQty = round((float) $quantityText, 2);
        $item = Database::one(
            'SELECT SaleItemID, SaleID, ProductionID, ProductName, Quantity, UnitPrice
             FROM SaleItems WHERE SaleItemID = ?',
            [$saleItemId]
        );
        if ($item === null) {
            throw new ValidationException('Select an item to return.');
        }
        $sold = (float) $item['Quantity'];
        $already = (float) Database::value(
            'SELECT COALESCE(SUM(Quantity), 0) FROM Returns WHERE SaleItemID = ?',
            [$saleItemId]
        );
        if ($returnQty > ($sold - $already) + 0.00001) {
            throw new ValidationException('Return quantity cannot exceed sold quantity.');
        }
        $refund = round($returnQty * (float) $item['UnitPrice'], 2);
        $productName = (string) $item['ProductName'];
        try {
            Database::transaction(function () use ($item, $returnQty, $refund, $refundMethod, $reason, $notes): void {
                Database::execute(
                    'INSERT INTO Returns
                        (SaleID, SaleItemID, ProductionID, Quantity, RefundAmount, RefundMethod, Reason, Notes, ReturnDate)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        (int) $item['SaleID'],
                        (int) $item['SaleItemID'],
                        (int) $item['ProductionID'],
                        $returnQty,
                        $refund,
                        $refundMethod,
                        $reason,
                        trim($notes),
                        date('Y-m-d H:i:s'),
                    ]
                );
                if ($reason !== 'Damaged') {
                    $updated = Database::execute(
                        'UPDATE Production SET Quantity = Quantity + ? WHERE ProductionID = ?',
                        [$returnQty, (int) $item['ProductionID']]
                    );
                    if ($updated !== 1) {
                        throw new ValidationException('Stock record for this sale item no longer exists.');
                    }
                }
            });
        } catch (ValidationException $e) {
            Audit::add(
                Auth::username() ?? 'system',
                'Return Processed',
                'Returns',
                "Product: {$productName}, Qty: {$returnQty}",
                'Failed: ' . $e->getMessage()
            );
            throw $e;
        } catch (\Throwable $e) {
            Audit::add(
                Auth::username() ?? 'system',
                'Return Processed',
                'Returns',
                "Product: {$productName}, Qty: {$returnQty}",
                'Failed: ' . $e->getMessage()
            );
            throw new ValidationException('Return failed: ' . $e->getMessage());
        }
        Audit::add(
            Auth::username() ?? 'system',
            'Return Processed',
            'Returns',
            "Product: {$productName}, Qty: {$returnQty}",
            'Success'
        );
    }
}
