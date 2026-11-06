<?php use Fms\Http; ?>
<section class="panel">
    <p>The desktop tool ran SQL Server <code>BACKUP DATABASE</code>. This web application writes a logical SQL snapshot of the factory tables into <code>storage/backups</code>, outside the public site, and lets you download it.</p>
    <form method="post">
        <?= Http::csrfField() ?>
        <button type="submit">Create backup</button>
    </form>
    <div class="table-wrap">
        <table>
            <caption>Saved backups</caption>
            <thead><tr><th>File</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($files as $file): ?>
                <tr>
                    <td><?= Http::e($file) ?></td>
                    <td><a href="<?= Http::e(Http::url('/admin/backup/download?file=' . urlencode($file))) ?>">Download</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($files === []): ?><tr><td colspan="2">No backups yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
