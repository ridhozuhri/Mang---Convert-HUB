<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AuditLogModel;
use App\Models\UserModel;

class AuditController extends BaseController
{
    public function index(): string
    {
        $auditModel = new AuditLogModel();
        $userModel  = new UserModel();

        $action   = trim((string) ($this->request->getGet('action') ?? ''));
        $model    = trim((string) ($this->request->getGet('model') ?? ''));
        $userId   = (int) ($this->request->getGet('user_id') ?? 0);
        $dateFrom = trim((string) ($this->request->getGet('date_from') ?? ''));
        $dateTo   = trim((string) ($this->request->getGet('date_to') ?? ''));

        $builder = $auditModel->select('audit_logs.*, users.name as user_name, users.email as user_email')
            ->join('users', 'users.id = audit_logs.user_id', 'left')
            ->orderBy('audit_logs.id', 'DESC');

        if ($action !== '') {
            $builder->where('audit_logs.action', $action);
        }
        if ($model !== '') {
            $builder->where('audit_logs.model', $model);
        }
        if ($userId > 0) {
            $builder->where('audit_logs.user_id', $userId);
        }
        if ($dateFrom !== '') {
            $builder->where('audit_logs.created_at >=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo !== '') {
            $builder->where('audit_logs.created_at <=', $dateTo . ' 23:59:59');
        }

        $rows = $builder->paginate(50);

        $actions = $auditModel->select('action')->distinct()->orderBy('action', 'ASC')->findColumn('action') ?? [];
        $models  = $auditModel->select('model')->distinct()->where('model IS NOT NULL')->orderBy('model', 'ASC')->findColumn('model') ?? [];
        $users   = $userModel->select('id,name,email')->where('is_active', 1)->orderBy('name', 'ASC')->findAll();

        return view('admin/audit/index', [
            'pageTitle'  => 'Audit Log',
            'rows'       => $rows,
            'pager'      => $auditModel->pager,
            'actions'    => $actions,
            'models'     => $models,
            'users'      => $users,
            'filters'    => [
                'action'    => $action,
                'model'     => $model,
                'user_id'   => $userId,
                'date_from' => $dateFrom,
                'date_to'   => $dateTo,
            ],
        ]);
    }
}
