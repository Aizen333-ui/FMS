<?php
declare(strict_types=1);

namespace Fms;

/** Fixed lists and thresholds taken from the desktop application. */
final class Catalog
{
    public const STAFF_ROLES = ['Owner', 'Manager', 'Sales Person'];
    public const WORKER_ROLES = ['Labor', 'Driver', 'Loader', 'Machine Operator'];
    public const MATERIALS = ['Cement', 'Sand', 'Crush', 'Steel', 'Mold Oil'];
    public const PAYMENT_REASONS = [
        'Cement',
        'Sand',
        'Crush',
        'Steel',
        'Mold Oil',
        'Worker Salary',
        'Diesel Expense',
        'Machine Maintenance',
        'Factory Rent',
        'Other Expense',
    ];
    public const PAYMENT_METHODS = ['Cash', 'Card', 'Bank Transfer', 'Credit'];
    public const PAYMENT_STATUSES = ['Paid', 'Pending', 'Partial'];
    public const RETURN_REASONS = ['Damaged', 'Wrong Product', 'Customer Request', 'Other'];
    public const REFUND_METHODS = ['Cash', 'Bank Transfer', 'Credit Adjustment'];
    public const REPORT_TYPES = ['Material Usage', 'Production', 'Sale', 'Return'];

    /** Sales product browser: low stock is above zero and at most this quantity. */
    public const PRODUCT_LOW_STOCK = 5000;

    /** Sales dashboard card counts production rows at or below this quantity. */
    public const DASHBOARD_LOW_STOCK = 500;

    public const MAX_PRODUCTION_QTY = 10000;

    public static function materialUnit(string $name): string
    {
        return match ($name) {
            'Cement' => 'Bag',
            'Sand' => 'Ton',
            'Crush' => 'Ton',
            'Steel' => 'KG',
            'Mold Oil' => 'Liters',
            default => '',
        };
    }

    public static function usageUnit(string $name): string
    {
        return match ($name) {
            'Cement' => 'Bag',
            'Sand' => 'Ton',
            'Crush' => 'Ton',
            'Steel' => 'Kg',
            'Mold Oil' => 'Litre',
            default => self::materialUnit($name),
        };
    }
}
