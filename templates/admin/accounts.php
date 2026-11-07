<?php use Fms\Http; ?>
<section class="panel">
    <form method="post" class="stack" id="admin-form">
        <?= Http::csrfField() ?>
        <div class="form-grid">
            <label>Username
                <input name="username" value="<?= Http::e(Http::old('username')) ?>">
            </label>
            <label>Password
                <input name="password" type="password" autocomplete="new-password">
            </label>
            <label>Confirm password
                <input name="confirm" type="password" autocomplete="new-password">
            </label>
        </div>
        <div class="actions">
            <button type="submit" name="action" value="add">Add admin</button>
            <button class="secondary" type="submit" name="action" value="update">Update admin</button>
            <button class="danger" type="submit" name="action" value="delete">Delete admin</button>
        </div>
    <div class="table-wrap">
        <table>
            <caption>Administrators</caption>
            <thead><tr><th></th><th>ID</th><th>Username</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><input type="radio" name="admin_id" value="<?= Http::e($row['AdminID']) ?>" data-admin-name="<?= Http::e($row['Username']) ?>" aria-label="Select <?= Http::e($row['Username']) ?>"></td>
                    <td><?= Http::e($row['AdminID']) ?></td>
                    <td><?= Http::e($row['Username']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    </form>
    <p class="page-back"><a class="button secondary" href="<?= Http::e(Http::url('/admin')) ?>">Back</a></p>
</section>
<script>
document.querySelectorAll("[data-admin-name]").forEach(function (radio) {
    let wasCheckedOnPointerDown = false;

    radio.addEventListener("pointerdown", function () {
        wasCheckedOnPointerDown = radio.checked;
    });

    radio.addEventListener("click", function () {
        const shouldUnselect = wasCheckedOnPointerDown && radio.checked;
        wasCheckedOnPointerDown = false;
        if (!shouldUnselect) {
            return;
        }

        radio.checked = false;
        const form = document.getElementById("admin-form");
        form.username.value = "";
        form.password.value = "";
        form.confirm.value = "";
    });

    radio.addEventListener("change", function () {
        const form = document.getElementById("admin-form");
        form.username.value = radio.getAttribute("data-admin-name");
        form.password.value = "";
        form.confirm.value = "";
    });
});

document.getElementById("admin-form").addEventListener("submit", function (event) {
    if (event.submitter && event.submitter.name === "action" && event.submitter.value === "delete"
        && !window.confirm("Are you sure you want to delete this admin?")) {
        event.preventDefault();
    }
});
</script>
