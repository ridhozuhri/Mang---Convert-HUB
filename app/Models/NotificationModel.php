<?php

namespace App\Models;

use CodeIgniter\Model;

class NotificationModel extends Model
{
    protected $table            = 'notifications';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'type',
        'title',
        'body',
        'data_json',
        'is_read',
        'read_at',
        'created_at',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;
    protected $useTimestamps          = false;

    public function unreadCount(int $userId): int
    {
        return $this->where('user_id', $userId)->where('is_read', 0)->countAllResults();
    }

    public function listTransactionForAdmin(int $limit = 200, bool $onlyUnread = true): array
    {
        $limit = max(1, min($limit, 1000));

        $builder = $this->select('notifications.*, users.name as user_name, users.email as user_email')
            ->join('users', 'users.id = notifications.user_id', 'left')
            ->where('notifications.type', 'order_status')
            ->orderBy('notifications.id', 'DESC')
            ->limit($limit);

        if ($onlyUnread) {
            $builder->where('notifications.is_read', 0);
        }

        return $builder->findAll();
    }

    public function unreadTransactionCount(): int
    {
        return $this->where('type', 'order_status')
            ->where('is_read', 0)
            ->countAllResults();
    }

    public function markAllTransactionRead(): void
    {
        $this->builder()
            ->where('type', 'order_status')
            ->where('is_read', 0)
            ->set([
                'is_read' => 1,
                'read_at' => date('Y-m-d H:i:s'),
            ])
            ->update();
    }
}
