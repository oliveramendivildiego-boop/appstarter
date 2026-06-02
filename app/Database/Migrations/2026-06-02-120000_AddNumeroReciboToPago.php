<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddNumeroReciboToPago extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('pago')) {
            return;
        }
        if ($this->db->fieldExists('numero_recibo', 'pago')) {
            return;
        }
        $this->forge->addColumn('pago', [
            'numero_recibo' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
                'null'       => true,
                'after'      => 'registro_id',
            ],
        ]);
    }

    public function down(): void
    {
        if ($this->db->tableExists('pago') && $this->db->fieldExists('numero_recibo', 'pago')) {
            $this->forge->dropColumn('pago', 'numero_recibo');
        }
    }
}
