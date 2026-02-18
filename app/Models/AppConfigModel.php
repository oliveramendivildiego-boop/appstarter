<?php

namespace App\Models;

use CodeIgniter\Model;

class AppConfigModel extends Model
{
    protected $table            = 'app_config';
    protected $primaryKey       = 'key';
    protected $useAutoIncrement = false;
    protected $returnType       = 'object';
    protected $allowedFields    = ['key', 'value'];

    public function getValue(string $key): string
    {
        $row = $this->find($key);
        return $row ? $row->value : '';
    }

    public function getMultiple(array $keys): array
    {
        if (empty($keys)) return [];
        $rows = $this->whereIn('key', $keys)->findAll();
        $result = [];
        foreach ($rows as $row) {
            $result[$row->key] = $row->value;
        }
        return $result;
    }

    public function saveValue(string $key, string $value): bool
    {
        $existing = $this->find($key);
        $data = ['key' => $key, 'value' => $value];

        if ($existing) {
            return $this->update($key, ['value' => $value]);
        }
        return $this->insert($data) !== false;
    }

    public function batchSave(array $data): bool
    {
        $db = \Config\Database::connect();
        $db->transStart();

        foreach ($data as $key => $value) {
            if (!$this->saveValue($key, (string) $value)) {
                $db->transRollback();
                return false;
            }
        }

        $db->transComplete();
        return $db->transStatus();
    }
}
