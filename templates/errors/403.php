<?php use Fms\Auth; use Fms\Http; ?>
<section class="panel">
    <h2>Not allowed</h2>
    <p>Your role does not include this section. Each role keeps the same boundaries as the desktop dashboards.</p>
    <p><a href="<?= Http::e(Http::url(Auth::home())) ?>">Go to your dashboard</a></p>
</section>
