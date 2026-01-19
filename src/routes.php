<?php

use ITSS\Core\Request;
use ITSS\Core\Response;

$config = require __DIR__ . '/../config/config.php';

$authController = new \ITSS\Modules\Auth\AuthController($config);
$router->get('/auth/login', [$authController, 'login']);
$router->get('/auth/callback', [$authController, 'callback']);
$router->get('/auth/logout', [$authController, 'logout']);
$router->get('/auth/check', [$authController, 'check']);

$router->get('/dashboard', function(Request $req, Response $res) {
    require __DIR__ . '/../views/dashboard.php';
});

$router->get('/projects', function(Request $req, Response $res) {
    require __DIR__ . '/../views/projects/list.php';
});

$router->get('/projects/{id}', function(Request $req, Response $res, $id) {
    require __DIR__ . '/../views/projects/detail.php';
});

$router->get('/invoices', function(Request $req, Response $res) {
    require __DIR__ . '/../views/invoices/list.php';
});

$router->get('/invoices/create', function(Request $req, Response $res) {
    require __DIR__ . '/../views/invoices/create.php';
});

$router->get('/invoices/import', function(Request $req, Response $res) {
    require __DIR__ . '/../views/invoices/import.php';
});

$router->get('/invoices/{id}', function(Request $req, Response $res, $id) {
    require __DIR__ . '/../views/invoices/detail.php';
});

$router->get('/documents', function(Request $req, Response $res) {
    require __DIR__ . '/../views/documents/list.php';
});

$router->get('/leaves', function(Request $req, Response $res) {
    require __DIR__ . '/../views/leaves/list.php';
});

$router->get('/leaves/create', function(Request $req, Response $res) {
    require __DIR__ . '/../views/leaves/create.php';
});

$router->get('/leaves/{id}', function(Request $req, Response $res, $id) {
    require __DIR__ . '/../views/leaves/detail.php';
});

$router->get('/bonuses', function(Request $req, Response $res) {
    require __DIR__ . '/../views/bonuses/list.php';
});

$router->get('/bonuses/schemes', function(Request $req, Response $res) {
    require __DIR__ . '/../views/bonuses/schemes.php';
});

$router->get('/bonuses/calculate', function(Request $req, Response $res) {
    require __DIR__ . '/../views/bonuses/calculate.php';
});

$router->get('/czasomat', function(Request $req, Response $res) {
    require __DIR__ . '/../views/czasomat.php';
});

$router->get('/api/projects', function(Request $req, Response $res) use ($config) {
    $projectModel = new \ITSS\Models\Project();
    $status = $req->query('status');
    $projects = $projectModel->getAll($status);
    $res->json(['success' => true, 'data' => $projects]);
});

$router->post('/api/projects', function(Request $req, Response $res) {
    $projectModel = new \ITSS\Models\Project();
    $data = $req->input();

    try {
        $id = $projectModel->create($data);
        $res->json(['success' => true, 'id' => $id]);
    } catch (\Exception $e) {
        $res->status(400)->json(['success' => false, 'error' => $e->getMessage()]);
    }
});

$router->get('/api/projects/{id}', function(Request $req, Response $res, $id) {
    $projectModel = new \ITSS\Models\Project();
    $project = $projectModel->findById($id);

    if (!$project) {
        $res->status(404)->json(['success' => false, 'error' => 'Project not found']);
        return;
    }

    $res->json(['success' => true, 'data' => $project]);
});

$router->get('/api/projects/{id}/financials', function(Request $req, Response $res, $id) {
    $projectModel = new \ITSS\Models\Project();
    $financials = $projectModel->getProjectFinancials($id);
    $res->json(['success' => true, 'data' => $financials]);
});

$router->get('/api/invoices', function(Request $req, Response $res) {
    $invoiceModel = new \ITSS\Models\Invoice();
    $type = $req->query('type');
    $projectId = $req->query('project_id');
    $invoices = $invoiceModel->getAll($type, $projectId);
    $res->json(['success' => true, 'data' => $invoices]);
});

$router->post('/api/invoices', function(Request $req, Response $res) {
    $invoiceModel = new \ITSS\Models\Invoice();
    $data = $req->input();
    $userId = \ITSS\Core\Session::get('user_id');

    try {
        $id = $invoiceModel->create($data, $userId);
        $res->json(['success' => true, 'id' => $id]);
    } catch (\Exception $e) {
        $res->status(400)->json(['success' => false, 'error' => $e->getMessage()]);
    }
});

$router->post('/api/invoices/{id}/mark-paid', function(Request $req, Response $res, $id) {
    $invoiceModel = new \ITSS\Models\Invoice();
    $paymentDate = $req->input('payment_date');

    try {
        $invoiceModel->markAsPaid($id, $paymentDate);
        $res->json(['success' => true]);
    } catch (\Exception $e) {
        $res->status(400)->json(['success' => false, 'error' => $e->getMessage()]);
    }
});

