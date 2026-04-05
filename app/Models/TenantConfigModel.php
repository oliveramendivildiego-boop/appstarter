<?php

namespace App\Models;

use CodeIgniter\Model;

class TenantConfigModel extends Model
{
    /** Siempre la conexión `management` (.env), no la del tenant activo */
    protected $DBGroup = 'management';

    protected $table            = 'tenant_configs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'tenant_key',
        'tenant_name',
        'public_base_url',
        'db_host',
        'db_port',
        'db_name',
        'db_user',
        'db_pass',
        'db_prefix',
        'is_active',
        'is_default',
    ];
    protected $useTimestamps = true;
}
