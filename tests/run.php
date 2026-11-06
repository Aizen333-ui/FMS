<?php
declare(strict_types=1);

use Fms\Auth;
use Fms\Catalog;
use Fms\Database;
use Fms\Http;
use Fms\Router;
use Fms\Schema;
use Fms\Services\AdminService;
use Fms\Services\BackupService;
use Fms\Services\CustomerService;
use Fms\Services\MaterialService;
use Fms\Services\PaymentService;
use Fms\Services\ProductService;
use Fms\Services\ProductionService;
use Fms\Services\ReportService;
use Fms\Services\ReturnService;
use Fms\Services\SalesService;
use Fms\Services\UserService;
use Fms\Services\WorkerService;
use Fms\ValidationException;

$database = sys_get_temp_dir() . '/fms-test-' . getmypid() . '.sqlite';
@unlink($database);
putenv('FMS_TESTING=1');
putenv('FMS_CONNECTION_STRING=sqlite:' . $database);
require dirname(__DIR__) . '/src/bootstrap.php';
require dirname(__DIR__) . '/src/routes.php';

$passed = 0;
$failed = 0;
ob_start();

function check(bool $condition, string $message): void
{
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "PASS {$message}\n";
        return;
    }
    $failed++;
    echo "FAIL {$message}\n";
}

function expect(callable $action, string $fragment, string $message): void
{
    try {
        $action();
        check(false, $message . ' (no exception)');
    } catch (ValidationException $e) {
        check(str_contains($e->getMessage(), $fragment), $message . ' [' . $e->getMessage() . ']');
    }
}

$config = require dirname(__DIR__) . '/config/database.php';
check($config['connection_string'] === '', 'committed connection string stays empty');

Schema::apply(Database::pdo());
check(Database::driver() === 'sqlite', 'test database is sqlite');

expect(fn () => Auth::attemptStaff('', ''), 'Please enter username and password', 'blank staff login');
expect(fn () => AdminService::createFirst('root', 'secret', 'other'), 'do not match', 'admin password confirmation');
AdminService::createFirst('root', 'secret', 'secret');
check(AdminService::count() === 1, 'first administrator is created');
expect(fn () => AdminService::delete(1), 'last admin', 'last administrator cannot be deleted');
AdminService::add('backup-admin', 'secret', 'secret');
$second = (int) Database::value('SELECT AdminID FROM SystemAdmins WHERE Username = ?', ['backup-admin']);
AdminService::delete($second);
check(AdminService::count() === 1, 'extra administrator can be deleted');

Auth::attemptAdmin('root', 'secret');
check(Auth::role() === 'Administrator', 'administrator login');
UserService::add('Owner One', 'owner', 'owner-pass', 'Owner');
UserService::add('Factory Lead', 'manager', 'manager-pass', 'Manager');
UserService::add('Sales One', 'seller', 'seller-pass', 'Sales Person');
UserService::add('Old Account', 'legacy', 'legacy-pass', 'Sales Person');
Database::execute('UPDATE Users SET Password = ?, PasswordHash = NULL WHERE Username = ?', ['plain-pass', 'legacy']);
Auth::logout();
Auth::attemptStaff('legacy', 'plain-pass');
check(Auth::role() === 'Sales Person', 'legacy plaintext password still signs in');
Auth::logout();
Auth::attemptStaff('owner', 'owner-pass');
check(Auth::home() === '/owner', 'owner home route');
$seller = (int) Database::value('SELECT UserID FROM Users WHERE Username = ?', ['seller']);
UserService::toggle($seller);
Auth::logout();
expect(fn () => Auth::attemptStaff('seller', 'seller-pass'), 'disabled', 'disabled account cannot sign in');
UserService::toggle($seller);
Auth::attemptStaff('seller', 'seller-pass');

expect(fn () => CustomerService::add('  ', '', ''), 'customer name', 'customer name is required');
CustomerService::add('Amina Khan', '03001234567', 'Lahore');
$id = (int) Database::value('SELECT CustomerID FROM Customers WHERE CustomerName = ?', ['Amina Khan']);
expect(fn () => CustomerService::update($id, 'Amina Khan', '03001234567', 'Lahore'), 'No changes', 'unchanged customer is rejected');
CustomerService::update($id, 'Amina Khan', '03007654321', 'Lahore');
check(count(CustomerService::list('030076')) === 1, 'customer search finds the new phone');

