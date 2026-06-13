<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTextoFijoOpcionAndColumn extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('opciones')) {
            $exists = $this->db->table('opciones')
                ->where('tabla', 'texto_fijo')
                ->countAllResults();

            if ($exists === 0) {
                $this->db->table('opciones')->insert([
                    'opciones' => 'Texto fijo',
                    'tabla'    => 'texto_fijo',
                ]);
            }
        }

        foreach (['secanacategoria', 'priresultados'] as $tableName) {
            if (! $this->db->tableExists($tableName)) {
                continue;
            }
            if ($this->db->fieldExists('texto_fijo', $tableName)) {
                continue;
            }
            $this->forge->addColumn($tableName, [
                'texto_fijo' => [
                    'type' => 'MEDIUMTEXT',
                    'null' => true,
                ],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists('opciones')) {
            $this->db->table('opciones')->where('tabla', 'texto_fijo')->delete();
        }

        foreach (['secanacategoria', 'priresultados'] as $tableName) {
            if ($this->db->tableExists($tableName) && $this->db->fieldExists('texto_fijo', $tableName)) {
                $this->forge->dropColumn($tableName, 'texto_fijo');
            }
        }
    }
}
