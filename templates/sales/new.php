<?php
use Fms\Catalog;
use Fms\Http;
$customerId = (int) ($cart['customer_id'] ?? 0);
?>
<div class="grid-2">
    <section class="panel">
        <h2>Available stock</h2>
        <ul>
            <?php foreach ($products as $product): ?>
                <li><?= Http::e($product['ProductName']) ?>: <?= Http::e(Http::qty($product['Quantity'])) ?></li>
            <?php endforeach; ?>
            <?php if ($products === []): ?><li>No finished goods in stock.</li><?php endif; ?>
        </ul>
        <form method="post" class="stack">
            <?= Http::csrfField() ?>
            <input type="hidden" name="action" value="add">
            <label>Customer
                <select name="customer_id" required>
                    <option value="">Select customer...</option>
                    <?php foreach ($customers as $customer): ?>
                        <option value="<?= Http::e($customer['CustomerID']) ?>"<?= $customerId === (int) $customer['CustomerID'] ? ' selected' : '' ?>><?= Http::e($customer['CustomerName']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Product
                <select name="product" required>
                    <option value="">Select product...</option>
                    <?php foreach ($catalog as $product): ?>
                        <option value="<?= Http::e($product['Name']) ?>"><?= Http::e($product['Name']) ?> (<?= Http::e($product['Unit']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Quantity
                <input name="quantity" inputmode="numeric" required>
            </label>
            <label>Unit price
                <input name="unit_price" inputmode="decimal" required>
            </label>
            <button type="submit">Add to cart</button>
        </form>
    </section>
    <section class="panel" data-cart data-subtotal="<?= Http::e((string) $totals['subtotal']) ?>">
        <h2>Cart</h2>
        <div class="table-wrap">
            <table>
                <caption>Items in this sale</caption>
                <thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Amount</th></tr></thead>
                <tbody>
                <?php foreach ($cart['lines'] as $index => $line): ?>
                    <tr>
                        <td><?= Http::e($line['product']) ?></td>
                        <td><?= Http::e((string) $line['quantity']) ?></td>
                        <td><?= Http::e(Http::money($line['unit_price'])) ?></td>
                        <td><?= Http::e(Http::money($line['quantity'] * $line['unit_price'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($cart['lines'] === []): ?><tr><td colspan="4">Cart is empty.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <p>Subtotal <strong>Rs <span><?= Http::e(number_format((float) $totals['subtotal'], 2)) ?></span></strong></p>
        <p>Tax <strong>Rs <span data-tax-amount>0.00</span></strong></p>
        <p>Grand total <strong>Rs <span data-grand><?= Http::e(number_format((float) $totals['grand'], 2)) ?></span></strong></p>
        <form method="post" class="form-grid">
            <?= Http::csrfField() ?>
            <input type="hidden" name="action" value="remove">
            <label>Cart line
                <select name="line">
                    <?php foreach ($cart['lines'] as $index => $line): ?>
                        <option value="<?= Http::e((string) $index) ?>"><?= Http::e($line['product']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Quantity to remove
                <input name="quantity" inputmode="numeric" placeholder="Blank removes the line">
            </label>
            <button class="secondary" type="submit">Remove</button>
        </form>
        <form method="post" class="stack" style="margin-top:1rem">
            <?= Http::csrfField() ?>
            <input type="hidden" name="action" value="complete">
            <div class="form-grid">
                <label>Discount amount
                    <input name="discount" inputmode="decimal" value="0">
                </label>
                <label>Tax percent
                    <input name="tax" inputmode="decimal" value="0">
                </label>
                <label>Payment method
                    <select name="method" required>
                        <option value="">Select payment method...</option>
                        <?php foreach (Catalog::PAYMENT_METHODS as $method): ?>
                            <option value="<?= Http::e($method) ?>"><?= Http::e($method) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Payment status
                    <select name="status" required>
                        <option value="">Select payment status...</option>
                        <?php foreach (Catalog::PAYMENT_STATUSES as $status): ?>
                            <option value="<?= Http::e($status) ?>"><?= Http::e($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <div class="actions">
                <button type="submit">Complete sale</button>
            </div>
        </form>
        <form method="post">
            <?= Http::csrfField() ?>
            <input type="hidden" name="action" value="clear">
            <button class="secondary" type="submit">Clear</button>
        </form>
    </section>
</div>
<section class="panel" style="margin-top:1rem">
    <div class="table-wrap">
        <table>
            <caption>Latest completed sales</caption>
            <thead><tr><th>Invoice</th><th>Customer</th><th>Date</th><th>Total</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($completed as $row): ?>
                <tr>
                    <td><a href="<?= Http::e(Http::url('/sales/invoice?invoice=' . urlencode((string) $row['InvoiceNo']))) ?>"><?= Http::e($row['InvoiceNo']) ?></a></td>
                    <td><?= Http::e($row['CustomerName']) ?></td>
                    <td><?= Http::e($row['SaleDate']) ?></td>
                    <td><?= Http::e(Http::money($row['GrandTotal'])) ?></td>
                    <td><?= Http::e($row['PaymentStatus']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($completed === []): ?><tr><td colspan="5">No completed sales.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