expect(fn () => WorkerService::add('Al', 'Labor', '100'), 'alphabets', 'short worker name is rejected');
expect(fn () => WorkerService::add('Ali1', 'Labor', '100'), 'alphabets', 'worker name cannot contain digits');
WorkerService::add('Ali Raza', 'Labor', '25000');
check(WorkerService::countsByRole()['Labor'] === 1, 'labor count');

check(ProductService::list() === [], 'products start empty, like workers');
expect(fn () => ProductionService::record('Tuff Tile', '1', date('Y-m-d')), 'select a product', 'production requires a product the owner added');
ProductService::add('Tuff Tile', 'Sqft');
ProductService::add('Solid Block', 'Piece');
ProductService::add('Hollow Block', 'Piece');
expect(fn () => ProductionService::record('Tuff Tile', '10001', date('Y-m-d')), '10000', 'production above 10000 is rejected');
expect(fn () => ProductionService::record('Tuff Tile', '0', date('Y-m-d')), '10000', 'zero production is rejected');
expect(fn () => ProductionService::record('Tuff Tile', '5', '2999-01-01'), 'Future', 'future production date is rejected');
ProductionService::record('Tuff Tile', '4', date('Y-m-d'));
ProductionService::record('Tuff Tile', '3', date('Y-m-d'));
ProductionService::record('Solid Block', '500', date('Y-m-d'));
ProductionService::record('Hollow Block', '501', date('Y-m-d'));
$dash = SalesService::dashboard();
check($dash['low_stock'] === 3, 'dashboard low stock uses 500');
$low = ProductionService::search('', 'Low Stock');
check(count($low) === 4, 'product low stock uses 5000 and excludes zero');

MaterialService::add('Cement', '10', '50');
MaterialService::add('Cement', '10', '60');
$payments = PaymentService::list();
check(count($payments) === 2, 'material receipt creates an expense');
check(abs((float) $payments[1]['Amount'] - 500.0) < 0.001 || abs((float) $payments[0]['Amount'] - 500.0) < 0.001, 'expense equals quantity times price');
MaterialService::remove(null, 'Cement', '10');
$cement = MaterialService::list();
check(count($cement) === 1 && abs((float) $cement[0]['Quantity'] - 10) < 0.001, 'named removal changes only the oldest line');
MaterialService::recordUsage(null, 'Cement', '6', date('Y-m-d'));
$left = (float) Database::value('SELECT Quantity FROM RawMaterial WHERE Name = ?', ['Cement']);
check(abs($left - 4) < 0.001, 'usage spreads across stock lines');
expect(fn () => MaterialService::recordUsage(null, 'Cement', '100', date('Y-m-d')), 'Not enough', 'usage cannot exceed stock');

PaymentService::add('Factory Rent', '', '100', date('Y-m-d'));
$rent = Database::one("SELECT PaymentID FROM Payments WHERE Reason = 'Factory Rent'");
expect(fn () => PaymentService::adjust((int) $rent['PaymentID'], 'Diesel Expense', ''), 'does not match', 'payment reason must match');
PaymentService::adjust((int) $rent['PaymentID'], 'Factory Rent', '40');
$remaining = (float) Database::value('SELECT Amount FROM Payments WHERE PaymentID = ?', [(int) $rent['PaymentID']]);
check(abs($remaining - 60) < 0.001, 'partial payment reduction');
PaymentService::adjust((int) $rent['PaymentID'], '', '60');
check(Database::one('SELECT PaymentID FROM Payments WHERE PaymentID = ?', [(int) $rent['PaymentID']]) === null, 'removing the full remainder deletes the payment');

$totals = SalesService::totals([
    ['quantity' => 2, 'unit_price' => 50],
], 10, 5);
check($totals['subtotal'] === 100.0 && $totals['tax'] === 5.0 && $totals['grand'] === 95.0, 'grand total is subtotal minus discount plus tax');
expect(fn () => SalesService::totals([['quantity' => 1, 'unit_price' => 100]], 200, 0), 'negative', 'discount cannot exceed the sale');