$router->get('/api/leaves', function(Request $req, Response $res) {
    $leaveModel = new \ITSS\Models\LeaveRequest();
    $userId = \ITSS\Core\Session::get('user_id');
    $userRole = \ITSS\Core\Session::get('user_role');

    if ($userRole === 'admin') {
        $status = $req->query('status');
        $leaves = $leaveModel->getAll($status);
    } elseif ($userRole === 'team_leader') {
        $leaves = $leaveModel->getPendingForTeamLeader($userId);
    } elseif (in_array($userRole, ['manager', 'director'])) {
        $leaves = $leaveModel->getPendingForManager($userId);
    } else {
        $leaves = $leaveModel->getByUser($userId);
    }

    $res->json(['success' => true, 'data' => $leaves]);
});

$router->post('/api/leaves', function(Request $req, Response $res) {
    $leaveModel = new \ITSS\Models\LeaveRequest();
    $userId = \ITSS\Core\Session::get('user_id');
    $data = $req->input();

    try {
        $id = $leaveModel->create($data, $userId);
        $res->json(['success' => true, 'id' => $id]);
    } catch (\Exception $e) {
        $res->status(400)->json(['success' => false, 'error' => $e->getMessage()]);
    }
});

$router->post('/api/leaves/{id}/approve-team-leader', function(Request $req, Response $res, $id) {
    $leaveModel = new \ITSS\Models\LeaveRequest();
    $userId = \ITSS\Core\Session::get('user_id');
    $comment = $req->input('comment');

    try {
        $leaveModel->approveByTeamLeader($id, $userId, $comment);
        $res->json(['success' => true]);
    } catch (\Exception $e) {
        $res->status(400)->json(['success' => false, 'error' => $e->getMessage()]);
    }
});

$router->post('/api/leaves/{id}/approve-manager', function(Request $req, Response $res, $id) {
    $leaveModel = new \ITSS\Models\LeaveRequest();
    $userId = \ITSS\Core\Session::get('user_id');
    $comment = $req->input('comment');

    try {
        $leaveModel->approveByManager($id, $userId, $comment);
        $res->json(['success' => true]);
    } catch (\Exception $e) {
        $res->status(400)->json(['success' => false, 'error' => $e->getMessage()]);
    }
});

$router->post('/api/leaves/{id}/reject', function(Request $req, Response $res, $id) {
    $leaveModel = new \ITSS\Models\LeaveRequest();
    $userId = \ITSS\Core\Session::get('user_id');
    $comment = $req->input('comment');

    try {
        $leaveModel->reject($id, $userId, $comment);
        $res->json(['success' => true]);
    } catch (\Exception $e) {
        $res->status(400)->json(['success' => false, 'error' => $e->getMessage()]);
    }
});

$router->get('/api/bonuses/schemes', function(Request $req, Response $res) {
    $bonusSchemeModel = new \ITSS\Models\BonusScheme();
    $userId = $req->query('user_id');
    $projectId = $req->query('project_id');

    if ($userId) {
        $schemes = $bonusSchemeModel->getByUser($userId);
    } elseif ($projectId) {
        $schemes = $bonusSchemeModel->getByProject($projectId);
    } else {
        $schemes = $bonusSchemeModel->getAll();
    }

    $res->json(['success' => true, 'data' => $schemes]);
});

$router->post('/api/bonuses/schemes', function(Request $req, Response $res) {
    $bonusSchemeModel = new \ITSS\Models\BonusScheme();
    $data = $req->input();

    try {
        $id = $bonusSchemeModel->create($data);
        $res->json(['success' => true, 'id' => $id]);
    } catch (\Exception $e) {
        $res->status(400)->json(['success' => false, 'error' => $e->getMessage()]);
    }
});

$router->post('/api/bonuses/calculate', function(Request $req, Response $res) use ($config) {
    $bonusService = new \ITSS\Services\BonusCalculationService();
    $userId = $req->input('user_id');
    $periodStart = $req->input('period_start');
    $periodEnd = $req->input('period_end');
    $projectId = $req->input('project_id');

    try {
        if ($userId) {
            $bonuses = $bonusService->calculateBonusForUser($userId, $periodStart, $periodEnd, $projectId);
        } else {
            $bonuses = $bonusService->calculateBonusesForPeriod($periodStart, $periodEnd);
        }
        $res->json(['success' => true, 'data' => $bonuses]);
    } catch (\Exception $e) {
        $res->status(400)->json(['success' => false, 'error' => $e->getMessage()]);
    }
});

$router->get('/api/bonuses/calculated', function(Request $req, Response $res) {
    $calculatedBonusModel = new \ITSS\Models\CalculatedBonus();
    $userId = $req->query('user_id');
    $status = $req->query('status');

    if ($userId) {
        $bonuses = $calculatedBonusModel->getByUser($userId, $status);
    } else {
        $bonuses = $calculatedBonusModel->getAll($status);
    }

    $res->json(['success' => true, 'data' => $bonuses]);
});

