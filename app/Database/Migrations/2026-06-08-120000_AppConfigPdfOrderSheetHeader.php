<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Activa cabecera Paciente / No. Orden desde la 2.ª hoja (config global por tenant).
 */
class AppConfigPdfOrderSheetHeader extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('app_config')) {
            return;
        }
        $exists = $this->db->table('app_config')->where('key', 'pdf_order_sheet_header_enabled')->countAllResults() > 0;
        if ($exists) {
            return;
        }
        $this->db->table('app_config')->insert([
            'key'   => 'pdf_order_sheet_header_enabled',
            'value' => '0',
        ]);
    }

    public function down()
    {
        if ($this->db->tableExists('app_config')) {
            $this->db->table('app_config')->where('key', 'pdf_order_sheet_header_enabled')->delete();
        }
    }
}
