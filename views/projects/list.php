<?php
use ITSS\Models\Project;

$projectModel = new Project();
$status = $_GET['status'] ?? null;
$projects = $projectModel->getAll($status);

$pageTitle = 'Projekty - ITSS Project Management';
ob_start();
?>

<h2>Projekty</h2>

<div class="card">
    <div class="card-header">
        Lista projektów
        <div style="float: right;">
            <select id="status-filter" class="form-control" style="width: auto; display: inline-block;">
                <option value="">Wszystkie</option>
                <option value="planning" <?php echo $status === 'planning' ? 'selected' : ''; ?>>Planowanie</option>
                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Aktywne</option>
                <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Zakończone</option>
                <option value="on_hold" <?php echo $status === 'on_hold' ? 'selected' : ''; ?>>Wstrzymane</option>
                <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Anulowane</option>
            </select>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Numer projektu</th>
                <th>Nazwa</th>
                <th>Status</th>
                <th>Handlowiec</th>
                <th>Architekt</th>
                <th>Data rozpoczęcia</th>
                <th>Data zakończenia</th>
                <th>Akcje</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($projects as $project):
                $statusBadge = match($project['status']) {
                    'active' => 'badge-success',
                    'planning' => 'badge-info',
                    'completed' => 'badge-secondary',
                    'on_hold' => 'badge-warning',
                    'cancelled' => 'badge-danger',
                    default => 'badge-info'
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
                <td><strong><?php echo htmlspecialchars($project['project_number']); ?></strong></td>
                <td><?php echo htmlspecialchars($project['project_name']); ?></td>
                <td><span class="badge <?php echo $statusBadge; ?>"><?php echo $statusLabel; ?></span></td>
                <td><?php echo htmlspecialchars(($project['salesperson_first_name'] ?? '') . ' ' . ($project['salesperson_last_name'] ?? '')); ?></td>
                <td><?php echo htmlspecialchars(($project['architect_first_name'] ?? '') . ' ' . ($project['architect_last_name'] ?? '')); ?></td>
                <td><?php echo $project['start_date'] ? date('Y-m-d', strtotime($project['start_date'])) : '-'; ?></td>
                <td><?php echo $project['end_date'] ? date('Y-m-d', strtotime($project['end_date'])) : '-'; ?></td>
                <td>
                    <a href="/projects/<?php echo $project['id']; ?>" class="btn btn-primary">Szczegóły</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
    document.getElementById('status-filter').addEventListener('change', function() {
        const status = this.value;
        window.location.href = status ? `/projects?status=${status}` : '/projects';
    });
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
