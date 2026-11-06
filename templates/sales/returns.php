<?php use Fms\Catalog; use Fms\Http; ?>
<section class="panel">
    <form method="get" class="form-grid">
        <label>Invoice number
            <input name="invoice" required value="<?= Http::e($number) ?>">
        </label>
        <button type="submit">Search invoice</button>
        <a class="button secondary" href="<?= Http::e(Http::url('/sales/returns')) ?>">Clear</a>
    </form>
    <?php if ($lookupMessage): ?><p class="alert error" role="alert"><?= Http::e($lookupMessage) ?></p><?php endif; ?>
    <?php if ($invoice): ?>
        <dl class="invoice-meta">
            <div><dt>Customer</dt><dd><?= Http::e($invoice['CustomerName']) ?></dd></div>
            <div><dt>Sale date</dt><dd><?= Http::e($invoice['SaleDate']) ?></dd></div>
            <div><dt>Payment</dt><dd><?= Http::e($invoice['PaymentMethod']) ?></dd></div>
            <div><dt>Original total</dt><dd><?= Http::e(Http::money($invoice['GrandTotal'])) ?></dd></div>
        </dl>
        <form method="post" class="stack">
            <?= Http::csrfField() ?>
            <input type="hidden" name="action" value="process">
            <input type="hidden" name="invoice" value="<?= Http::e($invoice['InvoiceNo']) ?>">
            <div class="table-wrap">
                <table>
                    <caption>Sold items</caption>
                    <thead><tr><th>Select</th><th>Product</th><th>Sold</th><th>Already returned</th><th>Unit price</th></tr></thead>
                    <tbody>
                    <?php foreach ($invoice['items'] as $item): ?>
                        <tr>
                            <td><input type="radio" name="sale_item_id" value="<?= Http::e($item['SaleItemID']) ?>" data-unit-price="<?= Http::e($item['UnitPrice']) ?>" required aria-label="Return <?= Http::e($item['ProductName']) ?>"></td>
                            <td><?= Http::e($item['ProductName']) ?></td>
                            <td><?= Http::e(Http::qty($item['Quantity'])) ?></td>
                            <td><?= Http::e(Http::qty($item['ReturnedQty'])) ?></td>
                            <td><?= Http::e(Http::money($item['UnitPrice'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="form-grid">
                <label>Return quantity
                    <input name="quantity" data-return-qty inputmode="decimal" required>
                </label>
                <label>Refund amount
                    <input data-refund readonly value="0.00">
                </label>
                <label>Reason
                    <select name="reason" required>
                        <option value="">Select reason...</option>
                        <?php foreach (Catalog::RETURN_REASONS as $reason): ?>
                            <option value="<?= Http::e($reason) ?>"><?= Http::e($reason) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Refund method
                    <select name="method" required>
                        <option value="">Select refund method...</option>
                        <?php foreach (Catalog::REFUND_METHODS as $method): ?>
                            <option value="<?= Http::e($method) ?>"><?= Http::e($method) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <label>Notes
                <textarea name="notes"></textarea>
            </label>
            <p class="note">Damaged goods are not put back into stock. Every other reason restores the returned quantity.</p>
            <button type="submit">Process return</button>
        </form>
    <?php endif; ?>
</section>
<section class="panel" style="margin-top:1rem">
    <div class="table-wrap">
        <table>
            <caption>Return history</caption>
            <thead><tr><th>Customer</th><th>Product</th><th>Quantity</th><th>Refund</th><th>Method</th><th>Reason</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($history as $row): ?>
                <tr>
                    <td><?= Http::e($row['CustomerName']) ?></td>
                    <td><?= Http::e($row['ProductName']) ?></td>
                    <td><?= Http::e(Http::qty($row['Quantity'])) ?></td>
                    <td><?= Http::e(Http::money($row['RefundAmount'])) ?></td>
                    <td><?= Http::e($row['RefundMethod']) ?></td>
                    <td><?= Http::e($row['Reason']) ?></td>
                    <td><?= Http::e($row['ReturnDate']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($history === []): ?><tr><td colspan="7">No returns yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<script>
document.querySelectorAll("[data-unit-price]").forEach(function (radio) {
    radio.addEventListener("change", function () {
        const qty = document.querySelector("[data-return-qty]");
        const refund = document.querySelector("[data-refund]");
        if (!qty.value) qty.value = "";
        qty.dataset.price = radio.getAttribute("data-unit-price");
        const update = function () {
            refund.value = (Number(qty.value || "0") * Number(qty.dataset.price || "0")).toFixed(2);
        };
        qty.oninput = update;
        update();
    });
});
</script>
