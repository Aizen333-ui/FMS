<?php use Fms\Http; ?>
<section class="panel">
    <form method="get" class="form-grid">
        <label>Search name, phone, or address
            <input name="q" value="<?= Http::e($search) ?>">
        </label>
        <button type="submit">Search</button>
        <a class="button secondary" href="<?= Http::e(Http::url('/sales/customers')) ?>">Clear</a>
    </form>
</section>
<section class="panel" style="margin-top:1rem">
    <h2>Customer</h2>
    <form method="post" class="stack" id="customer-form">
        <?= Http::csrfField() ?>
        <div class="form-grid">
        <label>Name
            <input name="name" required value="<?= Http::e(Http::old('name')) ?>">
        </label>
        <label>Phone
            <input name="phone" value="<?= Http::e(Http::old('phone')) ?>">
        </label>
        <label>Address
            <input name="address" value="<?= Http::e(Http::old('address')) ?>">
        </label>
        <div class="actions">
            <button type="submit" name="action" value="add">Add customer</button>
            <button class="secondary" type="submit" name="action" value="edit">Update selected</button>
        </div>
        </div>
    <div class="table-wrap">
        <table>
            <caption>Customers</caption>
            <thead><tr><th>Select</th><th>ID</th><th>Name</th><th>Phone</th><th>Address</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><input type="radio" name="customer_id" value="<?= Http::e($row['CustomerID']) ?>" data-customer='<?= Http::e(json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT)) ?>' aria-label="Select <?= Http::e($row['CustomerName']) ?>"></td>
                    <td><?= Http::e($row['CustomerID']) ?></td>
                    <td><?= Http::e($row['CustomerName']) ?></td>
                    <td><?= Http::e($row['Phone']) ?></td>
                    <td><?= Http::e($row['Address']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($rows === []): ?><tr><td colspan="5">No customers yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    </form>
</section>
<script>
document.querySelectorAll("[data-customer]").forEach(function (radio) {
    radio.addEventListener("change", function () {
        const row = JSON.parse(radio.getAttribute("data-customer"));
        const form = document.getElementById("customer-form");
        form.name.value = row.CustomerName;
        form.phone.value = row.Phone || "";
        form.address.value = row.Address || "";
    });
});
</script>
