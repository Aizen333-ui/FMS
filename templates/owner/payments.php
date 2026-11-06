<?php use Fms\Catalog; use Fms\Http; ?>
<section class="panel">
    <h2>Add payment</h2>
    <form method="post" class="form-grid">
        <?= Http::csrfField() ?>
        <input type="hidden" name="action" value="add">
        <label>Reason
            <select name="reason" data-reason required>
                <option value="">Select a reason...</option>
                <?php foreach (Catalog::PAYMENT_REASONS as $reason): ?>
                    <option value="<?= Http::e($reason) ?>"><?= Http::e($reason) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label data-worker>Worker
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
    <p class="note">Leave the amount blank to delete the selected payment. Enter an amount to subtract it. A matching reason is required only when a reason is selected.</p>
    <form method="post" class="stack">
        <?= Http::csrfField() ?>
        <input type="hidden" name="action" value="adjust">
        <div class="form-grid">
            <label>Reason check
                <select name="reason">
                    <option value="">No reason check</option>
                    <?php foreach (Catalog::PAYMENT_REASONS as $reason): ?>
                        <option value="<?= Http::e($reason) ?>"><?= Http::e($reason) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Amount to remove
                <input name="amount" inputmode="decimal">
            </label>
        </div>
        <div class="table-wrap">
            <table>
                <caption>Payments</caption>
                <thead><tr><th>Select</th><th>ID</th><th>Amount</th><th>Reason</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><input type="radio" name="payment_id" value="<?= Http::e($row['PaymentID']) ?>" required aria-label="Select payment <?= Http::e($row['PaymentID']) ?>"></td>
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
        <label class="check"><input type="checkbox" name="confirm" value="yes" required> I confirm this payment change</label>
        <button class="danger" type="submit">Update payment</button>
    </form>
</section>
