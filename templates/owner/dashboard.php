<?php use Fms\Http; ?>
<section class="cards">
    <article class="card"><span>Raw material usage</span><strong><?= Http::e(Http::percent((float) $data['usage_percent'])) ?></strong></article>
    <article class="card"><span>Expenses</span><strong><?= Http::e(Http::moneyWhole($data['expenses'])) ?></strong></article>
    <article class="card"><span>Production quantity</span><strong><?= Http::e(Http::qty($data['production'])) ?></strong></article>
</section>
<div class="grid-2" style="margin-top:1rem">
    <section class="panel">
        <h2>Workers</h2>
        <div class="cards">
            <?php foreach ($data['workers'] as $role => $count): ?>
                <article class="card"><span><?= Http::e($role) ?></span><strong><?= Http::e((string) $count) ?></strong></article>
            <?php endforeach; ?>
        </div>
    </section>
    <section class="panel">
        <h2>Raw material on hand</h2>
        <div class="cards">
            <?php foreach ($data['materials'] as $name => $qty): ?>
                <article class="card"><span><?= Http::e($name) ?></span><strong><?= Http::e(Http::qty($qty)) ?></strong></article>
            <?php endforeach; ?>
        </div>
    </section>
</div>
<div class="grid-2" style="margin-top:1rem">
    <section class="panel">
        <h2>Monthly material usage</h2>
        <?php $max = 1; foreach ($data['material_chart'] as $row) { $max = max($max, (float) $row['Total']); } ?>
        <div class="bars">
            <?php if ($data['material_chart'] === []): ?><p class="note">No material was used this month.</p><?php endif; ?>
            <?php foreach ($data['material_chart'] as $row): ?>
                <div class="bar-row">
                    <span><?= Http::e($row['Label']) ?></span>
                    <div class="track"><span style="width: <?= Http::e((string) ((float) $row['Total'] / $max * 100)) ?>%"></span></div>
                    <span><?= Http::e(Http::percent((float) $row['Total'])) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <section class="panel">
        <h2>Monthly expense distribution</h2>
        <?php $max = 1; foreach ($data['expense_chart'] as $row) { $max = max($max, (float) $row['Total']); } ?>
        <div class="bars">
            <?php if ($data['expense_chart'] === []): ?><p class="note">No expenses were recorded this month.</p><?php endif; ?>
            <?php foreach ($data['expense_chart'] as $row): ?>
                <div class="bar-row">
                    <span><?= Http::e($row['Label']) ?></span>
                    <div class="track"><span style="width: <?= Http::e((string) ((float) $row['Total'] / $max * 100)) ?>%"></span></div>
                    <span><?= Http::e(Http::percent((float) $row['Total'])) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>
