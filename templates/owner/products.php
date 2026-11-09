<?php use Fms\Http; ?>
<section class="panel">
    <h2>Products</h2>
    <form method="post" class="stack" id="product-form">
        <?= Http::csrfField() ?>
        <div class="form-grid">
            <label>Name
                <input name="name" required maxlength="100" value="<?= Http::e(Http::old('name')) ?>">
            </label>
            <label>Unit
                <input name="unit" required maxlength="30" value="<?= Http::e(Http::old('unit')) ?>" placeholder="Piece, Sqft, …">
            </label>
            <div class="actions">
                <button type="submit" name="action" value="add">Add product</button>
                <button class="danger" type="submit" name="action" value="delete" formnovalidate>Remove product</button>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <caption>Products</caption>
                <thead><tr><th>Select</th><th>ID</th><th>Name</th><th>Unit</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><input type="radio" name="product_id" data-toggle-radio value="<?= Http::e($row['ProductID']) ?>" aria-label="Select <?= Http::e($row['Name']) ?>"></td>
                        <td><?= Http::e($row['ProductID']) ?></td>
                        <td><?= Http::e($row['Name']) ?></td>
                        <td><?= Http::e($row['Unit']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($rows === []): ?><tr><td colspan="4">No products yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </form>
    <p class="note">Products with production records cannot be removed.</p>
</section>
<script>
document.getElementById("product-form").addEventListener("submit", function (event) {
    const selectedProduct = event.currentTarget.querySelector("[name=product_id]:checked");
    if (event.submitter && event.submitter.value === "delete" && selectedProduct
        && !window.confirm("Are you sure you want to remove the selected product?")) {
        event.preventDefault();
    }
});
</script>
<p class="page-back"><a class="button secondary" href="<?= Http::e(Http::url('/owner')) ?>">Back</a></p>
