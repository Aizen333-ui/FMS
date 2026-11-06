<?php
declare(strict_types=1);

namespace Fms\Services;

use Fms\Audit;
use Fms\Auth;
use Fms\Catalog;
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
        if (!preg_match('/^[A-Za-z ]{3,30}$/', $name)) {
            throw new ValidationException('Worker name must be alphabets only (3–30 chars).');
        }
        if (!in_array($role, Catalog::WORKER_ROLES, true)) {
            throw new ValidationException('Please select a worker role.');
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
        foreach (Catalog::WORKER_ROLES as $role) {
            $counts[$role] = (int) Database::value(
                'SELECT COUNT(*) FROM Workers WHERE Role = ?',
                [$role]
            );
        }
        return $counts;
    }
}
