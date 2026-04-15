<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEgresosModule extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('modules')) {
            return;
        }

        $exists = $this->db->table('modules')
            ->where('module_id', 'egresos')
            ->countAllResults();

        if ($exists === 0) {
            $this->db->table('modules')->insert([
                'name_lang_key' => 'module_egresos',
                'desc_lang_key' => 'module_egresos_desc',
                'sort'          => 60,
                'module_id'     => 'egresos',
            ]);
        }

        if ($this->db->tableExists('permissions')) {
            $permExists = $this->db->table('permissions')
                ->where('module_id', 'egresos')
                ->where('person_id', 1)
                ->countAllResults();

            if ($permExists === 0) {
                $this->db->table('permissions')->insert([
                    'module_id' => 'egresos',
                    'person_id' => 1,
                ]);
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('permissions')) {
            $this->db->table('permissions')
                ->where('module_id', 'egresos')
                ->delete();
        }

        if ($this->db->tableExists('modules')) {
            $this->db->table('modules')
                ->where('module_id', 'egresos')
                ->delete();
        }
    }
}

