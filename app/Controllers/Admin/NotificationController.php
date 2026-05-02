<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\AuditLogger;
use App\Libraries\NotificationService;
use App\Models\NotificationModel;

class NotificationController extends BaseController
{
    public function index(): string
    {
        $limit = (int) ($this->request->getGet('limit') ?? 200);
        $model = new NotificationModel();
        $rows = $model->listTransactionForAdmin($limit, true);

        return view('admin/notifications/index', [
            'pageTitle' => 'Notifikasi Transaksi',
            'rows'      => $rows,
            'limit'     => max(1, min($limit, 1000)),
            'unreadCount' => $model->unreadTransactionCount(),
        ]);
    }

    public function markAllRead()
    {
        $model = new NotificationModel();
        $before = $model->unreadTransactionCount();
        $model->markAllTransactionRead();

        AuditLogger::log([
            'action'     => 'notification_mark_all_read',
            'model'      => 'notifications',
            'new_values' => ['type' => 'order_status', 'affected' => $before],
        ]);

        return redirect()->back()->with('success', 'Semua notifikasi transaksi ditandai sudah dibaca.');
    }

    public function blast(): string
    {
        return view('admin/notifications/blast', [
            'pageTitle' => 'Blast Notification',
        ]);
    }

    public function sendBlast()
    {
        $rules = [
            'title' => 'required|min_length[3]|max_length[255]',
            'body'  => 'required|min_length[5]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', $this->validator->getErrors());
        }

        $title = trim((string) $this->request->getPost('title'));
        $body  = trim((string) $this->request->getPost('body'));
        $count = NotificationService::blastToAllUsers('blast', $title, $body);

        AuditLogger::log([
            'action'     => 'notification_blast',
            'model'      => 'notifications',
            'new_values' => ['title' => $title, 'body' => $body, 'count' => $count],
        ]);

        return redirect()->back()->with('success', 'Blast notifikasi terkirim ke ' . $count . ' user.');
    }
}
