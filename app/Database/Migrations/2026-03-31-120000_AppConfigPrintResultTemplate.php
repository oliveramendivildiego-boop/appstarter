<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Plantilla distinta para vista de impresión del reporte (HTML) vs PDF generado.
 */
class AppConfigPrintResultTemplate extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('app_config')) {
            return;
        }
        $exists = $this->db->table('app_config')->where('key', 'print_result_template_id')->countAllResults() > 0;
        if ($exists) {
            return;
        }
        $pdfId = '1';
        try {
            $row = $this->db->table('app_config')->where('key', 'pdf_result_template_id')->get()->getRowArray();
            if (! empty($row['value'])) {
                $pdfId = (string) $row['value'];
            }
        } catch (\Throwable $e) {
            // usar 1
        }
        $this->db->table('app_config')->insert([
            'key'   => 'print_result_template_id',
            'value' => $pdfId,
        ]);
    }

    public function down()
    {
        if ($this->db->tableExists('app_config')) {
            $this->db->table('app_config')->where('key', 'print_result_template_id')->delete();
        }
    }
}
