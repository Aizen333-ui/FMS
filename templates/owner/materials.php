<?php use Fms\Catalog; use Fms\Http; ?>
<section class="panel">
    <h2>Add stock</h2>
    <p class="note">Cement is stored in bags, sand and crush in tons, steel in kilograms, and mold oil in liters. Adding stock also records an expense for quantity times unit price.</p>
    <form method="post" class="form-grid">
        <?= Http::csrfField() ?>
        <input type="hidden" name="action" value="add">
        <label>Material
            <select name="name" required>
                <option value="">Select the raw material...</option>
                <?php foreach (Catalog::MATERIALS as $name): ?>
                    <option value="<?= Http::e($name) ?>"<?= Http::old('name') === $name ? ' selected' : '' ?>><?= Http::e($name) ?> (<?= Http::e(Catalog::materialUnit($name)) ?>)</option>
                <?php endforeach; ?>
            </select>
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
    <form method="post" class="stack">
        <?= Http::csrfField() ?>
        <input type="hidden" name="action" value="remove">
        <div class="form-grid">
            <label>Material
                <select name="name">
                    <option value="">Select the raw material...</option>
                    <?php foreach (Catalog::MATERIALS as $name): ?>
                        <option value="<?= Http::e($name) ?>"><?= Http::e($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Quantity to remove
                <input name="quantity" inputmode="decimal">
            </label>
        </div>
        <div class="table-wrap">
            <table>
                <caption>Stock lines</caption>
                <thead><tr><th>Select</th><th>ID</th><th>Name</th><th>Quantity</th><th>Unit</th><th>Unit price</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><input type="radio" name="material_id" value="<?= Http::e($row['MaterialID']) ?>" aria-label="Select <?= Http::e($row['Name']) ?> line <?= Http::e($row['MaterialID']) ?>"></td>
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
        <label class="check"><input type="checkbox" name="confirm" value="yes" required> I confirm this stock change</label>
        <button class="danger" type="submit">Remove</button>
    </form>
</section>
