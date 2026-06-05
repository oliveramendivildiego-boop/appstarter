<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ExpandRegvaluesValueColumn extends Migration
{
    public function up(): void
    {
        $table = $this->db->prefixTable('regvalues');
        if (! $this->db->fieldExists('regvalues', 'regvalues')) {
            return;
        }
        $this->db->query("ALTER TABLE `{$table}` MODIFY `regvalues` MEDIUMTEXT NOT NULL");
    }

    public function down(): void
    {
        $table = $this->db->prefixTable('regvalues');
        if (! $this->db->fieldExists('regvalues', 'regvalues')) {
            return;
        }
        $this->db->query("ALTER TABLE `{$table}` MODIFY `regvalues` VARCHAR(255) NOT NULL");
    }
}
