<?php use Fms\Http; ?>
<section class="panel auth-card">
    <h1>Administrator</h1>
    <p class="note">This sign-in uses the system administrator accounts, separate from factory staff.</p>
    <?php if (!$configured): ?>
        <p class="alert error" role="alert">The database connection string is empty. Configure the database before signing in.</p>
    <?php endif; ?>
    <form method="post" action="<?= Http::e(Http::url('/admin/login')) ?>" class="stack">
        <?= Http::csrfField() ?>
        <label>Username
            <input name="username" autocomplete="username" required value="<?= Http::e(Http::old('username')) ?>">
        </label>
        <label>Password
            <input name="password" type="password" autocomplete="current-password" required>
        </label>
        <button type="submit">Sign in</button>
    </form>
    <p><a href="<?= Http::e(Http::url('/')) ?>">Staff sign in</a></p>
</section>
