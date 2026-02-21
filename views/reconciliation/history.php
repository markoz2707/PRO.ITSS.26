<?php
use ITSS\Models\DataReconciliationLog;

$logModel = new DataReconciliationLog();
$history = $logModel->getAll(null, 200);
$stats = $logModel->getStats();

$pageTitle = 'Historia uspójniania - ITSS Project Management';
ob_start();
?>

<h2>Historia uspójniania danych</h2>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-value"><?php echo (int)($stats['total'] ?? 0); ?></div>
        <div class="stat-label">Operacji łącznie</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color: #28a745;"><?php echo (int)($stats['applied'] ?? 0); ?></div>
        <div class="stat-label">Zastosowanych</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color: #ff9800;"><?php echo (int)($stats['pending'] ?? 0); ?></div>
        <div class="stat-label">Oczekujących</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo number_format((float)($stats['avg_confidence'] ?? 0), 1); ?>%</div>
        <div class="stat-label">Średnia pewność</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        Dziennik operacji
        <a href="/reconciliation" class="btn btn-primary btn-sm" style="float:right;">Wróć do uspójniania</a>
    </div>

    <?php if (empty($history)): ?>
        <p style="color: #6c757d;">Brak wpisów w historii.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Typ operacji</th>
                    <th>Projekt</th>
                    <th>CRM ID</th>
                    <th>SD Kontrakt</th>
                    <th>SD Projekt</th>
                    <th>Pewność</th>
                    <th>Zaktualizowane pola</th>
                    <th>Status</th>
                    <th>Wykonał</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($history as $entry):
                    $typeLabel = match($entry['reconciliation_type']) {
                        'auto_match' => 'Automatyczne',
                        'manual_match' => 'Ręczne',
                        'merge' => 'Scalenie',
                        'unlink' => 'Rozłączenie',
                        default => $entry['reconciliation_type']
                    };
                    $typeBadge = match($entry['reconciliation_type']) {
                        'auto_match' => 'badge-info',
                        'manual_match' => 'badge-purple',
                        'merge' => 'badge-success',
                        'unlink' => 'badge-danger',
                        default => 'badge-secondary'
                    };
                    $statusBadge = match($entry['status']) {
                        'applied' => 'badge-success',
                        'pending' => 'badge-warning',
                        'rejected' => 'badge-danger',
                        'reverted' => 'badge-secondary',
                        default => 'badge-info'
                    };
                    $statusLabel = match($entry['status']) {
                        'applied' => 'Zastosowany',
                        'pending' => 'Oczekujący',
                        'rejected' => 'Odrzucony',
                        'reverted' => 'Cofnięty',
                        default => $entry['status']
                    };

                    $fieldsUpdated = $entry['fields_updated'] ? json_decode($entry['fields_updated'], true) : [];
                    $performer = !empty($entry['performed_by_first_name'])
                        ? $entry['performed_by_first_name'] . ' ' . $entry['performed_by_last_name']
                        : '-';
                ?>
                <tr>
                    <td style="white-space: nowrap;"><?php echo date('Y-m-d H:i', strtotime($entry['created_at'])); ?></td>
                    <td><span class="badge <?php echo $typeBadge; ?>"><?php echo $typeLabel; ?></span></td>
                    <td>
                        <?php if ($entry['project_name']): ?>
                            <a href="/projects/<?php echo $entry['project_id']; ?>">
                                <?php echo htmlspecialchars($entry['project_number'] ?? $entry['project_name']); ?>
                            </a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td style="font-size:0.8rem;"><?php echo htmlspecialchars($entry['crm_id'] ?? '-'); ?></td>
                    <td style="font-size:0.8rem;"><?php echo htmlspecialchars($entry['sd_contract_id'] ?? '-'); ?></td>
                    <td style="font-size:0.8rem;"><?php echo htmlspecialchars($entry['sd_project_id'] ?? '-'); ?></td>
                    <td>
                        <?php if ($entry['match_confidence']): ?>
                            <strong style="color: <?php echo $entry['match_confidence'] >= 80 ? '#28a745' : ($entry['match_confidence'] >= 60 ? '#ff9800' : '#dc3545'); ?>">
                                <?php echo number_format($entry['match_confidence'], 0); ?>%
                            </strong>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td style="font-size:0.8rem;">
                        <?php echo !empty($fieldsUpdated) ? implode(', ', $fieldsUpdated) : '-'; ?>
                    </td>
                    <td><span class="badge <?php echo $statusBadge; ?>"><?php echo $statusLabel; ?></span></td>
                    <td><?php echo htmlspecialchars($performer); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
