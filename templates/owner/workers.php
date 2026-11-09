<?php use Fms\Http; ?>
<section class="panel">
    <h2>Add, update or remove worker</h2>
    <form method="post" class="stack" id="worker-form">
        <?= Http::csrfField() ?>
        <div class="form-grid">
            <label>Name
                <input name="name" required maxlength="30" value="<?= Http::e(Http::old('name')) ?>">
            </label>
            <label>Role
                <input name="role" required maxlength="50" value="<?= Http::e(Http::old('role')) ?>" placeholder="Enter a role">
            </label>
            <label>Salary
                <input name="salary" inputmode="decimal" required value="<?= Http::e(Http::old('salary')) ?>">
            </label>
            <div class="actions">
                <button type="submit" name="action" value="add">Add worker</button>
                <button class="secondary" type="submit" name="action" value="update">Update worker</button>
                <button class="danger" type="submit" name="action" value="remove" formnovalidate>Remove worker</button>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <caption>Workers</caption>
                <thead><tr><th>Select</th><th>ID</th><th>Name</th><th>Role</th><th>Salary</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><input type="radio" name="worker_id" data-toggle-radio data-worker='<?= Http::e(json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT)) ?>' value="<?= Http::e($row['WorkerID']) ?>" aria-label="Select <?= Http::e($row['Name']) ?>"></td>
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
    </form>
</section>
<p class="page-back"><a class="button secondary" href="<?= Http::e(Http::url('/owner')) ?>">Back</a></p>
<script>
const workerForm = document.getElementById("worker-form");
workerForm.querySelectorAll("[data-worker]").forEach(function (radio) {
    radio.addEventListener("change", function () {
        const selected = workerForm.querySelector("[name=worker_id]:checked");
        const name = workerForm.elements.namedItem("name");
        const role = workerForm.elements.namedItem("role");
        const salary = workerForm.elements.namedItem("salary");
        if (selected) {
            const worker = JSON.parse(selected.getAttribute("data-worker"));
            name.value = worker.Name;
            role.value = worker.Role;
            salary.value = worker.Salary;
        } else {
            name.value = "";
            role.value = "";
            salary.value = "";
        }
    });
});
workerForm.addEventListener("submit", function (event) {
    if (event.submitter && event.submitter.value === "remove"
        && workerForm.querySelector("[name=worker_id]:checked")
        && !window.confirm("Are you sure you want to remove the selected worker?")) {
        event.preventDefault();
    }
});
</script>
