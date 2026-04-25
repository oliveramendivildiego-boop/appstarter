<?php
/**
 * Paginación independiente (query: h_page, a_page) para listas del expediente.
 *
 * @var string $pager_base
 * @var int    $this_page
 * @var int    $other_page
 * @var int    $total_pages
 * @var int    $total_items
 * @var int    $per_page
 * @var string $this_param  h_page|a_page
 * @var string $other_param a_page|h_page
 */
if (!isset($pager_base, $this_param, $other_param)) {
    return;
}
$total_items = (int) ($total_items ?? 0);
$total_pages = (int) ($total_pages ?? 0);
$this_page = (int) ($this_page ?? 1);
$other_page = (int) ($other_page ?? 1);
$per_page = (int) ($per_page ?? 15);
if ($total_items < 1) {
    return;
}
$from = ($this_page - 1) * $per_page + 1;
$to = min($this_page * $per_page, $total_items);

$link = static function (int $h, int $a) use ($pager_base): string {
    return $pager_base . '?' . http_build_query(['h_page' => $h, 'a_page' => $a]);
};

if ($this_param === 'h_page') {
    $prevH = max(1, $this_page - 1);
    $prevA = $other_page;
    $nextH = min($total_pages, $this_page + 1);
    $nextA = $other_page;
} else {
    $prevH = $other_page;
    $prevA = max(1, $this_page - 1);
    $nextH = $other_page;
    $nextA = min($total_pages, $this_page + 1);
}
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 px-3 py-2 border-top bg-light">
    <small class="text-muted mb-0">
        Mostrando <?= (int) $from ?>–<?= (int) $to ?> de <?= (int) $total_items ?>
    </small>
    <?php if ($total_pages > 1) : ?>
        <nav aria-label="Paginación">
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item<?= $this_page <= 1 ? ' disabled' : '' ?>">
                    <a class="page-link" href="<?= $link($prevH, $prevA) ?>">Anterior</a>
                </li>
                <li class="page-item disabled">
                    <span class="page-link">Página <?= (int) $this_page ?> / <?= (int) $total_pages ?></span>
                </li>
                <li class="page-item<?= $this_page >= $total_pages ? ' disabled' : '' ?>">
                    <a class="page-link" href="<?= $link($nextH, $nextA) ?>">Siguiente</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>
