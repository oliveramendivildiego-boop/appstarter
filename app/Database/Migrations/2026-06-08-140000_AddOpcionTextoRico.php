<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOpcionTextoRico extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('opciones')) {
            return;
        }

        $exists = $this->db->table('opciones')
            ->where('tabla', 'texto_rico')
            ->countAllResults();

        if ($exists > 0) {
            return;
        }

        $this->db->table('opciones')->insert([
            'opciones' => 'Texto enriquecido',
            'tabla'    => 'texto_rico',
        ]);
    }

    public function down(): void
    {
        if (! $this->db->tableExists('opciones')) {
            return;
        }

        $this->db->table('opciones')->where('tabla', 'texto_rico')->delete();
    }
}
