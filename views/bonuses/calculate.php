<?php
use ITSS\Models\User;
use ITSS\Models\Project;

$userModel = new User();
$projectModel = new Project();

$users = $userModel->getAll();
$projects = $projectModel->getAll();

$pageTitle = 'Oblicz premie - ITSS Project Management';
ob_start();
?>

<h2>Obliczanie premii</h2>

<div class="card">
    <div class="card-header">Parametry obliczania</div>

    <form id="bonus-calculation-form">
        <div class="form-group">
            <label>Pracownik</label>
            <select name="user_id" class="form-control">
                <option value="">Wszyscy pracownicy</option>
                <?php foreach ($users as $user): ?>
                <option value="<?php echo $user['id']; ?>">
                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Projekt (opcjonalnie)</label>
            <select name="project_id" class="form-control">
                <option value="">Wszystkie projekty</option>
                <?php foreach ($projects as $project): ?>
                <option value="<?php echo $project['id']; ?>">
                    <?php echo htmlspecialchars($project['project_number'] . ' - ' . $project['project_name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Data początkowa okresu</label>
            <input type="date" name="period_start" class="form-control" required
                   value="<?php echo date('Y-m-01'); ?>">
        </div>

        <div class="form-group">
            <label>Data końcowa okresu</label>
            <input type="date" name="period_end" class="form-control" required
                   value="<?php echo date('Y-m-t'); ?>">
        </div>

        <button type="submit" class="btn btn-primary">Oblicz premie</button>
    </form>
</div>

<div id="results" style="display: none;">
    <div class="card">
        <div class="card-header">Wyniki obliczania</div>
        <div id="results-content"></div>
    </div>
</div>

<script>
    document.getElementById('bonus-calculation-form').addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());

        const resultsDiv = document.getElementById('results');
        const resultsContent = document.getElementById('results-content');

        resultsContent.innerHTML = '<p>Obliczanie premii...</p>';
        resultsDiv.style.display = 'block';

        try {
            const response = await fetch('/api/bonuses/calculate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                if (result.data.length === 0) {
                    resultsContent.innerHTML = '<p>Brak danych do obliczenia premii dla wybranych kryteriów.</p>';
                } else {
                    let html = '<table><thead><tr><th>Użytkownik</th><th>Typ premii</th><th>Podstawa</th><th>Kwota premii</th><th>Szczegóły</th></tr></thead><tbody>';

                    result.data.forEach(bonus => {
                        html += '<tr>';
                        html += '<td>' + (bonus.user_name || 'N/A') + '</td>';
                        html += '<td>' + (bonus.bonus_type || 'N/A') + '</td>';
                        html += '<td>' + parseFloat(bonus.calculation_base || 0).toFixed(2) + '</td>';
                        html += '<td><strong>' + parseFloat(bonus.bonus_amount || 0).toFixed(2) + ' PLN</strong></td>';
                        html += '<td><small>' + (bonus.calculation || 'N/A') + '</small></td>';
                        html += '</tr>';
                    });

                    html += '</tbody></table>';

                    const totalBonus = result.data.reduce((sum, b) => sum + parseFloat(b.bonus_amount || 0), 0);
                    html += '<p style="margin-top: 1rem;"><strong>Suma premii: ' + totalBonus.toFixed(2) + ' PLN</strong></p>';

                    resultsContent.innerHTML = html;
                }
            } else {
                resultsContent.innerHTML = '<p style="color: red;">Błąd: ' + result.error + '</p>';
            }
        } catch (error) {
            resultsContent.innerHTML = '<p style="color: red;">Błąd: ' + error.message + '</p>';
        }
    });
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
