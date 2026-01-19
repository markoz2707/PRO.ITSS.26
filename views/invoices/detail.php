<?php
use ITSS\Models\Invoice;
use ITSS\Models\InvoiceItem;
use ITSS\Models\Document;
use ITSS\Models\Project;

$invoiceId = $id ?? $_GET['id'] ?? null;

if (!$invoiceId) {
    header('Location: /invoices');
    exit;
}

$invoiceModel = new Invoice();
$invoiceItemModel = new InvoiceItem();
$documentModel = new Document();
$projectModel = new Project();

$invoice = $invoiceModel->findById($invoiceId);

if (!$invoice) {
    header('Location: /invoices');
    exit;
}

$items = $invoiceItemModel->getByInvoice($invoiceId);
$documents = $documentModel->getByInvoice($invoiceId);

$project = null;
if ($invoice['project_id']) {
    $project = $projectModel->findById($invoice['project_id']);
}

$pageTitle = 'Faktura ' . $invoice['invoice_number'] . ' - ITSS Project Management';
ob_start();

$typeLabel = $invoice['invoice_type'] === 'revenue' ? 'Przychód' : 'Koszt';
$statusLabel = match($invoice['payment_status']) {
    'paid' => 'Zapłacona',
    'pending' => 'Oczekująca',
    'overdue' => 'Zaległa',
    'cancelled' => 'Anulowana',
    default => $invoice['payment_status']
};
?>

<h2>Faktura <?php echo htmlspecialchars($invoice['invoice_number']); ?></h2>

<div class="card">
    <div class="card-header">
        Podstawowe informacje
        <div style="float: right;">
            <a href="/invoices" class="btn btn-secondary">Powrót do listy</a>
        </div>
    </div>

    <table>
        <tr>
            <th style="width: 200px;">Numer faktury:</th>
            <td><strong><?php echo htmlspecialchars($invoice['invoice_number']); ?></strong></td>
        </tr>
        <tr>
            <th>Typ:</th>
            <td><?php echo $typeLabel; ?></td>
        </tr>
        <tr>
            <th>Kontrahent:</th>
            <td><?php echo htmlspecialchars($invoice['contractor'] ?: ($invoice['client_name'] ?: $invoice['supplier_name'])); ?></td>
        </tr>
        <?php if ($project): ?>
        <tr>
            <th>Projekt:</th>
            <td>
                <a href="/projects/<?php echo $project['id']; ?>">
                    <?php echo htmlspecialchars($project['project_number'] . ' - ' . $project['project_name']); ?>
                </a>
            </td>
        </tr>
        <?php endif; ?>
        <tr>
            <th>Data wystawienia:</th>
            <td><?php echo date('Y-m-d', strtotime($invoice['invoice_date'])); ?></td>
        </tr>
        <?php if ($invoice['due_date']): ?>
        <tr>
            <th>Termin płatności:</th>
            <td><?php echo date('Y-m-d', strtotime($invoice['due_date'])); ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($invoice['payment_deadline_date']): ?>
        <tr>
            <th>Data płatności:</th>
            <td><?php echo date('Y-m-d', strtotime($invoice['payment_deadline_date'])); ?></td>
        </tr>
        <?php endif; ?>
        <tr>
            <th>Status płatności:</th>
            <td><strong><?php echo $statusLabel; ?></strong></td>
        </tr>
        <?php if ($invoice['payment_date']): ?>
        <tr>
            <th>Data zapłaty:</th>
            <td><?php echo date('Y-m-d', strtotime($invoice['payment_date'])); ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($invoice['payment_received_date']): ?>
        <tr>
            <th>Data otrzymania płatności:</th>
            <td><?php echo date('Y-m-d', strtotime($invoice['payment_received_date'])); ?></td>
        </tr>
        <?php endif; ?>
    </table>
</div>

<div class="card">
    <div class="card-header">Kwoty</div>
    <table>
        <tr>
            <th style="width: 200px;">Kwota netto:</th>
            <td><strong><?php echo number_format($invoice['net_amount'], 2, ',', ' '); ?> <?php echo $invoice['currency']; ?></strong></td>
        </tr>
        <tr>
            <th>VAT:</th>
            <td><?php echo number_format($invoice['vat_amount'], 2, ',', ' '); ?> <?php echo $invoice['currency']; ?></td>
        </tr>
        <tr>
            <th>Kwota brutto:</th>
            <td><strong><?php echo number_format($invoice['gross_amount'], 2, ',', ' '); ?> <?php echo $invoice['currency']; ?></strong></td>
        </tr>
    </table>
</div>

<?php if ($invoice['business_type'] || $invoice['segment'] || $invoice['sector'] || $invoice['category']): ?>
<div class="card">
    <div class="card-header">Klasyfikacja biznesowa</div>
    <table>
        <?php if ($invoice['business_type']): ?>
        <tr>
            <th style="width: 200px;">Business Type:</th>
            <td><?php echo htmlspecialchars($invoice['business_type']); ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($invoice['segment']): ?>
        <tr>
            <th>Segment:</th>
            <td><?php echo htmlspecialchars($invoice['segment']); ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($invoice['sector']): ?>
        <tr>
            <th>Sektor:</th>
            <td><?php echo htmlspecialchars($invoice['sector']); ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($invoice['category']): ?>
        <tr>
            <th>Kategoria:</th>
            <td><?php echo htmlspecialchars($invoice['category']); ?></td>
        </tr>
        <?php endif; ?>
    </table>
