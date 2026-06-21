<?php
/**
 * Salto de página planificado (solo impresión navegador). Dompdf no lo usa.
 *
 * @var int $plan_page_index Página 0-based del LayoutPlan para el nodo siguiente.
 */
$planPageIndex = max(0, (int) ($plan_page_index ?? 0));
?>
<div class="report-browser-print-plan-page-break" data-plan-page="<?= (int) $planPageIndex ?>" aria-hidden="true"></div>
