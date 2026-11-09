<?php
declare(strict_types=1);

namespace Fms\Services;

use Fms\Audit;
use Fms\Auth;
use Fms\Catalog;
use Fms\Database;
use Fms\Http;
use Fms\ValidationException;

final class PaymentService
{
    public static function list(): array
    {
        return Database::all(
            'SELECT PaymentID, Amount, Reason, Date FROM Payments ORDER BY PaymentID DESC'
        );
    }

    public static function add(string $reason, string $workerName, string $amountText, string $day): void
    {
        MaterialService::ensureCatalog();
        $material = Database::one('SELECT Name FROM MaterialCatalog WHERE Name = ?', [$reason]);
        if (!in_array($reason, Catalog::PAYMENT_REASONS, true) && $material === null) {
            throw new ValidationException('Please select a reason.');
        }
        if (!is_numeric($amountText) || (float) $amountText <= 0) {
            throw new ValidationException('Enter valid amount.');
        }
        $amount = round((float) $amountText, 2);
        $when = Http::dateTimeFromDay($day);
        if ($reason === 'Worker Salary') {
            $workerName = trim($workerName);
            if ($workerName === '' || $workerName === 'Select a Worker...') {
                throw new ValidationException('Please select worker name.');
            }
            $worker = Database::one('SELECT Name FROM Workers WHERE Name = ?', [$workerName]);
            if ($worker === null) {
                throw new ValidationException('Please select worker name.');
            }
            $reason = 'Worker Salary - ' . $workerName;
        }
        try {
            Database::execute(
                'INSERT INTO Payments (Amount, Reason, Date) VALUES (?, ?, ?)',
                [$amount, $reason, $when]
            );
        } catch (\Throwable $e) {
            Audit::add(Auth::username(), 'CREATE', 'Payments', 'Failed to add payment: ' . $e->getMessage(), 'Failed');
            throw new ValidationException('Error adding: ' . $e->getMessage());
        }
        Audit::add(Auth::username(), 'CREATE', 'Payments', "Added payment of Rs {$amount} for '{$reason}'", 'Success');
    }

    public static function adjust(int $paymentId, string $selectedReason, string $amountText): string
    {
        MaterialService::ensureCatalog();
        $payment = Database::one(
            'SELECT PaymentID, Amount, Reason FROM Payments WHERE PaymentID = ?',
            [$paymentId]
        );
        if ($payment === null) {
            throw new ValidationException('Select a payment row first!');
        }
        $gridReason = trim((string) $payment['Reason']);
        $salaryReasonMatches = $selectedReason === 'Worker Salary'
            && str_starts_with($gridReason, 'Worker Salary - ');
        if ($selectedReason !== '' && $gridReason !== $selectedReason && !$salaryReasonMatches) {
            throw new ValidationException('Selected reason does not match the selected payment.');
        }
        $current = round((float) $payment['Amount'], 2);
        if ($amountText === '') {
            Database::execute('DELETE FROM Payments WHERE PaymentID = ?', [$paymentId]);
            Audit::add(
                Auth::username(),
                'DELETE',
                'Payments',
                "Deleted payment ID '{$paymentId}' amount Rs {$current}",
                'Success'
            );
            return 'Payment deleted!';
        }
        if (!is_numeric($amountText)) {
            throw new ValidationException('Enter a valid amount.');
        }
        $remove = round((float) $amountText, 2);
        if ($remove <= 0) {
            throw new ValidationException('Amount must be greater than zero.');
        }
        if ($remove > $current + 0.00001) {
            throw new ValidationException('Cannot remove more than the payment amount.');
        }
        if (abs($remove - $current) < 0.001) {
            Database::execute('DELETE FROM Payments WHERE PaymentID = ?', [$paymentId]);
            Audit::add(
                Auth::username(),
                'DELETE',
                'Payments',
                "Deleted full payment ID '{$paymentId}' amount Rs {$current}",
                'Success'
            );
        } else {
            Database::execute(
                'UPDATE Payments SET Amount = Amount - ? WHERE PaymentID = ?',
                [$remove, $paymentId]
            );
            Audit::add(
                Auth::username(),
                'UPDATE',
                'Payments',
                "Updated payment ID '{$paymentId}' amount Rs {$remove}",
                'Success'
            );
        }
        return 'Payment updated!';
    }

    public static function total(): float
    {
        return (float) Database::value('SELECT COALESCE(SUM(Amount), 0) FROM Payments');
    }
}
