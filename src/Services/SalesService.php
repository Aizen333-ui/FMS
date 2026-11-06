<?php
declare(strict_types=1);

namespace Fms\Services;

use Fms\Audit;
use Fms\Auth;
use Fms\Catalog;
use Fms\Database;
use Fms\Http;
use Fms\ValidationException;

final class SalesService
{
    public static function totals(array $lines, float $discount, float $taxPercent): array
    {
        if ($discount < 0 || $taxPercent < 0) {
            throw new ValidationException('Discount and tax cannot be negative.');
        }
        $subtotal = 0.0;
        foreach ($lines as $line) {
            $subtotal += (float) $line['quantity'] * (float) $line['unit_price'];
        }
        $subtotal = round($subtotal, 2);
        $discount = round($discount, 2);
        $tax = round($subtotal * $taxPercent / 100, 2);
        $grand = round($subtotal - $discount + $tax, 2);
        if ($grand < 0) {
            throw new ValidationException('Grand total cannot be negative. Reduce the discount.');
        }
        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'tax_percent' => $taxPercent,
            'grand' => $grand,
        ];
    }

    public static function cart(): array
    {
        $cart = $_SESSION['cart'] ?? ['customer_id' => 0, 'lines' => []];
        if (!isset($cart['lines']) || !is_array($cart['lines'])) {
            $cart = ['customer_id' => 0, 'lines' => []];
        }
        return $cart;
    }

    public static function saveCart(array $cart): void
    {
        $_SESSION['cart'] = $cart;
    }

    public static function clearCart(): void
    {
        unset($_SESSION['cart']);
    }

    public static function addToCart(int $customerId, string $product, string $quantityText, string $priceText): void
    {
        if ($customerId <= 0 || Database::one('SELECT CustomerID FROM Customers WHERE CustomerID = ?', [$customerId]) === null) {
            throw new ValidationException('Select customer.');
        }
        if (ProductService::find($product) === null) {
            throw new ValidationException('Select product.');
        }
        if (!preg_match('/^\d+$/', $quantityText)) {
            throw new ValidationException('Enter valid quantity.');
        }
        $quantity = (int) $quantityText;
        if ($quantity <= 0) {
            throw new ValidationException('Enter valid quantity.');
        }
        if (!is_numeric($priceText) || (float) $priceText < 0) {
            throw new ValidationException('Enter valid price.');
        }
        $price = round((float) $priceText, 2);
        $cart = self::cart();
        if ($cart['lines'] !== [] && (int) $cart['customer_id'] !== $customerId) {
            throw new ValidationException(
                "You cannot change the customer while the cart contains products.\n\nPlease remove all products from the cart before changing the customer."
            );
        }
        $already = 0;
        foreach ($cart['lines'] as $line) {
            if ($line['product'] === $product) {
                $already += (int) $line['quantity'];
            }
        }
        $available = ProductionService::available($product);
        if ($already + $quantity > $available + 0.00001) {
            throw new ValidationException('Not enough stock available.');
        }
        $merged = false;
        foreach ($cart['lines'] as &$line) {
            if ($line['product'] === $product && abs((float) $line['unit_price'] - $price) < 0.001) {
                $line['quantity'] += $quantity;
                $merged = true;
                break;
            }
        }
        unset($line);
        if (!$merged) {
            $cart['lines'][] = [
                'product' => $product,
                'quantity' => $quantity,
                'unit_price' => $price,
            ];
        }
        $cart['customer_id'] = $customerId;
        self::saveCart($cart);
    }

    public static function removeFromCart(int $index, string $quantityText): void
    {
        $cart = self::cart();
        if (!isset($cart['lines'][$index])) {
            throw new ValidationException('Select a product from cart first.');
        }
        $current = (int) $cart['lines'][$index]['quantity'];
        if ($quantityText === '') {
            array_splice($cart['lines'], $index, 1);
        } else {
            if (!preg_match('/^\d+$/', $quantityText) || (int) $quantityText <= 0) {
                throw new ValidationException('Enter a valid quantity.');
            }
            $remove = (int) $quantityText;
            if ($remove > $current) {
                throw new ValidationException("Cannot remove {$remove}. Only {$current} unit(s) in cart.");
            }
            if ($remove === $current) {
                array_splice($cart['lines'], $index, 1);
            } else {
                $cart['lines'][$index]['quantity'] = $current - $remove;
            }
        }
        if ($cart['lines'] === []) {
            $cart['customer_id'] = 0;
        }
        self::saveCart($cart);
    }

    public static function availableProducts(): array
    {
        return Database::all(
            'SELECT ProductName, SUM(Quantity) AS Quantity
             FROM Production
             WHERE Quantity > 0
             GROUP BY ProductName
             ORDER BY ProductName'
        );
    }

    public static function recent(int $limit = 10): array
    {
        return Database::all(
            'SELECT s.InvoiceNo, c.CustomerName, s.SaleDate, s.GrandTotal, s.PaymentStatus
             FROM Sales s
             LEFT JOIN Customers c ON s.CustomerID = c.CustomerID
             ORDER BY s.SaleDate DESC
             LIMIT ' . (int) $limit
        );
    }

    public static function history(string $search, string $status): array
    {
        $sql = 'SELECT s.SaleID, s.InvoiceNo, c.CustomerName, s.SaleDate, s.SubTotal, s.Discount,
                       s.Tax, s.GrandTotal, s.PaymentMethod, s.PaymentStatus
                FROM Sales s
                LEFT JOIN Customers c ON s.CustomerID = c.CustomerID
                WHERE 1 = 1';
        $params = [];
        if ($search !== '') {
            $sql .= ' AND (s.InvoiceNo LIKE ? ESCAPE \'\\\' OR c.CustomerName LIKE ? ESCAPE \'\\\')';
            $like = Http::like($search);
            $params[] = $like;
            $params[] = $like;
        }
        if ($status !== '' && $status !== 'All Statuses') {
            if (!in_array($status, Catalog::PAYMENT_STATUSES, true)) {
                throw new ValidationException('Unknown payment status.');
            }
            $sql .= ' AND s.PaymentStatus = ?';
            $params[] = $status;
        }
        $sql .= ' ORDER BY s.SaleDate DESC';
        return Database::all($sql, $params);
    }

    public static function complete(
        int $customerId,
        array $lines,
        float $discount,
        float $taxPercent,
        string $method,
        string $status
    ): int {
        if ($customerId <= 0 || Database::one('SELECT CustomerID FROM Customers WHERE CustomerID = ?', [$customerId]) === null) {
            throw new ValidationException('Select customer.');
        }
        if ($lines === []) {
            throw new ValidationException('Cart is empty.');
        }
        if (!in_array($method, Catalog::PAYMENT_METHODS, true) || !in_array($status, Catalog::PAYMENT_STATUSES, true)) {
            throw new ValidationException('Select payment details.');
        }
        $totals = self::totals($lines, $discount, $taxPercent);
        try {
            $saleId = Database::transaction(function () use ($customerId, $lines, $totals, $method, $status): int {
                $invoice = 'INV-' . date('YmdHis') . random_int(1000, 9999);
                Database::execute(
                    'INSERT INTO Sales
                        (InvoiceNo, CustomerID, SaleDate, SubTotal, Discount, Tax, GrandTotal, PaymentStatus, PaymentMethod)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $invoice,
                        $customerId,
                        date('Y-m-d H:i:s'),
                        $totals['subtotal'],
                        $totals['discount'],
                        $totals['tax'],
                        $totals['grand'],
                        $status,
                        $method,
                    ]
                );
                $saleId = (int) Database::pdo()->lastInsertId();
                foreach ($lines as $line) {
                    self::allocateLine($saleId, (string) $line['product'], (int) $line['quantity'], (float) $line['unit_price']);
                }
                Database::execute(
                    'INSERT INTO SalesPayment (SaleID, AmountPaid, PaymentMethod, PaymentDate, PaymentStatus)
                     VALUES (?, ?, ?, ?, ?)',
                    [$saleId, $totals['grand'], $method, date('Y-m-d H:i:s'), $status]
                );
                return $saleId;
            });
        } catch (ValidationException $e) {
            Audit::add(Auth::username(), 'SALE', 'Sales Dashboard', $e->getMessage(), 'Failure');
            throw $e;
        } catch (\Throwable $e) {
            Audit::add(Auth::username(), 'SALE', 'Sales Dashboard', $e->getMessage(), 'Failure');
            throw new ValidationException('Sale failed: ' . $e->getMessage());
        }
        Audit::add(Auth::username(), 'SALE', 'Sales Dashboard', "Completed sale ID {$saleId}", 'Success');
        self::clearCart();
        $_SESSION['last_sale_id'] = $saleId;
        return $saleId;
    }

    private static function allocateLine(int $saleId, string $product, int $quantity, float $price): void
    {
        if (ProductService::find($product) === null || $quantity <= 0) {
            throw new ValidationException('Select product.');
        }
        $rows = Database::all(
            'SELECT ProductionID, Quantity FROM Production
             WHERE ProductName = ? AND Quantity > 0
             ORDER BY ProductionID',
            [$product]
        );
        $remaining = $quantity;
        $slices = [];
        foreach ($rows as $row) {
            if ($remaining <= 0) {
                break;
            }
            $have = (float) $row['Quantity'];
            $take = (int) min($remaining, (int) floor($have + 0.00001));
            if ($take > 0) {
                $slices[] = [(int) $row['ProductionID'], $take];
                $remaining -= $take;
            }
        }
        if ($remaining > 0) {
            throw new ValidationException('Not enough stock available.');
        }
        foreach ($slices as [$productionId, $take]) {
            $updated = Database::execute(
                'UPDATE Production SET Quantity = Quantity - ? WHERE ProductionID = ? AND Quantity >= ?',
                [$take, $productionId, $take]
            );
            if ($updated !== 1) {
                throw new ValidationException('Not enough stock available.');
            }
            $amount = round($take * $price, 2);
            Database::execute(
                'INSERT INTO SaleItems (SaleID, ProductionID, ProductName, Quantity, UnitPrice, TotalAmount)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [$saleId, $productionId, $product, $take, $price, $amount]
            );
        }
    }

    public static function findIdByInvoice(string $invoice): ?int
    {
        $id = Database::value('SELECT SaleID FROM Sales WHERE InvoiceNo = ?', [$invoice]);
        if ($id === false || $id === null) {
            return null;
        }
        return (int) $id;
    }

    public static function invoice(int $saleId): ?array
    {
        $sale = Database::one(
            'SELECT s.SaleID, s.InvoiceNo, s.SaleDate, c.CustomerName, s.SubTotal, s.Discount,
                    s.Tax, s.GrandTotal, s.PaymentMethod, s.PaymentStatus
             FROM Sales s
             INNER JOIN Customers c ON s.CustomerID = c.CustomerID
             WHERE s.SaleID = ?',
            [$saleId]
        );
        if ($sale === null) {
            return null;
        }
        $sale['items'] = Database::all(
            'SELECT ProductName, Quantity, UnitPrice, TotalAmount FROM SaleItems WHERE SaleID = ?',
            [$saleId]
        );
        return $sale;
    }

    public static function dashboard(): array
    {
        $start = date('Y-m-d') . ' 00:00:00';
        $end = date('Y-m-d', strtotime('+1 day')) . ' 00:00:00';
        $sales = (int) Database::value(
            'SELECT COUNT(*) FROM Sales WHERE SaleDate >= ? AND SaleDate < ?',
            [$start, $end]
        );
        $revenue = (float) Database::value(
            'SELECT COALESCE(SUM(AmountPaid), 0) FROM SalesPayment WHERE PaymentDate >= ? AND PaymentDate < ?',
            [$start, $end]
        );
        $low = (int) Database::value(
            'SELECT COUNT(*) FROM Production WHERE Quantity <= ?',
            [Catalog::DASHBOARD_LOW_STOCK]
        );
        return [
            'sales' => $sales,
            'orders' => $sales,
            'revenue' => $revenue,
            'low_stock' => $low,
            'recent' => self::recent(10),
        ];
    }
}