$router->post('/api/sync/crm', function(Request $req, Response $res) use ($config) {
    try {
        $crmService = new \ITSS\Services\DynamicsCRMService($config['dynamics_crm']);
        $count = $crmService->syncProjects();
        $res->json(['success' => true, 'synced_count' => $count]);
    } catch (\Exception $e) {
        $res->status(500)->json(['success' => false, 'error' => $e->getMessage()]);
    }
});

$router->post('/api/sync/servicedesk', function(Request $req, Response $res) use ($config) {
    try {
        $serviceDeskService = new \ITSS\Services\ServiceDeskService($config['servicedesk']);
        $hoursCount = $serviceDeskService->syncWorkHours();
        $ticketsCount = $serviceDeskService->syncHelpdeskTickets();
        $res->json([
            'success' => true,
            'work_hours_synced' => $hoursCount,
            'tickets_synced' => $ticketsCount
        ]);
    } catch (\Exception $e) {
        $res->status(500)->json(['success' => false, 'error' => $e->getMessage()]);
    }
});

$router->post('/api/documents/upload', function(Request $req, Response $res) use ($config) {
    if (!$req->hasFile('document')) {
        $res->status(400)->json(['success' => false, 'error' => 'No file uploaded']);
        return;
    }

    $file = $req->file('document');
    $documentType = $req->post('document_type');
    $projectId = $req->post('project_id');
    $invoiceId = $req->post('invoice_id');

    $uploadPath = $config['upload']['documents_path'];
    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0755, true);
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $uploadPath . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        $res->status(500)->json(['success' => false, 'error' => 'Failed to save file']);
        return;
    }

    $documentModel = new \ITSS\Models\Document();
    $userId = \ITSS\Core\Session::get('user_id');

    try {
        $id = $documentModel->create([
            'document_name' => $file['name'],
            'document_type' => $documentType,
            'file_path' => $filepath,
            'file_size' => $file['size'],
            'mime_type' => $file['type'],
            'project_id' => $projectId ?: null,
            'invoice_id' => $invoiceId ?: null,
            'description' => $req->post('description')
        ], $userId);

        $res->json(['success' => true, 'id' => $id]);
    } catch (\Exception $e) {
        unlink($filepath);
        $res->status(400)->json(['success' => false, 'error' => $e->getMessage()]);
    }
});

$router->post('/api/invoices/import', function(Request $req, Response $res) use ($config) {
    if (!$req->hasFile('csv_file')) {
        $res->status(400)->json(['success' => false, 'error' => 'No CSV file uploaded']);
        return;
    }

    $file = $req->file('csv_file');
    $invoiceType = $req->post('invoice_type', 'revenue');
    $delimiter = $req->post('delimiter', ';');

    $uploadPath = $config['upload']['documents_path'];
    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0755, true);
    }

    $tempPath = $uploadPath . '/import_' . uniqid() . '.csv';

    if (!move_uploaded_file($file['tmp_name'], $tempPath)) {
        $res->status(500)->json(['success' => false, 'error' => 'Failed to save file']);
        return;
    }

    $userId = \ITSS\Core\Session::get('user_id');
    $importService = new \ITSS\Services\InvoiceImportService();

    try {
        $result = $importService->importFromCSV($tempPath, $userId, $invoiceType, $delimiter);
        unlink($tempPath);
        $res->json($result);
    } catch (\Exception $e) {
        if (file_exists($tempPath)) {
            unlink($tempPath);
        }
        $res->status(500)->json(['success' => false, 'error' => $e->getMessage()]);
    }
});

$router->get('/api/invoices/{id}/items', function(Request $req, Response $res, $id) {
    $invoiceItemModel = new \ITSS\Models\InvoiceItem();
    $items = $invoiceItemModel->getByInvoice($id);
    $res->json(['success' => true, 'data' => $items]);
});

$router->post('/api/invoices/{id}/items', function(Request $req, Response $res, $id) {
    $invoiceItemModel = new \ITSS\Models\InvoiceItem();
    $data = $req->input();
    $data['invoice_id'] = $id;

    try {
        $itemId = $invoiceItemModel->create($data);
        $res->json(['success' => true, 'id' => $itemId]);
    } catch (\Exception $e) {
        $res->status(400)->json(['success' => false, 'error' => $e->getMessage()]);
    }
});

$router->get('/api/projects/{id}/costs', function(Request $req, Response $res, $id) {
    $projectCostModel = new \ITSS\Models\ProjectCost();
    $costs = $projectCostModel->getByProject($id);
    $res->json(['success' => true, 'data' => $costs]);
});

$router->get('/api/projects/{id}/revenues', function(Request $req, Response $res, $id) {
    $projectRevenueModel = new \ITSS\Models\ProjectRevenue();
    $revenues = $projectRevenueModel->getByProject($id);
    $res->json(['success' => true, 'data' => $revenues]);
});
