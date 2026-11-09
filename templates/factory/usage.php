<?php use Fms\Http; ?>
<section class="panel">
    <h2>Record material used</h2>
    <p class="note">Select one stock line to take quantity from that line only. Leave quantity blank to use the whole line. Leave the line unselected and choose a material to spread the quantity across every line of that material, oldest first.</p>
    <form method="post" class="stack" id="material-usage-form">
        <?= Http::csrfField() ?>
        <div class="form-grid">
            <label>Material
                <select name="name">
                    <option value="">Select raw material...</option>
                    <?php foreach ($materials as $material): ?>
                        <option value="<?= Http::e($material['Name']) ?>"><?= Http::e($material['Name']) ?> (<?= Http::e($material['Unit']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Quantity
                <input name="quantity" inputmode="decimal">
            </label>
            <label>Date
                <input type="date" name="date" required max="<?= Http::e(Http::today()) ?>" value="<?= Http::e(Http::today()) ?>">
            </label>
            <button type="submit">Remove from stock</button>
        </div>
        <div class="table-wrap">
            <table>
                <caption>Current stock</caption>
                <thead><tr><th>Select</th><th>ID</th><th>Name</th><th>Quantity</th><th>Unit</th><th>Unit price</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><input type="radio" name="material_id" data-toggle-radio data-usage='<?= Http::e(json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT)) ?>' value="<?= Http::e($row['MaterialID']) ?>" aria-label="Use <?= Http::e($row['Name']) ?> line <?= Http::e($row['MaterialID']) ?>"></td>
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
    </form>
</section>
<p class="page-back"><a class="button secondary" href="<?= Http::e(Http::url('/factory')) ?>">Back</a></p>
<script>
const usageForm = document.getElementById("material-usage-form");
usageForm.querySelectorAll("[data-usage]").forEach(function (radio) {
    radio.addEventListener("change", function () {
        const selected = usageForm.querySelector("[name=material_id]:checked");
        const material = usageForm.elements.namedItem("name");
        const quantity = usageForm.elements.namedItem("quantity");
        if (selected) {
            const stock = JSON.parse(selected.getAttribute("data-usage"));
            material.value = stock.Name;
            quantity.value = stock.Quantity;
        } else {
            material.value = "";
            quantity.value = "";
        }
    });
});
usageForm.addEventListener("submit", function (event) {
    if (!window.confirm("Are you sure you want to remove this quantity from stock?")) {
        event.preventDefault();
    }
});
</script>
