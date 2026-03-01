<?php
use ITSS\Models\Project;
use ITSS\Models\Invoice;
use ITSS\Core\Session;

$projectModel = new Project();
$invoiceModel = new Invoice();

$activeProjects = count($projectModel->getAll('active'));
$totalProjects = count($projectModel->getAll());

$invoiceSummary = $invoiceModel->getSummary();
$totalRevenue = 0;
$totalCosts = 0;

foreach ($invoiceSummary as $summary) {
    if ($summary['invoice_type'] === 'revenue' && $summary['payment_status'] === 'paid') {
        $totalRevenue += $summary['total_net'];
    }
    if ($summary['invoice_type'] === 'cost' && $summary['payment_status'] === 'paid') {
        $totalCosts += $summary['total_net'];
    }
}

$userId = Session::get('user_id');
$userRole = Session::get('user_role');

$pageTitle = 'Dashboard - ITSS Project Management';
ob_start();
?>

<h2>Dashboard</h2>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-value"><?php echo $totalProjects; ?></div>
        <div class="stat-label">Wszystkich projektów</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo $activeProjects; ?></div>
        <div class="stat-label">Aktywnych projektów</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo number_format($totalRevenue, 2, ',', ' '); ?> PLN</div>
        <div class="stat-label">Przychody (zapłacone)</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo number_format($totalCosts, 2, ',', ' '); ?> PLN</div>
        <div class="stat-label">Koszty (zapłacone)</div>
    </div>
</div>

<div class="card">
    <div class="card-header">Ostatnie projekty</div>
    <table>
        <thead>
            <tr>
                <th>Numer projektu</th>
                <th>Nazwa</th>
                <th>Status</th>
                <th>Handlowiec</th>
                <th>Architekt</th>
                <th>Akcje</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $recentProjects = array_slice($projectModel->getAll(), 0, 10);
            foreach ($recentProjects as $project):
                $statusBadge = match($project['status']) {
                    'active' => 'badge-success',
                    'planning' => 'badge-info',
                    'completed' => 'badge-secondary',
                    default => 'badge-warning'
                };
                $statusLabel = match($project['status']) {
                    'active' => 'Aktywny',
                    'planning' => 'Planowanie',
                    'completed' => 'Zakończony',
                    'on_hold' => 'Wstrzymany',
                    'cancelled' => 'Anulowany',
                    default => $project['status']
                };
            ?>
            <tr>
                <td><?php echo htmlspecialchars($project['project_number']); ?></td>
                <td><?php echo htmlspecialchars($project['project_name']); ?></td>
                <td><span class="badge <?php echo $statusBadge; ?>"><?php echo $statusLabel; ?></span></td>
                <td><?php echo htmlspecialchars($project['salesperson_first_name'] . ' ' . $project['salesperson_last_name']); ?></td>
                <td><?php echo htmlspecialchars($project['architect_first_name'] . ' ' . $project['architect_last_name']); ?></td>
                <td><a href="/projects/<?php echo $project['id']; ?>" class="btn btn-primary">Szczegóły</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <div class="card-header">Szybkie akcje</div>
    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
        <a href="/invoices/create" class="btn btn-primary">Dodaj fakturę</a>
        <a href="/documents" class="btn btn-primary">Zarządzaj dokumentami</a>
        <a href="/bonuses/calculate" class="btn btn-success">Oblicz premie</a>
    </div>
</div>

<div class="card">
    <div class="card-header">Synchronizacja danych</div>
    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
        <button onclick="syncCRM()" class="btn btn-primary">Synchronizuj CRM</button>
        <button onclick="syncServiceDesk()" class="btn btn-primary">Synchronizuj ServiceDesk</button>
        <a href="/reconciliation" class="btn btn-warning">Uspójnianie danych</a>
    </div>
    <div id="sync-status" style="margin-top: 1rem;"></div>
</div>

<script>
    async function syncCRM() {
        const statusDiv = document.getElementById('sync-status');
        statusDiv.innerHTML = 'Synchronizacja CRM...';

        try {
            const response = await fetch('/api/sync/crm', { method: 'POST' });
            const data = await response.json();

            if (data.success) {
                statusDiv.innerHTML = `<span style="color: green;">✓ Zsynchronizowano ${data.synced_count} projektów</span>`;
            } else {
                statusDiv.innerHTML = `<span style="color: red;">✗ Błąd: ${data.error}</span>`;
            }
        } catch (error) {
            statusDiv.innerHTML = `<span style="color: red;">✗ Błąd: ${error.message}</span>`;
        }
    }

    async function syncServiceDesk() {
        const statusDiv = document.getElementById('sync-status');
        statusDiv.innerHTML = 'Synchronizacja ServiceDesk...';

        try {
            const response = await fetch('/api/sync/servicedesk', { method: 'POST' });
            const data = await response.json();

            if (data.success) {
                statusDiv.innerHTML = `<span style="color: green;">✓ Zsynchronizowano ${data.work_hours_synced} godzin pracy i ${data.tickets_synced} zgłoszeń</span>`;
            } else {
                statusDiv.innerHTML = `<span style="color: red;">✗ Błąd: ${data.error}</span>`;
            }
        } catch (error) {
            statusDiv.innerHTML = `<span style="color: red;">✗ Błąd: ${error.message}</span>`;
        }
    }
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
