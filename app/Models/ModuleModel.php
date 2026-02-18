<?php

namespace App\Models;

use CodeIgniter\Model;

class ModuleModel extends Model
{
    protected $table      = 'modules';
    protected $primaryKey = 'module_id';
    protected $returnType = 'object';

    public function getAllowedModules(int $person_id): array
    {
        return $this->db->table('modules')
            ->select('modules.*')
            ->join('permissions', 'permissions.module_id = modules.module_id')
            ->where('permissions.person_id', $person_id)
            ->orderBy('modules.sort', 'ASC')
            ->get()
            ->getResult();
    }

    public function getModuleName(string $module_id): string
    {
        $row = $this->find($module_id);
        return $row ? lang($row->name_lang_key ?? 'error_unknown') : lang('error_unknown');
    }
}
