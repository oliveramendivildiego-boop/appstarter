<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddHomeModule extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('modules')) {
            return;
        }

        $exists = $this->db->table('modules')
            ->where('module_id', 'home')
            ->countAllResults();

        if ($exists === 0) {
            $this->db->table('modules')->insert([
                'name_lang_key' => 'module_home',
                'desc_lang_key' => 'module_home_desc',
                'sort'          => 1,
                'module_id'     => 'home',
            ]);
        }

        if (!$this->db->tableExists('permissions')) {
            return;
        }

        // Compatibilidad: quien ya tiene algún permiso conserva acceso al dashboard
        $personIds = $this->db->table('permissions')
            ->select('person_id')
            ->distinct()
            ->get()
            ->getResultArray();

        foreach ($personIds as $row) {
            $personId = (int) ($row['person_id'] ?? 0);
            if ($personId < 1) {
                continue;
            }
            $hasHome = $this->db->table('permissions')
                ->where('person_id', $personId)
                ->where('module_id', 'home')
                ->countAllResults();
            if ($hasHome === 0) {
                $this->db->table('permissions')->insert([
                    'module_id' => 'home',
                    'person_id' => $personId,
                ]);
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('permissions')) {
            $this->db->table('permissions')
                ->where('module_id', 'home')
                ->delete();
        }

        if ($this->db->tableExists('modules')) {
            $this->db->table('modules')
                ->where('module_id', 'home')
                ->delete();
        }
    }
}
