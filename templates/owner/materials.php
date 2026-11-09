<?php use Fms\Http; ?>
<section class="panel">
    <h2>Add stock</h2>
    <p class="note">Enter a material name and its unit. Adding stock also records an expense for quantity times unit price.</p>
    <form method="post" class="form-grid">
        <?= Http::csrfField() ?>
        <input type="hidden" name="action" value="add">
        <label>Material
            <input name="name" required maxlength="100" value="<?= Http::e(Http::old('name')) ?>">
        </label>
        <label>Unit
            <input name="unit" required maxlength="30" value="<?= Http::e(Http::old('unit')) ?>">
        </label>
        <label>Quantity
            <input name="quantity" inputmode="decimal" required value="<?= Http::e(Http::old('quantity')) ?>">
        </label>
        <label>Unit price
            <input name="unit_price" inputmode="decimal" required value="<?= Http::e(Http::old('unit_price')) ?>">
        </label>
        <button type="submit">Add material</button>
    </form>
</section>
<section class="panel" style="margin-top:1rem">
    <h2>Reduce or delete stock</h2>
    <p class="note">Choose a stock line and leave the material blank to delete that line. Choose a material and a quantity to reduce the oldest line of that material. A line that reaches zero is deleted.</p>
    <form method="post" class="stack" id="remove-stock-form">
        <?= Http::csrfField() ?>
        <input type="hidden" name="action" value="remove">
        <div class="stock-remove-controls">
            <label>Material
                <select name="name">
                    <option value="">Select the raw material...</option>
                    <?php foreach ($materials as $material): ?>
                        <option value="<?= Http::e($material['Name']) ?>"><?= Http::e($material['Name']) ?> (<?= Http::e($material['Unit']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Quantity to remove
                <input name="quantity" inputmode="decimal">
            </label>
            <button class="danger" type="submit">Remove</button>
        </div>
        <div class="table-wrap">
            <table>
                <caption>Stock lines</caption>
                <thead><tr><th>Select</th><th>ID</th><th>Name</th><th>Quantity</th><th>Unit</th><th>Unit price</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><input type="radio" name="material_id" data-toggle-radio value="<?= Http::e($row['MaterialID']) ?>" aria-label="Select <?= Http::e($row['Name']) ?> line <?= Http::e($row['MaterialID']) ?>"></td>
                        <td><?= Http::e($row['MaterialID']) ?></td>
                        <td><?= Http::e($row['Name']) ?></td>
                        <td><?= Http::e(Http::qty($row['Quantity'])) ?></td>
                        <td><?= Http::e($row['Unit']) ?></td>
                        <td><?= Http::e(Http::money($row['UnitPrice'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($rows === []): ?><tr><td colspan="6">No raw material is in stock.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </form>
</section>
<p class="page-back"><a class="button secondary" href="<?= Http::e(Http::url('/owner')) ?>">Back</a></p>
<script>
document.getElementById("remove-stock-form").addEventListener("submit", function (event) {
    const form = event.currentTarget;
    const selectedLine = form.querySelector("[name=material_id]:checked");
    const selectedMaterial = form.elements.namedItem("name").value;

    if (selectedLine && selectedMaterial === ""
        && !window.confirm("Are you sure you want to delete this stock line?")) {
        event.preventDefault();
    }
});
</script>
