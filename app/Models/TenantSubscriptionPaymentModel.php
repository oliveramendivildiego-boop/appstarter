<?php

namespace App\Models;

use CodeIgniter\Model;

class TenantSubscriptionPaymentModel extends Model
{
    /** Misma BD central que tenant_configs */
    protected $DBGroup = 'management';

    protected $table            = 'tenant_subscription_payments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $allowedFields    = [
        'tenant_config_id',
        'period_start',
        'period_end',
        'amount',
        'currency',
        'notes',
        'voucher_filename',
    ];
}
