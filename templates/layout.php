<?php
use Fms\Auth;
use Fms\Http;

$role = Auth::role();
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$nav = match ($role) {
    'Owner' => [
        '/owner' => 'Dashboard',
        '/owner/materials' => 'Raw material',
        '/owner/products' => 'Products',
        '/owner/payments' => 'Payments',
        '/owner/workers' => 'Workers',
        '/owner/reports' => 'Reports',
    ],
    'Manager' => [
        '/factory' => 'Dashboard',
        '/factory/production' => 'Record production',
        '/factory/usage' => 'Material usage',
    ],
    'Sales Person' => [
        '/sales' => 'Dashboard',
        '/sales/new' => 'New sale',
        '/sales/products' => 'Products',
        '/sales/customers' => 'Customers',
        '/sales/history' => 'Sales history',
        '/sales/returns' => 'Returns',
    ],
    'Administrator' => [
        '/admin' => 'Dashboard',
        '/admin/users' => 'Users',
        '/admin/accounts' => 'Admin accounts',
        '/admin/reports' => 'Reports',
        '/admin/settings' => 'System settings',
        '/admin/backup' => 'Database backup',
    ],
    default => [],
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= Http::e($title ?? 'Factory Management System') ?></title>
    <link rel="stylesheet" href="<?= Http::e(Http::url('/assets/app.css')) ?>">
</head>
<body>
<a class="skip" href="#main">Skip to content</a>
<?php if ($nav === []): ?>
<main id="main" class="auth-shell">
    <?php foreach (Http::flashes() as $flash): ?>
        <div class="alert <?= Http::e($flash['type']) ?>" role="status"><?= Http::e($flash['message']) ?></div>
    <?php endforeach; ?>
    <?= $content ?>
</main>
<?php else: ?>
<div class="shell">
    <aside class="sidebar" aria-label="Sections">
        <div class="brand">
            <strong>MS Crete</strong>
            <span><?= Http::e($role) ?></span>
        </div>
        <?php foreach ($nav as $href => $label): ?>
            <a href="<?= Http::e(Http::url($href)) ?>"<?= rtrim($path, '/') === rtrim(Http::basePath() . $href, '/') ? ' aria-current="page"' : '' ?>><?= Http::e($label) ?></a>
        <?php endforeach; ?>
        <form method="post" action="<?= Http::e(Http::url('/logout')) ?>">
            <?= Http::csrfField() ?>
            <button class="linkish" type="submit">Log out</button>
        </form>
    </aside>
    <div class="workspace">
        <header class="topbar">
            <div>
                <button class="menu-toggle" type="button" data-menu aria-expanded="false">Menu</button>
                <h1><?= Http::e($title ?? '') ?></h1>
            </div>
            <p class="who"><?= Http::e(Auth::user()['name'] ?? Auth::username()) ?></p>
        </header>
        <main id="main">
            <?php foreach (Http::flashes() as $flash): ?>
                <div class="alert <?= Http::e($flash['type']) ?>" role="status"><?= Http::e($flash['message']) ?></div>
            <?php endforeach; ?>
            <?= $content ?>
        </main>
    </div>
</div>
<?php endif; ?>
<script src="<?= Http::e(Http::url('/assets/app.js')) ?>"></script>
</body>
</html>
