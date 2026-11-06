<?php use Fms\Http; ?>
<section class="cards">
    <article class="card"><span>Today's sales</span><strong><?= Http::e((string) $data['sales']) ?></strong></article>
    <article class="card"><span>Today's revenue</span><strong><?= Http::e(Http::money($data['revenue'])) ?></strong></article>
    <article class="card"><span>Orders today</span><strong><?= Http::e((string) $data['orders']) ?></strong></article>
    <article class="card"><span>Low stock rows</span><strong><?= Http::e((string) $data['low_stock']) ?></strong></article>
</section>
<p class="note">Low stock counts production rows whose quantity is 500 or less.</p>
<section class="panel">
    <div class="table-wrap">
        <table>
            <caption>Recent sales</caption>
            <thead><tr><th>Invoice</th><th>Customer</th><th>Date</th><th>Grand total</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($data['recent'] as $row): ?>
                <tr>
                    <td><a href="<?= Http::e(Http::url('/sales/invoice?invoice=' . urlencode((string) $row['InvoiceNo']))) ?>"><?= Http::e($row['InvoiceNo']) ?></a></td>
                    <td><?= Http::e($row['CustomerName']) ?></td>
                    <td><?= Http::e($row['SaleDate']) ?></td>
                    <td><?= Http::e(Http::money($row['GrandTotal'])) ?></td>
                    <td><?= Http::e($row['PaymentStatus']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($data['recent'] === []): ?><tr><td colspan="5">No sales yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
