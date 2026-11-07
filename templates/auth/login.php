<?php use Fms\Http; ?>
<section class="panel auth-card">
    <h1 class="login-title">Factory Management System</h1>
    <p class="note">Sign in with a staff account. Owners, managers, and sales people each open their own dashboard.</p>
    <?php if (!$configured): ?>
        <p class="alert error" role="alert">The database connection string is empty. Configure <code>config/database.php</code> and import <code>database/schema.mysql.sql</code> before signing in.</p>
    <?php endif; ?>
    <form method="post" action="<?= Http::e(Http::url('/login')) ?>" class="stack auth-form">
        <?= Http::csrfField() ?>
        <label>Username
            <input name="username" autocomplete="username" required value="<?= Http::e(Http::old('username')) ?>">
        </label>
        <label>Password
            <input name="password" type="password" autocomplete="current-password" required>
        </label>
        <button type="submit">Sign in</button>
    </form>
    <p class="auth-switch"><a href="<?= Http::e(Http::url('/admin/login')) ?>">Administrator sign in</a></p>
</section>
