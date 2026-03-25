<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLeyendasAndModule extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('leyendas')) {
            $this->forge->addField([
                'leyenda_id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'titulo' => ['type' => 'VARCHAR', 'constraint' => 255],
                'mensaje' => ['type' => 'TEXT'],
                'activo' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'deleted' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('leyenda_id', true);
            $this->forge->createTable('leyendas', true);
        }

        // Registrar módulo en tabla modules para permisos
        if ($this->db->tableExists('modules')) {
            $exists = $this->db->table('modules')->where('module_id', 'leyendas')->countAllResults() > 0;
            if (!$exists) {
                $fields = $this->db->getFieldNames($this->db->prefixTable('modules'));
                $data = ['module_id' => 'leyendas'];
                if (in_array('name_lang_key', $fields, true)) $data['name_lang_key'] = 'module_leyendas';
                if (in_array('desc_lang_key', $fields, true)) $data['desc_lang_key'] = 'module_leyendas_desc';
                if (in_array('sort', $fields, true)) $data['sort'] = 95;
                $this->db->table('modules')->insert($data);
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('modules')) {
            $this->db->table('modules')->where('module_id', 'leyendas')->delete();
        }
        if ($this->db->tableExists('leyendas')) {
            $this->forge->dropTable('leyendas', true);
        }
    }
}