$before = ProductionService::available('Tuff Tile');
expect(fn () => SalesService::complete($id, [['product' => 'Tuff Tile', 'quantity' => 99, 'unit_price' => 10]], 0, 0, 'Cash', 'Paid'), 'Not enough', 'sale rolls back when stock is short');
check(abs(ProductionService::available('Tuff Tile') - $before) < 0.001, 'failed sale does not change stock');

$saleId = SalesService::complete(
    $id,
    [['product' => 'Tuff Tile', 'quantity' => 5, 'unit_price' => 20]],
    10,
    5,
    'Cash',
    'Partial'
);
check(abs(ProductionService::available('Tuff Tile') - 2) < 0.001, 'sale consumes stock across batches');
$items = Database::all('SELECT ProductionID, Quantity FROM SaleItems WHERE SaleID = ? ORDER BY SaleItemID', [$saleId]);
check(count($items) === 2 && (float) $items[0]['Quantity'] === 4.0 && (float) $items[1]['Quantity'] === 1.0, 'sale lines follow oldest production first');
$paid = (float) Database::value('SELECT AmountPaid FROM SalesPayment WHERE SaleID = ?', [$saleId]);
check(abs($paid - 95.0) < 0.001, 'sales payment stores the grand total');

$firstStock = (float) Database::value('SELECT Quantity FROM Production WHERE ProductionID = ?', [(int) $items[0]['ProductionID']]);
ReturnService::process((int) Database::value('SELECT SaleItemID FROM SaleItems WHERE SaleID = ? ORDER BY SaleItemID LIMIT 1', [$saleId]), '4', 'Damaged', 'Cash', 'cracked');
$afterDamage = (float) Database::value('SELECT Quantity FROM Production WHERE ProductionID = ?', [(int) $items[0]['ProductionID']]);
check(abs($afterDamage - $firstStock) < 0.001, 'damaged returns stay out of stock');
expect(fn () => ReturnService::process((int) Database::value('SELECT SaleItemID FROM SaleItems WHERE SaleID = ? ORDER BY SaleItemID LIMIT 1', [$saleId]), '1', 'Wrong Product', 'Cash', ''), 'exceed', 'a line cannot be returned twice');
$secondItem = (int) Database::value('SELECT SaleItemID FROM SaleItems WHERE SaleID = ? ORDER BY SaleItemID DESC LIMIT 1', [$saleId]);
$secondProduction = (int) $items[1]['ProductionID'];
$secondBefore = (float) Database::value('SELECT Quantity FROM Production WHERE ProductionID = ?', [$secondProduction]);
ReturnService::process($secondItem, '1', 'Customer Request', 'Bank Transfer', '');
$secondAfter = (float) Database::value('SELECT Quantity FROM Production WHERE ProductionID = ?', [$secondProduction]);
check(abs($secondAfter - ($secondBefore + 1)) < 0.001, 'good returns restore the original batch');

$midday = date('Y-m-d') . ' 15:00:00';
Database::execute('UPDATE Production SET Date = ? WHERE ProductionID = ?', [$midday, (int) $items[0]['ProductionID']]);
$report = ReportService::owner(date('Y-m-d'), date('Y-m-d'), 'Production');
check($report !== [], 'report range includes records later on the end date');

$sql = BackupService::sql();
check(str_contains($sql, 'INSERT INTO Customers') && str_contains($sql, 'Amina Khan'), 'backup exports customer rows');

$_SESSION['user'] = ['kind' => 'staff', 'id' => 1, 'username' => 'seller', 'name' => 'Sales One', 'role' => 'Sales Person'];
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/owner';
$_SERVER['SCRIPT_NAME'] = '/index.php';
ob_start();
$router = new Router();
register_routes($router);
$router->dispatch('GET', '/owner');
$page = ob_get_clean();
check(str_contains($page, 'Not allowed'), 'sales role cannot open the owner dashboard');
check(http_response_code() === 403, 'forbidden status is 403');

echo "\n{$passed} passed, {$failed} failed\n";
$report = ob_get_clean();
echo $report;
@unlink($database);
exit($failed === 0 ? 0 : 1);
