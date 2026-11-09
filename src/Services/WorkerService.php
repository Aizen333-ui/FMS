<?php
declare(strict_types=1);

namespace Fms\Services;

use Fms\Audit;
use Fms\Auth;
use Fms\Database;
use Fms\ValidationException;

final class WorkerService
{
    public static function list(): array
    {
        return Database::all('SELECT WorkerID, Name, Role, Salary FROM Workers ORDER BY WorkerID DESC');
    }

    public static function names(): array
    {
        return Database::all('SELECT Name FROM Workers ORDER BY Name');
    }

    public static function add(string $name, string $role, string $salaryText): void
    {
        $name = trim($name);
        $role = trim($role);
        if (!preg_match('/^[A-Za-z ]{3,30}$/', $name)) {
            throw new ValidationException('Worker name must be alphabets only (3–30 chars).');
        }
        if (!preg_match('/^.{1,50}$/us', $role)) {
            throw new ValidationException('Worker role is required and must be 50 characters or fewer.');
        }
        if (!is_numeric($salaryText)) {
            throw new ValidationException('Salary can only be entered in numbers.');
        }
        $salary = round((float) $salaryText, 2);
        if ($salary < 0) {
            throw new ValidationException('Salary can only be entered in numbers.');
        }
        try {
            Database::execute(
                'INSERT INTO Workers (Name, Role, Salary) VALUES (?, ?, ?)',
                [$name, $role, $salary]
            );
        } catch (\Throwable $e) {
            Audit::add(Auth::username(), 'CREATE', 'Workers', "Failed to add worker '{$name}'. Error: {$e->getMessage()}", 'Failed');
            throw new ValidationException('Error adding worker: ' . $e->getMessage());
        }
        Audit::add(
            Auth::username(),
            'CREATE',
            'Workers',
            "Added worker '{$name}' with role '{$role}' and salary Rs {$salary}",
            'Success'
        );
    }

    public static function update(int $id, string $name, string $role, string $salaryText): void
    {
        $name = trim($name);
        $role = trim($role);
        if ($id <= 0) {
            throw new ValidationException('Select a worker to update.');
        }
        $current = Database::one(
            'SELECT Name, Role, Salary FROM Workers WHERE WorkerID = ?',
            [$id]
        );
        if ($current === null) {
            throw new ValidationException('Select a worker to update.');
        }
        if (!preg_match('/^[A-Za-z ]{3,30}$/', $name)) {
            throw new ValidationException('Worker name must be alphabets only (3–30 chars).');
        }
        if (!preg_match('/^.{1,50}$/us', $role)) {
            throw new ValidationException('Worker role is required and must be 50 characters or fewer.');
        }
        if (!is_numeric($salaryText)) {
            throw new ValidationException('Salary can only be entered in numbers.');
        }
        $salary = round((float) $salaryText, 2);
        if ($salary < 0) {
            throw new ValidationException('Salary can only be entered in numbers.');
        }
        if ($name === (string) $current['Name']
            && $role === (string) $current['Role']
            && $salary === round((float) $current['Salary'], 2)) {
            throw new ValidationException('No changes were made.');
        }
        try {
            Database::execute(
                'UPDATE Workers SET Name = ?, Role = ?, Salary = ? WHERE WorkerID = ?',
                [$name, $role, $salary, $id]
            );
        } catch (\Throwable $e) {
            Audit::add(Auth::username(), 'UPDATE', 'Workers', "Failed to update worker with ID {$id}. Error: {$e->getMessage()}", 'Failed');
            throw new ValidationException('Error updating worker: ' . $e->getMessage());
        }
        Audit::add(
            Auth::username(),
            'UPDATE',
            'Workers',
            "Updated worker '{$name}' (ID={$id}) with role '{$role}' and salary Rs {$salary}",
            'Success'
        );
    }

    public static function remove(int $id): void
    {
        $exists = Database::one('SELECT WorkerID FROM Workers WHERE WorkerID = ?', [$id]);
        if ($exists === null) {
            throw new ValidationException('Select a worker to remove.');
        }
        try {
            Database::execute('DELETE FROM Workers WHERE WorkerID = ?', [$id]);
        } catch (\Throwable $e) {
            Audit::add(Auth::username(), 'DELETE', 'Workers', "Failed to remove worker with ID {$id}. Error: {$e->getMessage()}", 'Failed');
            throw new ValidationException('Error removing worker: ' . $e->getMessage());
        }
        Audit::add(Auth::username(), 'DELETE', 'Workers', "Removed worker with ID {$id}", 'Success');
    }

    public static function countsByRole(): array
    {
        $counts = [];
        foreach (Database::all('SELECT Role, COUNT(*) AS WorkerCount FROM Workers GROUP BY Role ORDER BY Role') as $row) {
            $counts[(string) $row['Role']] = (int) $row['WorkerCount'];
        }
        return $counts;
    }
}