</div>
<?php endif; ?>

<?php
$hasMPK = $invoice['mpk_dh1'] || $invoice['mpk_dh2'] || $invoice['mpk_gnp'] ||
          $invoice['mpk_do'] || $invoice['mpk_og'] || $invoice['mpk_eu1'] ||
          $invoice['mpk_eu2'] || $invoice['mpk_ono'] || $invoice['mpk_ksdo'];
if ($hasMPK):
?>
<div class="card">
    <div class="card-header">Kody MPK (Miejsca Powstawania Kosztów)</div>
    <table>
        <?php
        $mpkFields = [
            'mpk_dh1' => 'MPK-DH1',
            'mpk_dh2' => 'MPK-DH2',
            'mpk_gnp' => 'MPK-GNP',
            'mpk_do' => 'MPK-DO',
            'mpk_og' => 'MPK-OG',
            'mpk_eu1' => 'MPK-EU1',
            'mpk_eu2' => 'MPK-EU2',
            'mpk_ono' => 'MPK-ONO',
            'mpk_ksdo' => 'MPK-KSDO'
        ];
        foreach ($mpkFields as $field => $label):
            if ($invoice[$field]):
        ?>
        <tr>
            <th style="width: 200px;"><?php echo $label; ?>:</th>
            <td><?php echo htmlspecialchars($invoice[$field]); ?></td>
        </tr>
        <?php
            endif;
        endforeach;
        ?>
    </table>
</div>
<?php endif; ?>

<?php if ($invoice['operator_client'] || $invoice['uwagi'] || $invoice['baza_licze'] || $invoice['mpt']): ?>
<div class="card">
    <div class="card-header">Dodatkowe informacje</div>
    <table>
        <?php if ($invoice['operator_client']): ?>
        <tr>
            <th style="width: 200px;">Opiekun handlowy:</th>
            <td><?php echo htmlspecialchars($invoice['operator_client']); ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($invoice['baza_licze']): ?>
        <tr>
            <th>Baza licencji:</th>
            <td><?php echo htmlspecialchars($invoice['baza_licze']); ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($invoice['mpt']): ?>
        <tr>
            <th>MPT:</th>
            <td><?php echo htmlspecialchars($invoice['mpt']); ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($invoice['uwagi']): ?>
        <tr>
            <th>Uwagi:</th>
            <td><?php echo nl2br(htmlspecialchars($invoice['uwagi'])); ?></td>
        </tr>
        <?php endif; ?>
    </table>
</div>
<?php endif; ?>

<?php if ($invoice['description']): ?>
<div class="card">
    <div class="card-header">Opis faktury</div>
    <p><?php echo nl2br(htmlspecialchars($invoice['description'])); ?></p>
</div>
<?php endif; ?>

<?php if (count($items) > 0): ?>
<div class="card">
    <div class="card-header">Pozycje faktury (<?php echo count($items); ?>)</div>
    <table>
        <thead>
            <tr>
                <th>Lp.</th>
                <th>Nazwa</th>
                <th>Kategoria</th>
                <th>Ilość</th>
                <th>J.m.</th>
                <th>Cena jedn. netto</th>
                <th>Kwota netto</th>
                <th>VAT</th>
                <th>Kwota brutto</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?php echo $item['item_number']; ?></td>
                <td><?php echo htmlspecialchars($item['item_name'] ?: $item['item_description']); ?></td>
                <td><?php echo htmlspecialchars($item['category'] ?: '-'); ?></td>
                <td><?php echo number_format($item['quantity'], 2); ?></td>
                <td><?php echo htmlspecialchars($item['unit']); ?></td>
                <td><?php echo $item['unit_net_price'] ? number_format($item['unit_net_price'], 2, ',', ' ') : '-'; ?></td>
                <td><?php echo number_format($item['net_amount'], 2, ',', ' '); ?></td>
                <td><?php echo number_format($item['vat_rate'], 0); ?>%</td>
                <td><?php echo number_format($item['gross_amount'], 2, ',', ' '); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php if (count($documents) > 0): ?>
<div class="card">
    <div class="card-header">Załączniki (<?php echo count($documents); ?>)</div>
    <table>
        <thead>
            <tr>
                <th>Nazwa pliku</th>
                <th>Typ</th>
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
<?php endif; ?>

<div style="margin-top: 2rem;">
    <a href="/invoices" class="btn btn-secondary">Powrót do listy faktur</a>
    <?php if ($invoice['payment_status'] !== 'paid'): ?>
    <button onclick="markAsPaid()" class="btn btn-success">Oznacz jako zapłacone</button>
    <?php endif; ?>
</div>

<script>
    async function markAsPaid() {
        const paymentDate = prompt('Data zapłaty (YYYY-MM-DD):', new Date().toISOString().split('T')[0]);
        if (!paymentDate) return;

        try {
            const response = await fetch('/api/invoices/<?php echo $invoiceId; ?>/mark-paid', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ payment_date: paymentDate })
            });

            const result = await response.json();

            if (result.success) {
                alert('Faktura oznaczona jako zapłacona');
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
