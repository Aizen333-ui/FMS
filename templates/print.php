<?php use Fms\Http; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= Http::e($title ?? 'Invoice') ?></title>
    <link rel="stylesheet" href="<?= Http::e(Http::url('/assets/app.css')) ?>">
</head>
<body>
<main id="main">
    <?php foreach (Http::flashes() as $flash): ?>
        <p class="alert <?= Http::e($flash['type']) ?> no-print" role="status"><?= Http::e($flash['message']) ?></p>
    <?php endforeach; ?>
    <?= $content ?>
</main>
</body>
</html>
