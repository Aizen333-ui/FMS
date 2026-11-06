<?php use Fms\Http; ?>
<article class="invoice">
    <p class="no-print"><a href="<?= Http::e(Http::url('/sales/new')) ?>">Back to sales</a></p>
    <h1><?= Http::e($companyName) ?></h1>
    <h2>SALES INVOICE</h2>
    <div class="invoice-meta">
        <div>Invoice No: <?= Http::e($sale['InvoiceNo']) ?></div>
        <div>Date: <?= Http::e(date('d-m-Y', strtotime((string) $sale['SaleDate']))) ?></div>
        <div>Customer: <?= Http::e($sale['CustomerName']) ?></div>
        <div>Payment: <?= Http::e($sale['PaymentMethod'] ?: 'Pending') ?></div>
    </div>
    <table>
        <thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Amount</th></tr></thead>
        <tbody>
        <?php foreach ($sale['items'] as $item): ?>
            <tr>
                <td><?= Http::e($item['ProductName']) ?></td>
                <td><?= Http::e(Http::qty($item['Quantity'])) ?></td>
                <td><?= Http::e(number_format((float) $item['UnitPrice'], 2)) ?></td>
                <td><?= Http::e(number_format((float) $item['TotalAmount'], 2)) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <div class="totals">
        <div><span>Subtotal</span><span>Rs. <?= Http::e(number_format((float) $sale['SubTotal'], 2)) ?></span></div>
        <div><span>Discount</span><span>Rs. <?= Http::e(number_format((float) $sale['Discount'], 2)) ?></span></div>
        <div><span>Tax</span><span>Rs. <?= Http::e(number_format((float) $sale['Tax'], 2)) ?></span></div>
        <div><strong>Grand Total</strong><strong>Rs. <?= Http::e(number_format((float) $sale['GrandTotal'], 2)) ?></strong></div>
    </div>
    <p style="text-align:center">Thank you for your business!</p>
    <p class="no-print"><button type="button" onclick="window.print()">Print invoice</button></p>
</article>
