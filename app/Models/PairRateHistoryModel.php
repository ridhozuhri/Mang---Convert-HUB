<?php

namespace App\Models;

use CodeIgniter\Model;

class PairRateHistoryModel extends Model
{
    protected $table            = 'pair_rate_history';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'pair_id',
        'rate',
        'changed_by',
        'change_reason',
        'created_at',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;
    protected $useTimestamps          = false;

    public function getByPair(int $pairId, ?int $rangeDays = null): array
    {
        $builder = $this->select('pair_rate_history.*, users.name as changed_by_name')
            ->join('users', 'users.id = pair_rate_history.changed_by', 'left')
            ->where('pair_rate_history.pair_id', $pairId)
            ->orderBy('pair_rate_history.created_at', 'DESC');

        if ($rangeDays !== null && $rangeDays > 0) {
            $threshold = date('Y-m-d H:i:s', strtotime('-' . $rangeDays . ' days'));
            $builder->where('pair_rate_history.created_at >=', $threshold);
        }

        return $builder->findAll();
    }
}
