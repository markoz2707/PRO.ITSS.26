<?php
use ITSS\Models\BonusScheme;

$bonusSchemeModel = new BonusScheme();
$schemes = $bonusSchemeModel->getAll();

$pageTitle = 'Schematy premiowe - ITSS Project Management';
ob_start();
?>

<h2>Schematy premiowe</h2>

<div class="card">
    <div class="card-header">
        Lista aktywnych schematów
        <div style="float: right;">
            <button class="btn btn-primary" onclick="alert('Tworzenie schematu przez GUI w przygotowaniu.')">Nowy schemat</button>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Nazwa schematu</th>
                <th>Typ premii</th>
                <th>Projekt</th>
                <th>Pracownik</th>
                <th>Wartość (%) / Stawka</th>
                <th>Data utworzenia</th>
                <th>Akcje</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($schemes)): ?>
                <tr><td colspan="7" style="text-align: center;">Brak skonfigurowanych schematów premiowych.</td></tr>
            <?php endif; ?>
            <?php foreach ($schemes as $scheme): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($scheme['scheme_name'] ?? 'Schemat #' . $scheme['id']); ?></strong></td>
                <td>
                    <span class="badge badge-info">
                    <?php echo match($scheme['bonus_type']) {
                        'margin_1' => 'Premia od Marży 1',
                        'margin_2' => 'Premia od Marży 2',
                        'hourly' => 'Premia godzinowa',
                        'helpdesk_percent' => 'Pula helpdesk %',
                        'helpdesk_fixed' => 'Stała za zgłoszenie',
                        default => $scheme['bonus_type']
                    }; ?>
                    </span>
                </td>
                <td><?php echo htmlspecialchars($scheme['project_number'] ?? 'Wszystkie'); ?></td>
                <td><?php echo htmlspecialchars($scheme['user_name'] ?? ($scheme['first_name'] . ' ' . $scheme['last_name'])); ?></td>
                <td>
                    <strong>
                    <?php if (in_array($scheme['bonus_type'], ['margin_1', 'margin_2', 'helpdesk_percent'])): ?>
                        <?php echo $scheme['percentage']; ?> %
                    <?php elseif ($scheme['bonus_type'] === 'hourly'): ?>
                        <?php echo number_format($scheme['hourly_rate'], 2, ',', ' '); ?> PLN/h
                    <?php else: ?>
                        <?php echo number_format($scheme['fixed_amount'], 2, ',', ' '); ?> PLN
                    <?php endif; ?>
                    </strong>
                </td>
                <td><?php echo date('Y-m-d', strtotime($scheme['created_at'])); ?></td>
                <td>
                    <button class="btn btn-sm btn-secondary" onclick="alert('Edycja w przygotowaniu.')">Edytuj</button>
                    <button class="btn btn-sm btn-danger" onclick="alert('Usuwanie w przygotowaniu.')">Usuń</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <div class="card-header">
        Legenda typów premii
    </div>
    <div style="padding: 10px;">
        <ul>
            <li><strong>Marża 1:</strong> Przychody (zapłacone) - Koszty bezpośrednie (zapłacone).</li>
            <li><strong>Marża 2:</strong> Marża 1 - Koszty pracy (godziny x stawka).</li>
            <li><strong>Premia godzinowa:</strong> Liczba przepracowanych godzin x stała stawka.</li>
            <li><strong>Helpdesk %:</strong> Procent z ogólnej puli premiowej dla zespołu wsparcia.</li>
            <li><strong>Helpdesk stała:</strong> Stała kwota za każde rozwiązane zgłoszenie.</li>
        </ul>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
