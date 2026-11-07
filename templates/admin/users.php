<?php use Fms\Catalog; use Fms\Http; ?>
<section class="panel">
    <form method="get" class="user-search">
        <label>Search name, username, or role
            <input name="q" value="<?= Http::e($search) ?>">
        </label>
        <div class="actions">
            <button type="submit">Search</button>
            <a class="button secondary" href="<?= Http::e(Http::url('/admin/users')) ?>">Refresh</a>
        </div>
    </form>
    <h2>Account</h2>
    <form method="post" class="stack" id="user-form">
        <?= Http::csrfField() ?>
        <div class="form-grid">
            <label>Full name
                <input name="full_name" value="<?= Http::e(Http::old('full_name')) ?>">
            </label>
            <label>Username
                <input name="username" value="<?= Http::e(Http::old('username')) ?>">
            </label>
            <label>Password
                <input name="password" type="password" autocomplete="new-password">
            </label>
            <label>Confirm password
                <input name="confirm" type="password" autocomplete="new-password">
            </label>
            <label>Role
                <select name="role">
                    <option value="">Select a role...</option>
                    <?php foreach (Catalog::STAFF_ROLES as $role): ?>
                        <option value="<?= Http::e($role) ?>"<?= Http::old('role') === $role ? ' selected' : '' ?>><?= Http::e($role) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <div class="actions">
            <button type="submit" name="action" value="add">Add user</button>
            <button class="secondary" type="submit" name="action" value="update">Update user</button>
            <button class="secondary" type="submit" name="action" value="reset">Reset password</button>
            <button class="secondary" type="submit" name="action" value="toggle">Enable or disable</button>
            <button class="danger" type="submit" name="action" value="delete">Delete user</button>
        </div>
    <div class="table-wrap">
        <table>
            <caption>Users</caption>
            <thead><tr><th></th><th>ID</th><th>Name</th><th>Username</th><th>Role</th><th>Active</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><input type="radio" name="user_id" value="<?= Http::e($row['UserID']) ?>" data-user='<?= Http::e(json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT)) ?>' aria-label="Select <?= Http::e($row['Username']) ?>"></td>
                    <td><?= Http::e($row['UserID']) ?></td>
                    <td><?= Http::e($row['FullName']) ?></td>
                    <td><?= Http::e($row['Username']) ?></td>
                    <td><?= Http::e($row['Role']) ?></td>
                    <td><?= (int) $row['IsActive'] === 1 ? 'Yes' : 'No' ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($rows === []): ?><tr><td colspan="6">No users yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    </form>
    <p class="page-back"><a class="button secondary" href="<?= Http::e(Http::url('/admin')) ?>">Back</a></p>
</section>
<script>
document.querySelectorAll("[data-user]").forEach(function (radio) {
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
        const form = document.getElementById("user-form");
        form.full_name.value = "";
        form.username.value = "";
        form.role.value = "";
        form.password.value = "";
        form.confirm.value = "";
    });

    radio.addEventListener("change", function () {
        const row = JSON.parse(radio.getAttribute("data-user"));
        const form = document.getElementById("user-form");
        form.full_name.value = row.FullName;
        form.username.value = row.Username;
        form.role.value = row.Role;
        form.password.value = "";
        form.confirm.value = "";
    });
});

document.getElementById("user-form").addEventListener("submit", function (event) {
    if (event.submitter && event.submitter.name === "action" && event.submitter.value === "delete"
        && !window.confirm("Are you sure you want to delete this user?")) {
        event.preventDefault();
    }
});
</script>
