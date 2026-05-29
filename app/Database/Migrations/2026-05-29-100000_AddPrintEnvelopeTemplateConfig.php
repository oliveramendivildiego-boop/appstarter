<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPrintEnvelopeTemplateConfig extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('app_config')) {
            return;
        }

        $exists = $this->db->table('app_config')->where('key', 'print_envelope_template_id')->countAllResults() > 0;
        if ($exists) {
            return;
        }

        $default = '1';
        $active  = $this->db->table('app_config')->where('key', 'active_envelope_template_id')->get()->getRowArray();
        if (is_array($active) && trim((string) ($active['value'] ?? '')) !== '') {
            $default = trim((string) $active['value']);
        }

        $this->db->table('app_config')->insert([
            'key'   => 'print_envelope_template_id',
            'value' => $default,
        ]);
    }

    public function down()
    {
        if ($this->db->tableExists('app_config')) {
            $this->db->table('app_config')->where('key', 'print_envelope_template_id')->delete();
        }
    }
}
