<?php
use ITSS\Models\LeaveRequest;
use ITSS\Core\Session;

$leaveModel = new LeaveRequest();
$userId = Session::get('user_id');
$userRole = Session::get('user_role');

if ($userRole === 'admin') {
    $status = $_GET['status'] ?? null;
    $leaves = $leaveModel->getAll($status);
    $showAllUsers = true;
} elseif ($userRole === 'team_leader') {
    $leaves = array_merge(
        $leaveModel->getPendingForTeamLeader($userId),
        $leaveModel->getByUser($userId)
    );
    $showAllUsers = true;
} elseif (in_array($userRole, ['manager', 'director'])) {
    $leaves = array_merge(
        $leaveModel->getPendingForManager($userId),
        $leaveModel->getByUser($userId)
    );
    $showAllUsers = true;
} else {
    $leaves = $leaveModel->getByUser($userId);
    $showAllUsers = false;
}

$pageTitle = 'Wnioski urlopowe - ITSS Project Management';
ob_start();
?>

<h2>Wnioski urlopowe</h2>

<div class="card">
    <div class="card-header">
        Lista wniosków
        <div style="float: right;">
            <a href="/leaves/create" class="btn btn-primary">Złóż nowy wniosek</a>
        </div>
    </div>

    <?php if ($userRole === 'admin'): ?>
    <div style="padding: 1rem; border-bottom: 1px solid #e1e1e1;">
        <label>Filtruj po statusie:</label>
        <select id="status-filter" class="form-control" style="width: auto; display: inline-block; margin-left: 0.5rem;">
            <option value="">Wszystkie</option>
            <option value="draft" <?php echo ($_GET['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>Szkic</option>
            <option value="pending_team_leader" <?php echo ($_GET['status'] ?? '') === 'pending_team_leader' ? 'selected' : ''; ?>>Oczekuje na lidera</option>
            <option value="pending_manager" <?php echo ($_GET['status'] ?? '') === 'pending_manager' ? 'selected' : ''; ?>>Oczekuje na kierownika</option>
            <option value="approved" <?php echo ($_GET['status'] ?? '') === 'approved' ? 'selected' : ''; ?>>Zatwierdzone</option>
            <option value="rejected" <?php echo ($_GET['status'] ?? '') === 'rejected' ? 'selected' : ''; ?>>Odrzucone</option>
            <option value="cancelled" <?php echo ($_GET['status'] ?? '') === 'cancelled' ? 'selected' : ''; ?>>Anulowane</option>
        </select>
    </div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <?php if ($showAllUsers): ?>
                <th>Pracownik</th>
                <?php endif; ?>
                <th>Typ urlopu</th>
                <th>Data od</th>
                <th>Data do</th>
                <th>Liczba dni</th>
                <th>Status</th>
                <th>Data złożenia</th>
                <th>Akcje</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($leaves as $leave):
                $leaveTypeLabel = match($leave['leave_type']) {
                    'vacation' => 'Urlop wypoczynkowy',
                    'sick_leave' => 'Zwolnienie lekarskie',
                    'unpaid' => 'Urlop bezpłatny',
                    'occasional' => 'Urlop okolicznościowy',
                    'on_demand' => 'Urlop na żądanie',
                    default => $leave['leave_type']
                };

                $statusLabel = match($leave['status']) {
                    'draft' => 'Szkic',
                    'pending_team_leader' => 'Oczekuje na lidera',
                    'pending_manager' => 'Oczekuje na kierownika',
                    'approved' => 'Zatwierdzony',
                    'rejected' => 'Odrzucony',
                    'cancelled' => 'Anulowany',
                    default => $leave['status']
                };

                $statusBadge = match($leave['status']) {
                    'approved' => 'badge-success',
                    'pending_team_leader', 'pending_manager' => 'badge-warning',
                    'rejected', 'cancelled' => 'badge-danger',
                    'draft' => 'badge-info',
                    default => 'badge-info'
                };

                $canApproveTeamLeader = $userRole === 'team_leader' && $leave['status'] === 'pending_team_leader';
                $canApproveManager = in_array($userRole, ['manager', 'director']) && $leave['status'] === 'pending_manager';
            ?>
            <tr>
                <?php if ($showAllUsers): ?>
                <td><?php echo htmlspecialchars(($leave['first_name'] ?? $leave['user_first_name'] ?? '') . ' ' . ($leave['last_name'] ?? $leave['user_last_name'] ?? '')); ?></td>
                <?php endif; ?>
                <td><?php echo $leaveTypeLabel; ?></td>
                <td><?php echo date('Y-m-d', strtotime($leave['start_date'])); ?></td>
                <td><?php echo date('Y-m-d', strtotime($leave['end_date'])); ?></td>
                <td><?php echo $leave['days_count']; ?></td>
                <td><span class="badge <?php echo $statusBadge; ?>"><?php echo $statusLabel; ?></span></td>
                <td><?php echo date('Y-m-d H:i', strtotime($leave['created_at'])); ?></td>
                <td>
                    <a href="/leaves/<?php echo $leave['id']; ?>" class="btn btn-primary">Szczegóły</a>
                    <?php if ($canApproveTeamLeader): ?>
                    <button onclick="approveTeamLeader(<?php echo $leave['id']; ?>)" class="btn btn-success">Zatwierdź</button>
                    <button onclick="reject(<?php echo $leave['id']; ?>)" class="btn btn-danger">Odrzuć</button>
                    <?php endif; ?>
                    <?php if ($canApproveManager): ?>
                    <button onclick="approveManager(<?php echo $leave['id']; ?>)" class="btn btn-success">Zatwierdź</button>
                    <button onclick="reject(<?php echo $leave['id']; ?>)" class="btn btn-danger">Odrzuć</button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
    <?php if ($userRole === 'admin'): ?>
    document.getElementById('status-filter').addEventListener('change', function() {
        const status = this.value;
        window.location.href = status ? `/leaves?status=${status}` : '/leaves';
    });
    <?php endif; ?>

    async function approveTeamLeader(id) {
        const comment = prompt('Komentarz (opcjonalnie):');
        if (comment === null) return;

        try {
            const response = await fetch(`/api/leaves/${id}/approve-team-leader`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ comment })
            });

            const result = await response.json();

            if (result.success) {
                alert('Wniosek zatwierdzony');
                location.reload();
            } else {
                alert('Błąd: ' + result.error);
            }
        } catch (error) {
            alert('Błąd: ' + error.message);
        }
    }

    async function approveManager(id) {
        const comment = prompt('Komentarz (opcjonalnie):');
        if (comment === null) return;

        try {
            const response = await fetch(`/api/leaves/${id}/approve-manager`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ comment })
            });

            const result = await response.json();

            if (result.success) {
                alert('Wniosek zatwierdzony');
                location.reload();
            } else {
                alert('Błąd: ' + result.error);
            }
        } catch (error) {
            alert('Błąd: ' + error.message);
        }
    }

    async function reject(id) {
        const comment = prompt('Powód odrzucenia (wymagany):');
        if (!comment) {
            alert('Musisz podać powód odrzucenia');
            return;
        }

        try {
            const response = await fetch(`/api/leaves/${id}/reject`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ comment })
            });

            const result = await response.json();

            if (result.success) {
                alert('Wniosek odrzucony');
                location.reload();
            } else {
                alert('Błąd: ' + result.error);
            }
        } catch (error) {
            alert('Błąd: ' + error.message);
        }
    }
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
