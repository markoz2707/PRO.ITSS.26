<?php
use ITSS\Models\CalculatedBonus;

$calculatedBonusModel = new CalculatedBonus();
$status = $_GET['status'] ?? null;
$bonuses = $calculatedBonusModel->getAll($status);

$pageTitle = 'Premie - ITSS Project Management';
ob_start();
?>

<h2>Zatwierdzone premie</h2>

<div class="card">
    <div class="card-header">
        Lista obliczonych premii
        <div style="float: right;">
            <select id="status-filter" class="form-control" style="width: auto; display: inline-block; margin-right: 1rem;">
                <option value="">Wszystkie statusy</option>
                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Oczekujące</option>
                <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Zatwierdzone</option>
                <option value="paid" <?php echo $status === 'paid' ? 'selected' : ''; ?>>Wypłacone</option>
            </select>
            <a href="/bonuses/calculate" class="btn btn-primary">Oblicz nowe premie</a>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Pracownik</th>
                <th>Typ premii</th>
                <th>Projekt</th>
                <th>Okres</th>
                <th>Kwota</th>
                <th>Status</th>
                <th>Data obliczenia</th>
                <th>Akcje</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($bonuses)): ?>
                <tr><td colspan="8" style="text-align: center;">Brak obliczonych premii w systemie.</td></tr>
            <?php endif; ?>
            <?php foreach ($bonuses as $bonus): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($bonus['user_name'] ?? 'N/A'); ?></strong></td>
                <td><?php echo htmlspecialchars($bonus['bonus_type'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($bonus['project_number'] ?? '-'); ?></td>
                <td><?php echo $bonus['period_start']; ?> - <?php echo $bonus['period_end']; ?></td>
                <td><strong><?php echo number_format($bonus['amount'], 2, ',', ' '); ?> PLN</strong></td>
                <td>
                    <span class="badge <?php 
                        echo match($bonus['status']) {
                            'paid' => 'badge-success',
                            'approved' => 'badge-info',
                            'pending' => 'badge-warning',
                            default => 'badge-secondary'
                        };
                    ?>">
                        <?php echo match($bonus['status']) {
                            'paid' => 'Wypłacona',
                            'approved' => 'Zatwierdzona',
                            'pending' => 'Do zatwierdzenia',
                            default => $bonus['status']
                        }; ?>
                    </span>
                </td>
                <td><?php echo date('Y-m-d H:i', strtotime($bonus['created_at'])); ?></td>
                <td>
                    <?php if ($bonus['status'] === 'pending'): ?>
                        <button class="btn btn-sm btn-success" onclick="updateStatus(<?php echo $bonus['id']; ?>, 'approved')">Zatwierdź</button>
                    <?php elseif ($bonus['status'] === 'approved'): ?>
                        <button class="btn btn-sm btn-primary" onclick="updateStatus(<?php echo $bonus['id']; ?>, 'paid')">Oznacz jako wypłaconą</button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
    document.getElementById('status-filter').addEventListener('change', function() {
        const status = this.value;
        window.location.href = status ? `/bonuses?status=${status}` : '/bonuses';
    });

    function updateStatus(id, newStatus) {
        if (!confirm(`Czy na pewno chcesz zmienić status na ${newStatus}?`)) return;

        // Implementacja API do zmiany statusu premii mogłaby być tu dodana
        alert('Funkcjonalność zmiany statusu przez API w przygotowaniu.');
    }
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
