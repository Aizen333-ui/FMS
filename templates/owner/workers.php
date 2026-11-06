<?php use Fms\Catalog; use Fms\Http; ?>
<section class="panel">
    <h2>Add worker</h2>
    <form method="post" class="form-grid">
        <?= Http::csrfField() ?>
        <input type="hidden" name="action" value="add">
        <label>Name
            <input name="name" required maxlength="30" value="<?= Http::e(Http::old('name')) ?>">
        </label>
        <label>Role
            <select name="role" required>
                <option value="">Select role...</option>
                <?php foreach (Catalog::WORKER_ROLES as $role): ?>
                    <option value="<?= Http::e($role) ?>"<?= Http::old('role') === $role ? ' selected' : '' ?>><?= Http::e($role) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Salary
            <input name="salary" inputmode="decimal" required value="<?= Http::e(Http::old('salary')) ?>">
        </label>
        <button type="submit">Add worker</button>
    </form>
</section>
<section class="panel" style="margin-top:1rem">
    <form method="post" class="stack">
        <?= Http::csrfField() ?>
        <input type="hidden" name="action" value="remove">
        <div class="table-wrap">
            <table>
                <caption>Workers</caption>
                <thead><tr><th>Select</th><th>ID</th><th>Name</th><th>Role</th><th>Salary</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><input type="radio" name="worker_id" value="<?= Http::e($row['WorkerID']) ?>" required aria-label="Select <?= Http::e($row['Name']) ?>"></td>
                        <td><?= Http::e($row['WorkerID']) ?></td>
                        <td><?= Http::e($row['Name']) ?></td>
                        <td><?= Http::e($row['Role']) ?></td>
                        <td><?= Http::e(Http::money($row['Salary'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($rows === []): ?><tr><td colspan="5">No workers yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <label class="check"><input type="checkbox" name="confirm" value="yes" required> I confirm deletion of the selected worker</label>
        <button class="danger" type="submit">Remove worker</button>
    </form>
</section>
