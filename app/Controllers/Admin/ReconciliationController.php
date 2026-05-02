<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AssetLedgerEntryModel;

class ReconciliationController extends BaseController
{
    public function index(): string
    {
        $dateFrom = trim((string) ($this->request->getGet('date_from') ?? date('Y-m-d')));
        $dateTo = trim((string) ($this->request->getGet('date_to') ?? date('Y-m-d')));
        $rows = (new AssetLedgerEntryModel())->summaryByDateRange($dateFrom, $dateTo);

        return view('admin/reconciliation/index', [
            'pageTitle' => 'Reconciliation',
            'rows' => $rows,
            'filters' => ['date_from' => $dateFrom, 'date_to' => $dateTo],
        ]);
    }
}
