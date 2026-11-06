<?php use Fms\Http; ?>
<section class="cards">
    <article class="card"><span>Connection</span><strong><?= $status['ok'] ? 'OK' : 'Failed' ?></strong></article>
    <article class="card"><span>Users</span><strong><?= Http::e($status['users'] === null ? '—' : (string) $status['users']) ?></strong></article>
    <article class="card"><span>Admins</span><strong><?= Http::e($status['admins'] === null ? '—' : (string) $status['admins']) ?></strong></article>
</section>
<section class="panel" style="margin-top:1rem">
    <p class="<?= $status['ok'] ? 'alert success' : 'alert error' ?>" role="status"><?= Http::e($status['message']) ?></p>
    <label>Connection string
        <input readonly value="<?= Http::e($status['connection'] === '' ? '(empty)' : $status['connection']) ?>">
    </label>
    <p class="note">The password, when one is present, is hidden. Change the connection in config/database.php on the server. It is not edited from this page.</p>
    <p><a class="button" href="<?= Http::e(Http::url('/admin/logs/export')) ?>">Export logs</a></p>
    <div class="table-wrap">
        <table>
            <caption>Audit logs</caption>
            <thead><tr><th>User</th><th>Action</th><th>Module</th><th>Description</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= Http::e($log['Username']) ?></td>
                    <td><?= Http::e($log['Action']) ?></td>
                    <td><?= Http::e($log['Module']) ?></td>
                    <td><?= Http::e($log['Description']) ?></td>
                    <td><?= Http::e($log['Status']) ?></td>
                    <td><?= Http::e($log['LogDate']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($logs === []): ?><tr><td colspan="6">No logs yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
