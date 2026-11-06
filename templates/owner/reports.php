<?php use Fms\Catalog; use Fms\Http; ?>
<section class="panel">
    <form method="get" class="form-grid">
        <label>From
            <input type="date" name="from" required value="<?= Http::e($from) ?>">
        </label>
        <label>To
            <input type="date" name="to" required value="<?= Http::e($to) ?>">
        </label>
        <label>Type
            <select name="type">
                <option value="All"<?= $type === 'All' ? ' selected' : '' ?>>All</option>
                <?php foreach (Catalog::REPORT_TYPES as $item): ?>
                    <option value="<?= Http::e($item) ?>"<?= $type === $item ? ' selected' : '' ?>><?= Http::e($item) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit">View report</button>
    </form>
</section>
<section class="panel" style="margin-top:1rem">
    <div class="table-wrap">
        <table>
            <caption>Factory activity</caption>
            <thead><tr><th>Type</th><th>Name</th><th>Amount / quantity</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= Http::e($row['Type']) ?></td>
                    <td><?= Http::e($row['Name']) ?></td>
                    <td><?= Http::e(Http::qty($row['Amount'])) ?></td>
                    <td><?= Http::e($row['Date']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$loaded): ?><tr><td colspan="4">Choose a date range and view the report.</td></tr>
            <?php elseif ($rows === []): ?><tr><td colspan="4">No rows in this range.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
