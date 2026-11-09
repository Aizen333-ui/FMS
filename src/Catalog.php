<?php
declare(strict_types=1);

namespace Fms;

/** Fixed lists and thresholds taken from the desktop application. */
final class Catalog
{
    public const STAFF_ROLES = ['Owner', 'Manager', 'Sales Person'];
    public const PAYMENT_REASONS = [
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
    public const REPORT_TYPES = ['Material Usage', 'Production', 'Sale', 'Return', 'Payment'];

    /** Sales product browser: low stock is above zero and at most this quantity. */
    public const PRODUCT_LOW_STOCK = 5000;

    /** Sales dashboard card counts production rows at or below this quantity. */
    public const DASHBOARD_LOW_STOCK = 500;

    public const MAX_PRODUCTION_QTY = 10000;

}
