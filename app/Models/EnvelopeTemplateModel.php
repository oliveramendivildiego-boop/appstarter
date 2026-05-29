<?php

namespace App\Models;

use CodeIgniter\Model;

class EnvelopeTemplateModel extends Model
{
    protected $table            = 'envelope_templates';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $allowedFields    = ['name', 'layout_json'];
}
