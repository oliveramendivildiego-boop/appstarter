<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Amplía app_config.value: JSON de validadores/aprobadores y otras claves
 * superan fácilmente 255 caracteres; con VARCHAR truncado el JSON queda
 * inválido y las listas en Config → Validación se vacían al guardar.
 */
class AppConfigValueMediumtext extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('app_config')) {
            return;
        }
        $table = $this->db->prefixTable('app_config');
        $this->db->query("ALTER TABLE `{$table}` MODIFY `value` MEDIUMTEXT NOT NULL");
    }

    public function down()
    {
        if (! $this->db->tableExists('app_config')) {
            return;
        }
        $table = $this->db->prefixTable('app_config');
        $this->db->query("ALTER TABLE `{$table}` MODIFY `value` VARCHAR(255) NOT NULL");
    }
}
