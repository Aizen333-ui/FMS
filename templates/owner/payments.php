<?php use Fms\Catalog; use Fms\Http; ?>
<section class="panel">
    <h2>Add payment</h2>
    <form method="post" class="form-grid">
        <?= Http::csrfField() ?>
        <input type="hidden" name="action" value="add">
        <label>Reason
            <select name="reason" data-reason required>
                <option value="">Select a reason...</option>
                <?php foreach ($materials as $material): ?>
                    <option value="<?= Http::e($material['Name']) ?>"><?= Http::e($material['Name']) ?></option>
                <?php endforeach; ?>
                <?php foreach (Catalog::PAYMENT_REASONS as $reason): ?>
                    <option value="<?= Http::e($reason) ?>"><?= Http::e($reason) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label data-worker hidden>Worker
            <select name="worker">
                <option value="">Select a worker...</option>
                <?php foreach ($workers as $worker): ?>
                    <option value="<?= Http::e($worker['Name']) ?>"><?= Http::e($worker['Name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Amount
            <input name="amount" inputmode="decimal" required>
        </label>
        <label>Date
            <input name="date" type="date" required max="<?= Http::e(Http::today()) ?>" value="<?= Http::e(Http::today()) ?>">
        </label>
        <button type="submit">Add payment</button>
    </form>
</section>
<section class="panel" style="margin-top:1rem">
    <h2>Delete or reduce a payment</h2>
    <p class="note">Select a payment to load its reason and current amount. Enter an amount to subtract it, or clear the amount to delete the selected payment.</p>
    <form method="post" class="stack" id="payment-adjust-form">
        <?= Http::csrfField() ?>
        <input type="hidden" name="action" value="adjust">
        <div class="form-grid">
            <label>Reason
                <input name="reason" readonly disabled>
            </label>
            <label>Amount to remove
                <input name="amount" inputmode="decimal" disabled>
            </label>
            <button class="danger" type="submit">Update payment</button>
        </div>
        <div class="table-wrap">
            <table>
                <caption>Payments</caption>
                <thead><tr><th>Select</th><th>ID</th><th>Amount</th><th>Reason</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><input type="radio" name="payment_id" data-toggle-radio data-payment='<?= Http::e(json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT)) ?>' value="<?= Http::e($row['PaymentID']) ?>" required aria-label="Select payment <?= Http::e($row['PaymentID']) ?>"></td>
                        <td><?= Http::e($row['PaymentID']) ?></td>
                        <td><?= Http::e(Http::money($row['Amount'])) ?></td>
                        <td><?= Http::e($row['Reason']) ?></td>
                        <td><?= Http::e($row['Date']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($rows === []): ?><tr><td colspan="5">No payments recorded.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </form>
</section>
<p class="page-back"><a class="button secondary" href="<?= Http::e(Http::url('/owner')) ?>">Back</a></p>
<script>
document.querySelectorAll("[data-payment]").forEach(function (radio) {
    radio.addEventListener("change", function () {
        const form = document.getElementById("payment-adjust-form");
        const reason = form.elements.namedItem("reason");
        const amount = form.elements.namedItem("amount");
        if (radio.checked) {
            const payment = JSON.parse(radio.getAttribute("data-payment"));
            reason.value = payment.Reason;
            amount.value = payment.Amount;
            reason.disabled = false;
            amount.disabled = false;
        } else if (!form.querySelector("[name=payment_id]:checked")) {
            reason.value = "";
            amount.value = "";
            reason.disabled = true;
            amount.disabled = true;
        }
    });
});
</script>
