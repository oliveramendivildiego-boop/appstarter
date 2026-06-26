<?php
declare(strict_types=1);
putenv('PDF_RENDERER=mpdf');
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$p = new Config\Paths();
require $p->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($p);
$l = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$rs = is_array($l['page_style']['results_table'] ?? null) ? $l['page_style']['results_table'] : [];
echo json_encode([
    'title_top' => $rs['grupo_cabecera_title_margin_top_px'] ?? null,
    'title_bottom' => $rs['grupo_cabecera_title_margin_bottom_px'] ?? null,
    'metodo_top' => $rs['grupo_cabecera_metodo_margin_top_px'] ?? null,
    'metodo_bottom' => $rs['grupo_cabecera_metodo_margin_bottom_px'] ?? null,
    'area_sep_top' => $rs['grupo_area_separator_margin_top_px'] ?? null,
    'area_sep_bottom' => $rs['grupo_area_separator_margin_bottom_px'] ?? null,
    'subgrupo_gap' => $rs['subgrupo_prueba_gap_px'] ?? null,
    'table_mt' => $rs['table_margin_top_px'] ?? null,
    'title_font_size' => $rs['grupo_cabecera_title_font_size_pt'] ?? null,
], JSON_PRETTY_PRINT) . PHP_EOL;
