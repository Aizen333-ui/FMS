<?php
declare(strict_types=1);

namespace Fms\Controllers;

use Fms\Audit;
use Fms\Auth;
use Fms\Guard;
use Fms\Http;
use Fms\Services\CustomerService;
use Fms\Services\ProductService;
use Fms\Services\ProductionService;
use Fms\Services\ReturnService;
use Fms\Services\SalesService;
use Fms\ValidationException;

final class SalesController
{
    public static function dashboard(): void
    {
        Guard::database();
        Http::render('sales/dashboard', [
            'title' => 'Sales dashboard',
            'data' => SalesService::dashboard(),
        ]);
    }

    public static function sale(): void
    {
        Guard::database();
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $action = Http::post('action');
            Guard::handle(function () use ($action): string {
                if ($action === 'add') {
                    SalesService::addToCart(
                        (int) Http::post('customer_id'),
                        Http::post('product'),
                        Http::post('quantity'),
                        Http::post('unit_price')
                    );
                    return 'Product added to the cart.';
                }
                if ($action === 'remove') {
                    SalesService::removeFromCart((int) Http::post('line'), Http::post('quantity'));
                    return 'Cart updated.';
                }
                if ($action === 'clear') {
                    SalesService::clearCart();
                    return 'Sale form cleared.';
                }
                if ($action === 'complete') {
                    $cart = SalesService::cart();
                    $saleId = SalesService::complete(
                        (int) ($cart['customer_id'] ?? 0),
                        $cart['lines'] ?? [],
                        is_numeric(Http::post('discount')) ? (float) Http::post('discount') : 0.0,
                        is_numeric(Http::post('tax')) ? (float) Http::post('tax') : 0.0,
                        Http::post('method'),
                        Http::post('status')
                    );
                    Http::flash('success', 'Sale completed successfully.');
                    Http::clearOld();
                    Http::redirect('/sales/invoice?id=' . $saleId);
                }
                throw new ValidationException('Unknown action.');
            }, '/sales/new');
        }
        $cart = SalesService::cart();
        $totals = ['subtotal' => 0, 'discount' => 0, 'tax' => 0, 'grand' => 0];
        try {
            $totals = SalesService::totals($cart['lines'], 0, 0);
        } catch (ValidationException) {
        }
        Http::render('sales/new', [
            'title' => 'New sale',
            'customers' => CustomerService::options(),
            'products' => SalesService::availableProducts(),
            'catalog' => ProductService::list(),
            'cart' => $cart,
            'completed' => SalesService::recent(20),
            'totals' => $totals,
        ]);
    }

    public static function products(): void
    {
        Guard::database();
        $search = Http::query('q');
        $filter = Http::query('filter', 'All Products');
        $rows = [];
        try {
            $rows = ProductionService::search($search, $filter);
        } catch (ValidationException $e) {
            Http::flash('error', $e->getMessage());
        }
        Http::render('sales/products', [
            'title' => 'Products',
            'rows' => $rows,
            'search' => $search,
            'filter' => $filter,
        ]);
    }

    public static function customers(): void
    {
        Guard::database();
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Guard::handle(function (): string {
                $action = Http::post('action');
                if ($action === 'add') {
                    CustomerService::add(Http::post('name'), Http::post('phone'), Http::post('address'));
                    return 'Customer added successfully.';
                }
                if ($action === 'edit') {
                    CustomerService::update(
                        (int) Http::post('customer_id'),
                        Http::post('name'),
                        Http::post('phone'),
                        Http::post('address')
                    );
                    return 'Customer updated successfully.';
                }
                throw new ValidationException('Unknown action.');
            }, '/sales/customers');
        }
        Http::render('sales/customers', [
            'title' => 'Customers',
            'rows' => CustomerService::list(Http::query('q')),
            'search' => Http::query('q'),
        ]);
    }

    public static function history(): void
    {
        Guard::database();
        $search = Http::query('q');
        $status = Http::query('status', 'All Statuses');
        $rows = [];
        try {
            $rows = SalesService::history($search, $status);
        } catch (ValidationException $e) {
            Http::flash('error', $e->getMessage());
        }
        Http::render('sales/history', [
            'title' => 'Sales history',
            'rows' => $rows,
            'search' => $search,
            'status' => $status,
        ]);
    }

    public static function returns(): void
    {
        Guard::database();
        $invoice = null;
        $message = null;
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $action = Http::post('action');
            if ($action === 'process') {
                Guard::handle(function (): string {
                    ReturnService::process(
                        (int) Http::post('sale_item_id'),
                        Http::post('quantity'),
                        Http::post('reason'),
                        Http::post('method'),
                        Http::post('notes')
                    );
                    return 'Return processed successfully.';
                }, '/sales/returns?invoice=' . rawurlencode(Http::post('invoice')));
            }
        }
        $number = Http::query('invoice', Http::post('invoice'));
        if ($number !== '') {
            try {
                $invoice = ReturnService::findInvoice($number);
                if ($invoice === null) {
                    $message = 'Invoice not found.';
                }
            } catch (ValidationException $e) {
                $message = $e->getMessage();
            }
        }
        Http::render('sales/returns', [
            'title' => 'Returns',
            'invoice' => $invoice,
            'number' => $number,
            'lookupMessage' => $message,
            'history' => ReturnService::history(),
        ]);
    }

    public static function invoice(): void
    {
        Guard::database();
        $id = (int) Http::query('id');
        if ($id <= 0 && Http::query('invoice') !== '') {
            $id = SalesService::findIdByInvoice(Http::query('invoice')) ?? 0;
        }
        if ($id <= 0) {
            $id = (int) ($_SESSION['last_sale_id'] ?? 0);
        }
        $sale = $id > 0 ? SalesService::invoice($id) : null;
        if ($sale === null) {
            Http::flash('error', 'Complete a sale or select an invoice first.');
            Http::redirect('/sales/new');
        }
        Audit::add(Auth::username() ?? 'system', 'View Invoice', 'Invoice Report', 'Viewed invoice ' . $sale['InvoiceNo'], 'Success');
        Http::render('sales/invoice', [
            'title' => 'Invoice ' . $sale['InvoiceNo'],
            'sale' => $sale,
        ], 'print');
    }
}
