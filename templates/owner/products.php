<?php use Fms\Http; ?>
<section class="panel">
    <h2>Add product</h2>
    <form method="post" class="form-grid">
        <?= Http::csrfField() ?>
        <input type="hidden" name="action" value="add">
        <label>Name
            <input name="name" required maxlength="100" value="<?= Http::e(Http::old('name')) ?>">
        </label>
        <label>Unit
            <input name="unit" required maxlength="30" value="<?= Http::e(Http::old('unit')) ?>" placeholder="Piece, Sqft, …">
        </label>
        <button type="submit">Add product</button>
    </form>
</section>
<section class="panel" style="margin-top:1rem">
    <form method="post" class="stack" id="product-form">
        <?= Http::csrfField() ?>
        <div class="table-wrap">
            <table>
                <caption>Products</caption>
                <thead><tr><th>Select</th><th>ID</th><th>Name</th><th>Unit</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><input type="radio" name="product_id" value="<?= Http::e($row['ProductID']) ?>" data-product='<?= Http::e(json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT)) ?>' aria-label="Select <?= Http::e($row['Name']) ?>"></td>
                        <td><?= Http::e($row['ProductID']) ?></td>
                        <td><?= Http::e($row['Name']) ?></td>
                        <td><?= Http::e($row['Unit']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($rows === []): ?><tr><td colspan="4">No products yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="form-grid">
            <label>Name
                <input name="name" maxlength="100">
            </label>
            <label>Unit
                <input name="unit" maxlength="30">
            </label>
            <button class="secondary" type="submit" name="action" value="update">Update selected</button>
        </div>
        <label class="check"><input type="checkbox" name="confirm" value="yes"> I confirm removal of the selected product</label>
        <button class="danger" type="submit" name="action" value="delete">Remove product</button>
    </form>
    <p class="note">A product that already has production records cannot be removed. Updating the name keeps those records.</p>
</section>
<script>
document.querySelectorAll("[data-product]").forEach(function (radio) {
    radio.addEventListener("change", function () {
        const row = JSON.parse(radio.getAttribute("data-product"));
        const form = document.getElementById("product-form");
        form.name.value = row.Name;
        form.unit.value = row.Unit;
    });
});
</script>
