<?php use Fms\Http; ?>
<section class="cards">
    <article class="card"><span>Total users</span><strong><?= Http::e((string) $data['total_users']) ?></strong></article>
    <article class="card"><span>Active users</span><strong><?= Http::e((string) $data['active_users']) ?></strong></article>
    <article class="card"><span>Disabled users</span><strong><?= Http::e((string) $data['disabled_users']) ?></strong></article>
    <article class="card"><span>Administrators</span><strong><?= Http::e((string) $data['admins']) ?></strong></article>
</section>
<section class="panel" style="margin-top:1rem">
    <div class="table-wrap">
        <table>
            <caption>By role</caption>
            <thead><tr><th>Role</th><th>Total</th><th>Active</th><th>Disabled</th></tr></thead>
            <tbody>
            <?php foreach ($data['by_role'] as $row): ?>
                <tr>
                    <td><?= Http::e($row['Role']) ?></td>
                    <td><?= Http::e($row['Total']) ?></td>
                    <td><?= Http::e($row['Active']) ?></td>
                    <td><?= Http::e($row['Disabled']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<section class="panel" style="margin-top:1rem">
    <div class="table-wrap">
        <table>
            <caption>Recent users</caption>
            <thead><tr><th>ID</th><th>Name</th><th>Username</th><th>Role</th><th>Active</th></tr></thead>
            <tbody>
            <?php foreach ($data['recent'] as $row): ?>
                <tr>
                    <td><?= Http::e($row['UserID']) ?></td>
                    <td><?= Http::e($row['FullName']) ?></td>
                    <td><?= Http::e($row['Username']) ?></td>
                    <td><?= Http::e($row['Role']) ?></td>
                    <td><?= (int) $row['IsActive'] === 1 ? 'Yes' : 'No' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<section class="panel" style="margin-top:1rem">
    <p><a class="button" href="<?= Http::e(Http::url('/admin/logs/export')) ?>">Export logs</a></p>
    <div class="table-wrap audit-log-wrap">
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
<p class="page-back"><a class="button secondary" href="<?= Http::e(Http::url('/admin')) ?>">Back</a></p>
