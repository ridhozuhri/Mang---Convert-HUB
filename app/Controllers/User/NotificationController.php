<?php

namespace App\Controllers\User;

use App\Controllers\BaseController;
use App\Models\NotificationModel;

class NotificationController extends BaseController
{
    public function index(): string
    {
        $userId = (int) session()->get('user_id');
        $rows   = (new NotificationModel())
            ->where('user_id', $userId)
            ->orderBy('id', 'DESC')
            ->findAll();

        return view('user/notifications', [
            'pageTitle' => 'Notifikasi',
            'rows'      => $rows,
        ]);
    }

    public function markRead()
    {
        $userId = (int) session()->get('user_id');
        $ids    = $this->request->getPost('ids');
        $ids    = is_array($ids) ? array_map('intval', $ids) : [];

        $model = new NotificationModel();
        if ($ids === []) {
            $model->where('user_id', $userId)->where('is_read', 0)->set([
                'is_read' => 1,
                'read_at' => date('Y-m-d H:i:s'),
            ])->update();
        } else {
            foreach ($ids as $id) {
                $model->where('id', $id)->where('user_id', $userId)->set([
                    'is_read' => 1,
                    'read_at' => date('Y-m-d H:i:s'),
                ])->update();
            }
        }

        return redirect()->back()->with('success', 'Notifikasi ditandai sudah dibaca.');
    }
}
