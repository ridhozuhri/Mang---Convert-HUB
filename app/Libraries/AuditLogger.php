<?php

namespace App\Libraries;

use App\Models\AuditLogModel;

class AuditLogger
{
    public static function log(array $payload): void
    {
        $session = session();
        $request = service('request');

        $data = [
            'user_id'    => $payload['user_id'] ?? $session->get('user_id'),
            'action'     => $payload['action'] ?? 'unknown',
            'model'      => $payload['model'] ?? null,
            'model_id'   => $payload['model_id'] ?? null,
            'old_values' => isset($payload['old_values']) ? json_encode($payload['old_values']) : null,
            'new_values' => isset($payload['new_values']) ? json_encode($payload['new_values']) : null,
            'ip_address' => $payload['ip_address'] ?? $request->getIPAddress(),
            'user_agent' => $payload['user_agent'] ?? substr((string) $request->getUserAgent(), 0, 255),
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $model = new AuditLogModel();
        $model->insert($data);
    }
}
