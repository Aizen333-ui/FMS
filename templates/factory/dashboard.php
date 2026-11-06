<?php use Fms\Http; ?>
<section class="cards">
    <article class="card"><span>Total production</span><strong><?= Http::e(Http::qty($data['production'])) ?></strong></article>
    <article class="card"><span>Raw usage</span><strong><?= Http::e(Http::percent((float) $data['usage_percent'])) ?></strong></article>
</section>
<section class="cards" style="margin-top:1rem">
    <?php foreach ($data['cards'] as $card): ?>
        <article class="card"><span><?= Http::e($card['ProductName']) ?></span><strong><?= Http::e(Http::qty($card['TotalQty'])) ?></strong></article>
    <?php endforeach; ?>
    <?php if ($data['cards'] === []): ?><article class="card"><span>Production</span><strong>0</strong></article><?php endif; ?>
</section>
<div class="grid-2" style="margin-top:1rem">
    <section class="panel">
        <h2>Material usage this month</h2>
        <?php $max = 1; foreach ($data['material_chart'] as $row) { $max = max($max, (float) $row['Total']); } ?>
        <div class="bars">
            <?php if ($data['material_chart'] === []): ?><p class="note">No usage this month.</p><?php endif; ?>
            <?php foreach ($data['material_chart'] as $row): ?>
                <div class="bar-row">
                    <span><?= Http::e($row['Label']) ?></span>
                    <div class="track"><span style="width: <?= Http::e((string) ((float) $row['Total'] / $max * 100)) ?>%"></span></div>
                    <span><?= Http::e(Http::qty($row['Total'])) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <section class="panel">
        <h2>Monthly production</h2>
        <?php $max = 1; foreach ($data['production_chart'] as $row) { $max = max($max, (float) $row['Total']); } ?>
        <div class="bars">
            <?php if ($data['production_chart'] === []): ?><p class="note">No production this month.</p><?php endif; ?>
            <?php foreach ($data['production_chart'] as $row): ?>
                <div class="bar-row">
                    <span><?= Http::e($row['Label']) ?></span>
                    <div class="track"><span style="width: <?= Http::e((string) ((float) $row['Total'] / $max * 100)) ?>%"></span></div>
                    <span><?= Http::e(Http::qty($row['Total'])) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>
