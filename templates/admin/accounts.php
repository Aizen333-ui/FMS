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
        </div>
        <label class="check"><input type="checkbox" name="confirm" value="yes"> Confirm deletion</label>
        <button class="danger" type="submit" name="action" value="delete">Delete admin</button>
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
</section>
<script>
document.querySelectorAll("[data-admin-name]").forEach(function (radio) {
    radio.addEventListener("change", function () {
        const form = document.getElementById("admin-form");
        form.username.value = radio.getAttribute("data-admin-name");
        form.password.value = "";
        form.confirm.value = "";
    });
});
</script>
