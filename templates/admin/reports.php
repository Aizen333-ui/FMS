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
