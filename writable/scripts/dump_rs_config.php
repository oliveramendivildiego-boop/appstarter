<?php
declare(strict_types=1);
$_SERVER['CI_ENVIRONMENT']='development';
define('ENVIRONMENT','development');
define('FCPATH', dirname(__DIR__,2).'/public/');
chdir(FCPATH);
require FCPATH.'../app/Config/Paths.php';
$paths=new Config\Paths();
require $paths->systemDirectory.'/Boot.php';
CodeIgniter\Boot::bootConsole($paths);
$layout=(new App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$rs=App\Services\ReportPdfLayoutService::normalizeResultsTableStyle($layout['page_style']['results_table']??[]);
echo json_encode([
    'cell_padding_v_px'=>$rs['cell_padding_v_px']??null,
    'table_margin_top_px'=>$rs['table_margin_top_px']??null,
    'subgrupo_prueba_gap_px'=>$rs['subgrupo_prueba_gap_px']??null,
    'subgrupoPruebaGapPx'=>App\Services\ReportPdfLayoutService::subgrupoPruebaGapPx($layout),
], JSON_PRETTY_PRINT);
