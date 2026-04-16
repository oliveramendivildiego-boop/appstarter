<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RegistroNumeroOrdenFolio extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('registro')) {
            $fields = $this->db->getFieldNames($this->db->prefixTable('registro'));
            if (!in_array('numero_orden', $fields, true)) {
                $this->forge->addColumn('registro', [
                    'numero_orden' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 64,
                        'null'       => true,
                    ],
                ]);
                $t = $this->db->prefixTable('registro');
                try {
                    $this->db->query("ALTER TABLE {$t} ADD UNIQUE INDEX registro_numero_orden_uq (numero_orden)");
                } catch (\Throwable $e) {
                    // Si ya existe o el motor no lo permite, continuar
                }
            }
        }

        if (!$this->db->tableExists('registro_folio_secuencia')) {
            $this->forge->addField([
                'seq_key' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                ],
                'last_num' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'default'    => 0,
                ],
            ]);
            $this->forge->addKey('seq_key', true);
            $this->forge->createTable('registro_folio_secuencia', true);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('registro_folio_secuencia')) {
            $this->forge->dropTable('registro_folio_secuencia', true);
        }
        if ($this->db->tableExists('registro')) {
            $fields = $this->db->getFieldNames($this->db->prefixTable('registro'));
            if (in_array('numero_orden', $fields, true)) {
                $t = $this->db->prefixTable('registro');
                try {
                    $this->db->query("ALTER TABLE {$t} DROP INDEX registro_numero_orden_uq");
                } catch (\Throwable $e) {
                }
                $this->forge->dropColumn('registro', 'numero_orden');
            }
        }
    }
}
