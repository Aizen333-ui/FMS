<?php use Fms\Catalog; use Fms\Http; ?>
<section class="panel">
    <form method="get" class="form-grid">
        <label>Invoice or customer
            <input name="q" value="<?= Http::e($search) ?>">
        </label>
        <label>Payment status
            <select name="status">
                <option value="All Statuses">All Statuses</option>
                <?php foreach (Catalog::PAYMENT_STATUSES as $item): ?>
                    <option value="<?= Http::e($item) ?>"<?= $status === $item ? ' selected' : '' ?>><?= Http::e($item) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit">Search</button>
        <a class="button secondary" href="<?= Http::e(Http::url('/sales/history')) ?>">Clear</a>
    </form>
    <div class="table-wrap">
        <table>
            <caption>Sales history</caption>
            <thead><tr><th>Invoice</th><th>Customer</th><th>Date</th><th>Subtotal</th><th>Discount</th><th>Tax</th><th>Grand total</th><th>Method</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><a href="<?= Http::e(Http::url('/sales/invoice?id=' . (int) $row['SaleID'])) ?>"><?= Http::e($row['InvoiceNo']) ?></a></td>
                    <td><?= Http::e($row['CustomerName']) ?></td>
                    <td><?= Http::e($row['SaleDate']) ?></td>
                    <td><?= Http::e(Http::money($row['SubTotal'])) ?></td>
                    <td><?= Http::e(Http::money($row['Discount'])) ?></td>
                    <td><?= Http::e(Http::money($row['Tax'])) ?></td>
                    <td><?= Http::e(Http::money($row['GrandTotal'])) ?></td>
                    <td><?= Http::e($row['PaymentMethod']) ?></td>
                    <td><?= Http::e($row['PaymentStatus']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($rows === []): ?><tr><td colspan="9">No sales match.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
