<?php
helper('layout');
$dt = header_datetime_context();
?>
<div class="header-datetime-wrap ms-auto d-flex align-items-center flex-shrink-0 px-2 px-md-3 py-2" aria-live="polite">
    <i class="fa-regular fa-clock header-datetime__icon me-1 me-sm-2" aria-hidden="true"></i>
    <time
        id="header-datetime"
        class="header-datetime navbar-theme-meta text-nowrap"
        datetime="<?= esc($dt['iso']) ?>"
        data-timezone="<?= esc($dt['timezone'], 'attr') ?>"
        data-format="<?= esc($dt['format'], 'attr') ?>"
        data-has-seconds="<?= ! empty($dt['has_seconds']) ? '1' : '0' ?>"
        title="<?= esc($dt['timezone']) ?>"
    ><?= esc($dt['display']) ?></time>
</div>
