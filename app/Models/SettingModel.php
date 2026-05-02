<?php

namespace App\Models;

use CodeIgniter\Model;

class SettingModel extends Model
{
    protected $table            = 'settings';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'group',
        'key',
        'value',
        'type',
        'label',
        'description',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getCached(string $group, string $key): mixed
    {
        $cacheKey = "setting_{$group}_{$key}";
        $cached = cache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $row = $this->where('group', $group)->where('key', $key)->first();
        $value = is_array($row) ? $this->castValue($row['value'] ?? null, $row['type'] ?? 'string') : null;
        cache()->save($cacheKey, $value, 300);

        return $value;
    }

    public function setValue(string $group, string $key, mixed $value): void
    {
        $row = $this->where('group', $group)->where('key', $key)->first();
        $data = [
            'value'      => is_scalar($value) || $value === null ? (string) $value : json_encode($value),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if (is_array($row)) {
            $this->update((int) $row['id'], $data);
        } else {
            $this->insert([
                'group'      => $group,
                'key'        => $key,
                'value'      => $data['value'],
                'type'       => 'string',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
        cache()->delete("setting_{$group}_{$key}");
    }

    public function getValue(string $group, string $key, mixed $default = null): mixed
    {
        $value = $this->getCached($group, $key);
        if ($value === null) {
            return $default;
        }

        return $value;
    }

    public function getGroupMap(string $group): array
    {
        $rows = $this->where('group', $group)->findAll();
        $map  = [];

        foreach ($rows as $row) {
            $map[$row['key']] = $this->castValue($row['value'] ?? null, $row['type'] ?? 'string');
            cache()->save("setting_{$group}_{$row['key']}", $map[$row['key']], 300);
        }

        return $map;
    }

    private function castValue(mixed $value, string $type, mixed $default = null): mixed
    {
        if ($value === null) {
            return $default;
        }

        return match ($type) {
            'bool'   => in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true),
            'int'    => (int) $value,
            'json'   => json_decode((string) $value, true) ?? $default,
            default  => $value,
        };
    }
}
