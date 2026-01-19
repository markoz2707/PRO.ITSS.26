<?php
use ITSS\Models\Project;
use ITSS\Models\Invoice;
use ITSS\Models\Document;
use ITSS\Models\WorkHour;

$projectId = $id ?? $_GET['id'] ?? null;

if (!$projectId) {
    header('Location: /projects');
    exit;
}

$projectModel = new Project();
$invoiceModel = new Invoice();
$documentModel = new Document();
$workHourModel = new WorkHour();

$project = $projectModel->findById($projectId);

if (!$project) {
    header('Location: /projects');
    exit;
}

$financials = $projectModel->getProjectFinancials($projectId);
$invoices = $invoiceModel->getByProject($projectId);
$documents = $documentModel->getByProject($projectId);
$workHours = $workHourModel->getSummaryByProject($projectId);

$pageTitle = $project['project_name'] . ' - ITSS Project Management';
ob_start();
?>

<h2><?php echo htmlspecialchars($project['project_name']); ?></h2>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-value"><?php echo number_format($financials['total_revenue'], 2, ',', ' '); ?> PLN</div>
        <div class="stat-label">Przychody</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo number_format($financials['total_costs'], 2, ',', ' '); ?> PLN</div>
        <div class="stat-label">Koszty</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color: <?php echo $financials['margin_1'] >= 0 ? '#28a745' : '#dc3545'; ?>">
            <?php echo number_format($financials['margin_1'], 2, ',', ' '); ?> PLN
        </div>
        <div class="stat-label">Marża 1 (<?php echo number_format($financials['margin_1_percent'], 1); ?>%)</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color: <?php echo $financials['margin_2'] >= 0 ? '#28a745' : '#dc3545'; ?>">
            <?php echo number_format($financials['margin_2'], 2, ',', ' '); ?> PLN
        </div>
        <div class="stat-label">Marża 2 (<?php echo number_format($financials['margin_2_percent'], 1); ?>%)</div>
    </div>
</div>

<div class="card">
    <div class="card-header">Informacje o projekcie</div>
    <table>
        <tr>
            <th style="width: 200px;">Numer projektu:</th>
            <td><?php echo htmlspecialchars($project['project_number']); ?></td>
        </tr>
        <tr>
            <th>Status:</th>
            <td>
                <?php
                $statusLabel = match($project['status']) {
                    'active' => 'Aktywny',
                    'planning' => 'Planowanie',
                    'completed' => 'Zakończony',
                    'on_hold' => 'Wstrzymany',
                    'cancelled' => 'Anulowany',
                    default => $project['status']
                };
                echo $statusLabel;
                ?>
            </td>
        </tr>
        <tr>
            <th>Handlowiec:</th>
            <td><?php echo htmlspecialchars(($project['salesperson_first_name'] ?? '') . ' ' . ($project['salesperson_last_name'] ?? '')); ?></td>
        </tr>
        <tr>
            <th>Architekt:</th>
            <td><?php echo htmlspecialchars(($project['architect_first_name'] ?? '') . ' ' . ($project['architect_last_name'] ?? '')); ?></td>
        </tr>
        <tr>
            <th>Data rozpoczęcia:</th>
            <td><?php echo $project['start_date'] ? date('Y-m-d', strtotime($project['start_date'])) : '-'; ?></td>
        </tr>
        <tr>
            <th>Data zakończenia:</th>
            <td><?php echo $project['end_date'] ? date('Y-m-d', strtotime($project['end_date'])) : '-'; ?></td>
        </tr>
        <?php if ($project['description']): ?>
        <tr>
            <th>Opis:</th>
            <td><?php echo nl2br(htmlspecialchars($project['description'])); ?></td>
        </tr>
        <?php endif; ?>
    </table>
</div>

<div class="card">
    <div class="card-header">Godziny pracy</div>
    <table>
        <thead>
            <tr>
                <th>Typ pracy</th>
                <th>Pracownik</th>
                <th>Suma godzin</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($workHours as $wh):
                $workTypeLabel = match($wh['work_type']) {
                    'implementation' => 'Realizacja',
                    'presales' => 'Presales',
                    'support' => 'Wsparcie',
                    default => $wh['work_type']
                };
            ?>
            <tr>
                <td><?php echo $workTypeLabel; ?></td>
                <td><?php echo htmlspecialchars($wh['first_name'] . ' ' . $wh['last_name']); ?></td>
                <td><strong><?php echo number_format($wh['total_hours'], 2); ?> h</strong></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <div class="card-header">Faktury</div>
    <table>
        <thead>
            <tr>
                <th>Numer faktury</th>
                <th>Typ</th>
                <th>Data</th>
                <th>Kwota netto</th>
                <th>Kwota brutto</th>
                <th>Status płatności</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($invoices as $invoice):
                $typeLabel = $invoice['invoice_type'] === 'revenue' ? 'Przychód' : 'Koszt';
                $statusLabel = match($invoice['payment_status']) {
                    'paid' => 'Zapłacona',
                    'pending' => 'Oczekująca',
                    'overdue' => 'Zaległa',
                    'cancelled' => 'Anulowana',
                    default => $invoice['payment_status']
                };
                $statusBadge = match($invoice['payment_status']) {
                    'paid' => 'badge-success',
                    'pending' => 'badge-warning',
                    'overdue' => 'badge-danger',
                    'cancelled' => 'badge-secondary',
                    default => 'badge-info'
                };
            ?>
            <tr>
                <td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                <td><?php echo $typeLabel; ?></td>
                <td><?php echo date('Y-m-d', strtotime($invoice['invoice_date'])); ?></td>
                <td><?php echo number_format($invoice['net_amount'], 2, ',', ' '); ?> <?php echo $invoice['currency']; ?></td>
                <td><?php echo number_format($invoice['gross_amount'], 2, ',', ' '); ?> <?php echo $invoice['currency']; ?></td>
                <td><span class="badge <?php echo $statusBadge; ?>"><?php echo $statusLabel; ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <div class="card-header">Dokumenty (<?php echo count($documents); ?>)</div>
    <table>
        <thead>
            <tr>
                <th>Nazwa pliku</th>
                <th>Typ dokumentu</th>
                <th>Rozmiar</th>
                <th>Data dodania</th>
                <th>Dodane przez</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($documents as $doc):
                $typeLabel = match($doc['document_type']) {
                    'contract' => 'Umowa',
                    'invoice_attachment' => 'Załącznik do faktury',
                    'acceptance_protocol' => 'Protokół odbioru',
                    'other' => 'Inne',
                    default => $doc['document_type']
                };
                $fileSize = $doc['file_size'] / 1024;
                $fileSizeLabel = $fileSize > 1024 ? number_format($fileSize / 1024, 2) . ' MB' : number_format($fileSize, 2) . ' KB';
            ?>
            <tr>
                <td><?php echo htmlspecialchars($doc['document_name']); ?></td>
                <td><?php echo $typeLabel; ?></td>
                <td><?php echo $fileSizeLabel; ?></td>
                <td><?php echo date('Y-m-d H:i', strtotime($doc['created_at'])); ?></td>
                <td><?php echo htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div style="margin-top: 2rem;">
    <a href="/projects" class="btn btn-secondary">Powrót do listy projektów</a>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
