<?php use Fms\Http; ?>
<section class="panel">
    <form method="post" class="form-grid">
        <?= Http::csrfField() ?>
        <label>Product
            <select name="product" required>
                <option value="">Select the product...</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?= Http::e($product['Name']) ?>"><?= Http::e($product['Name']) ?> (<?= Http::e($product['Unit']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Quantity
            <input name="quantity" inputmode="decimal" required>
        </label>
        <label>Date
            <input type="date" name="date" required max="<?= Http::e(Http::today()) ?>" value="<?= Http::e(Http::today()) ?>">
        </label>
        <button type="submit">Save production</button>
    </form>
    <?php if ($products === []): ?><p class="note">No products yet. The owner adds them under Products.</p><?php endif; ?>
    <p class="note">Quantity must be greater than zero and at most 10,000. The saved time is the current time on the selected day.</p>
</section>
<section class="panel" style="margin-top:1rem">
    <div class="table-wrap">
        <table>
            <caption>Production records</caption>
            <thead><tr><th>ID</th><th>Product</th><th>Quantity</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= Http::e($row['ProductionID']) ?></td>
                    <td><?= Http::e($row['ProductName']) ?></td>
                    <td><?= Http::e(Http::qty($row['Quantity'])) ?></td>
                    <td><?= Http::e($row['Date']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($rows === []): ?><tr><td colspan="4">No production recorded.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
