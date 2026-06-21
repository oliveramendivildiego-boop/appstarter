<?php
declare(strict_types=1);
$_SERVER['CI_ENVIRONMENT']='development'; define('ENVIRONMENT','development');
define('FCPATH', dirname(__DIR__,2).'/public/'); chdir(FCPATH);
require FCPATH.'../app/Config/Paths.php'; $p=new Config\Paths(); require $p->systemDirectory.'/Boot.php'; CodeIgniter\Boot::bootConsole($p);
$layout=(new App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$ps=$layout['page_style']??[];
echo 'pagination_mode: '.(\App\Services\ReportPdfLayoutService::resolvePaginationModeFromLayout($layout))."\n";
echo 'legacy gpb mode: '.(($ps['grupo_prueba_page_break']['mode']??'(none)'))."\n";
echo 'segmentIntactStyle: '.(\App\Services\ReportPdfLayoutService::grupoPruebaSegmentIntactStyleAttr($layout)?:'(vacío)')."\n";
echo 'bodyClass: '.(\App\Services\ReportPdfLayoutService::grupoPruebaPageBreakBodyClass($layout))."\n";
