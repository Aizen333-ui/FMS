<?php use Fms\Catalog; use Fms\Http; ?>
<section class="panel">
    <h2>Record material used</h2>
    <p class="note">Select one stock line to take quantity from that line only. Leave quantity blank to use the whole line. Leave the line unselected and choose a material to spread the quantity across every line of that material, oldest first.</p>
    <form method="post" class="stack">
        <?= Http::csrfField() ?>
        <div class="form-grid">
            <label>Material
                <select name="name">
                    <option value="">Select raw material...</option>
                    <?php foreach (Catalog::MATERIALS as $name): ?>
                        <option value="<?= Http::e($name) ?>"><?= Http::e($name) ?> (<?= Http::e(Catalog::usageUnit($name)) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Quantity
                <input name="quantity" inputmode="decimal">
            </label>
            <label>Date
                <input type="date" name="date" required max="<?= Http::e(Http::today()) ?>" value="<?= Http::e(Http::today()) ?>">
            </label>
        </div>
        <div class="table-wrap">
            <table>
                <caption>Current stock</caption>
                <thead><tr><th>Select</th><th>ID</th><th>Name</th><th>Quantity</th><th>Unit</th><th>Unit price</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><input type="radio" name="material_id" value="<?= Http::e($row['MaterialID']) ?>" aria-label="Use <?= Http::e($row['Name']) ?> line <?= Http::e($row['MaterialID']) ?>"></td>
                        <td><?= Http::e($row['MaterialID']) ?></td>
                        <td><?= Http::e($row['Name']) ?></td>
                        <td><?= Http::e(Http::qty($row['Quantity'])) ?></td>
                        <td><?= Http::e($row['Unit']) ?></td>
                        <td><?= Http::e(Http::money($row['UnitPrice'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($rows === []): ?><tr><td colspan="6">No stock lines.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <label class="check"><input type="checkbox" name="confirm" value="yes" required> I confirm this usage</label>
        <button type="submit">Remove from stock</button>
    </form>
</section>
