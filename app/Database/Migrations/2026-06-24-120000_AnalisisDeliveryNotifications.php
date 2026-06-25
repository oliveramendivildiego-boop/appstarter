<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AnalisisDeliveryNotifications extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('registro')) {
            $fields = $this->db->getFieldNames($this->db->prefixTable('registro'));
            if (! in_array('notificar_entrega', $fields, true)) {
                $this->forge->addColumn('registro', [
                    'notificar_entrega' => [
                        'type'       => 'TINYINT',
                        'constraint' => 1,
                        'default'    => 0,
                        'null'       => false,
                        'comment'    => '1=generar alerta de entrega cuando el análisis esté listo',
                    ],
                ]);
            }
        }

        if ($this->db->tableExists('analisis_delivery_notification')) {
            return;
        }

        $this->forge->addField([
            'notification_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'registro_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'prianacategoria_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'comment'    => 'ID del análisis (prueba) en catálogo',
            ],
            'validated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 16,
                'default'    => 'pending',
            ],
            'attended_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'attended_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('notification_id', true);
        $this->forge->addKey(['status', 'validated_at']);
        $this->forge->addKey('registro_id');
        $this->forge->addKey('prianacategoria_id');
        $this->forge->addUniqueKey(['registro_id', 'prianacategoria_id', 'status'], 'uq_delivery_notif_reg_pria_status');
        $this->forge->createTable('analisis_delivery_notification', true);
    }

    public function down(): void
    {
        if ($this->db->tableExists('analisis_delivery_notification')) {
            $this->forge->dropTable('analisis_delivery_notification', true);
        }
        if ($this->db->tableExists('registro')) {
            $fields = $this->db->getFieldNames($this->db->prefixTable('registro'));
            if (in_array('notificar_entrega', $fields, true)) {
                $this->forge->dropColumn('registro', 'notificar_entrega');
            }
        }
    }
}
