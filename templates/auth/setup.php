<?php use Fms\Http; ?>
<section class="panel auth-card">
    <h1>Create the first administrator</h1>
    <p class="note">No administrator exists yet. This account can add staff users and other administrators.</p>
    <form method="post" action="<?= Http::e(Http::url('/admin/setup')) ?>" class="stack">
        <?= Http::csrfField() ?>
        <label>Username
            <input name="username" autocomplete="username" required value="<?= Http::e(Http::old('username')) ?>">
        </label>
        <label>Password
            <input name="password" type="password" autocomplete="new-password" required>
        </label>
        <label>Confirm password
            <input name="confirm" type="password" autocomplete="new-password" required>
        </label>
        <button type="submit">Create administrator</button>
    </form>
</section>
