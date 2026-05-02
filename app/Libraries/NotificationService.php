<?php

namespace App\Libraries;

use App\Models\NotificationModel;
use App\Models\UserModel;
use Config\Database;

class NotificationService
{
    public static function sendToUser(int $userId, string $type, string $title, string $body, ?array $data = null): bool
    {
        $model = new NotificationModel();
        return (bool) $model->insert([
            'user_id'    => $userId,
            'type'       => $type,
            'title'      => $title,
            'body'       => $body,
            'data_json'  => $data !== null ? json_encode($data) : null,
            'is_read'    => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function blastToAllUsers(string $type, string $title, string $body, ?array $data = null): int
    {
        return self::sendToRoles(['admin', 'staff', 'viewer', 'user'], $type, $title, $body, $data);
    }

    public static function sendToRoles(array $roles, string $type, string $title, string $body, ?array $data = null): int
    {
        $db = Database::connect();
        if (! $db->tableExists('notifications')) {
            return 0;
        }

        $userModel = new UserModel();
        $users = $userModel
            ->select('id')
            ->whereIn('role', $roles)
            ->where('is_active', 1)
            ->findAll();

        if ($users === []) {
            return 0;
        }

        $rows = [];
        $now = date('Y-m-d H:i:s');
        foreach ($users as $user) {
            $rows[] = [
                'user_id'    => (int) $user['id'],
                'type'       => $type,
                'title'      => $title,
                'body'       => $body,
                'data_json'  => $data !== null ? json_encode($data) : null,
                'is_read'    => 0,
                'created_at' => $now,
            ];
        }

        if ($rows === []) {
            return 0;
        }

        $db->table('notifications')->insertBatch($rows);

        return count($rows);
    }
}
