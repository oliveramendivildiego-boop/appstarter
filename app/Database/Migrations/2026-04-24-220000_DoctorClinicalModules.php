<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DoctorClinicalModules extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('registro')) {
            $fields = $this->db->getFieldNames($this->db->prefixTable('registro'));
            $toAdd = [];
            if (!in_array('diagnostico_presuntivo', $fields, true)) {
                $toAdd['diagnostico_presuntivo'] = ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true];
            }
            if (!in_array('motivo_estudio', $fields, true)) {
                $toAdd['motivo_estudio'] = ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true];
            }
            if ($toAdd !== []) {
                $this->forge->addColumn('registro', $toAdd);
            }
        }

        if ($this->db->tableExists('doctors')) {
            $fields = $this->db->getFieldNames($this->db->prefixTable('doctors'));
            $toAdd = [];
            if (!in_array('clinical_alerts_enabled', $fields, true)) {
                $toAdd['clinical_alerts_enabled'] = ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1];
            }
            if (!in_array('clinical_alert_email', $fields, true)) {
                $toAdd['clinical_alert_email'] = ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1];
            }
            if (!in_array('clinical_alert_whatsapp', $fields, true)) {
                $toAdd['clinical_alert_whatsapp'] = ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0];
            }
            if ($toAdd !== []) {
                $this->forge->addColumn('doctors', $toAdd);
            }
        }

        if (!$this->db->tableExists('doctor_clinical_alert_logs')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'doctor_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'registro_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'channel' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => false],
                'severity' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => false],
                'message' => ['type' => 'TEXT', 'null' => true],
                'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'pending'],
                'sent_at' => ['type' => 'DATETIME', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['doctor_id', 'registro_id']);
            $this->forge->createTable('doctor_clinical_alert_logs', true);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('doctor_clinical_alert_logs')) {
            $this->forge->dropTable('doctor_clinical_alert_logs', true);
        }

        if ($this->db->tableExists('doctors')) {
            $fields = $this->db->getFieldNames($this->db->prefixTable('doctors'));
            foreach (['clinical_alerts_enabled', 'clinical_alert_email', 'clinical_alert_whatsapp'] as $field) {
                if (in_array($field, $fields, true)) {
                    $this->forge->dropColumn('doctors', $field);
                }
            }
        }

        if ($this->db->tableExists('registro')) {
            $fields = $this->db->getFieldNames($this->db->prefixTable('registro'));
            foreach (['diagnostico_presuntivo', 'motivo_estudio'] as $field) {
                if (in_array($field, $fields, true)) {
                    $this->forge->dropColumn('registro', $field);
                }
            }
        }
    }
}
