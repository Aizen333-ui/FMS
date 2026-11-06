<?php use Fms\Http; ?>
<section class="panel auth-card">
    <h1><?= Http::e($title ?? 'Database') ?></h1>
    <p class="alert error" role="alert"><?= Http::e($error ?? 'The database is not available.') ?></p>
    <p>Create an empty MySQL database, import <code>database/schema.mysql.sql</code>, then set <code>connection_string</code> in <code>config/database.php</code>. It is intentionally empty until the local server is configured.</p>
    <p>After that, run <code>php bin/install.php</code> if you have not imported the schema, and open <code>/admin/setup</code> to create the first administrator.</p>
</section>
