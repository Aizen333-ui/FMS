<?php use Fms\Http; ?>
<section class="panel">
    <form method="get" class="form-grid">
        <label>Search product
            <input name="q" value="<?= Http::e($search) ?>">
        </label>
        <label>Stock
            <select name="filter">
                <?php foreach (['All Products', 'In Stock', 'Low Stock'] as $item): ?>
                    <option value="<?= Http::e($item) ?>"<?= $filter === $item ? ' selected' : '' ?>><?= Http::e($item) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit">Search</button>
        <a class="button secondary" href="<?= Http::e(Http::url('/sales/products')) ?>">Clear</a>
    </form>
    <p class="note">Low stock means quantity above zero and at most 5,000. This list shows each production batch.</p>
    <div class="table-wrap">
        <table>
            <caption>Products</caption>
            <thead><tr><th>Product</th><th>Quantity</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= Http::e($row['ProductName']) ?></td>
                    <td><?= Http::e(Http::qty($row['Quantity'])) ?></td>
                    <td><?= Http::e($row['Date']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($rows === []): ?><tr><td colspan="3">No products match.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
