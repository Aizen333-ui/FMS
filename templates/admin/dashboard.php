<?php use Fms\Http; ?>
<section class="cards">
    <article class="card"><span>Users</span><strong><?= Http::e((string) $data['total_users']) ?></strong></article>
    <article class="card"><span>Active</span><strong><?= Http::e((string) $data['active_users']) ?></strong></article>
    <article class="card"><span>Disabled</span><strong><?= Http::e((string) $data['disabled_users']) ?></strong></article>
    <article class="card"><span>Administrators</span><strong><?= Http::e((string) $data['admins']) ?></strong></article>
</section>
<section class="panel" style="margin-top:1rem">
    <div class="table-wrap">
        <table>
            <caption>Users by role</caption>
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
            <?php if ($data['by_role'] === []): ?><tr><td colspan="4">No staff users yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
