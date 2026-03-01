<?php
use ITSS\Models\Invoice;

$invoiceModel = new Invoice();
$type = $_GET['type'] ?? null;
$invoices = $invoiceModel->getAll($type);

$pageTitle = 'Faktury - ITSS Project Management';
ob_start();
?>

<h2>Faktury</h2>

<div class="card">
    <div class="card-header">
        Lista faktur
        <div style="float: right;">
            <select id="type-filter" class="form-control" style="width: auto; display: inline-block; margin-right: 1rem;">
                <option value="">Wszystkie</option>
                <option value="revenue" <?php echo $type === 'revenue' ? 'selected' : ''; ?>>Przychody</option>
                <option value="cost" <?php echo $type === 'cost' ? 'selected' : ''; ?>>Koszty</option>
            </select>
            <a href="/invoices/create" class="btn btn-primary">Dodaj fakturę</a>
            <a href="/invoices/import" class="btn btn-success">Importuj z CSV</a>
            <a href="/invoices/ksef" class="btn btn-info">Import z KSeF</a>
            <button onclick="syncEmails()" class="btn btn-secondary">Pobierz z e-mail</button>
            <button onclick="exportInvoices()" class="btn btn-warning">Eksportuj do CSV</button>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Numer faktury</th>
                <th>Typ</th>
                <th>Kontrahent</th>
                <th>Projekt</th>
                <th>Data</th>
                <th>Kwota netto</th>
                <th>Kwota brutto</th>
                <th>Business Type</th>
                <th>Status płatności</th>
                <th>Akcje</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($invoices as $invoice):
                $typeLabel = $invoice['invoice_type'] === 'revenue' ? 'Przychód' : 'Koszt';
                $typeBadge = $invoice['invoice_type'] === 'revenue' ? 'badge-success' : 'badge-danger';

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
                <td><strong><?php echo htmlspecialchars($invoice['invoice_number']); ?></strong></td>
                <td><span class="badge <?php echo $typeBadge; ?>"><?php echo $typeLabel; ?></span></td>
                <td><?php echo htmlspecialchars($invoice['contractor'] ?: ($invoice['client_name'] ?: $invoice['supplier_name'])); ?></td>
                <td>
                    <?php if ($invoice['project_number']): ?>
                        <a href="/projects/<?php echo $invoice['project_id']; ?>">
                            <?php echo htmlspecialchars($invoice['project_number']); ?>
                        </a>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td><?php echo date('Y-m-d', strtotime($invoice['invoice_date'])); ?></td>
                <td><?php echo number_format($invoice['net_amount'], 2, ',', ' '); ?> <?php echo $invoice['currency']; ?></td>
                <td><?php echo number_format($invoice['gross_amount'], 2, ',', ' '); ?> <?php echo $invoice['currency']; ?></td>
                <td><?php echo htmlspecialchars($invoice['business_type'] ?: '-'); ?></td>
                <td><span class="badge <?php echo $statusBadge; ?>"><?php echo $statusLabel; ?></span></td>
                <td>
                    <a href="/invoices/<?php echo $invoice['id']; ?>" class="btn btn-primary">Szczegóły</a>
                    <?php if ($invoice['payment_status'] !== 'paid'): ?>
                    <button onclick="markAsPaid(<?php echo $invoice['id']; ?>)" class="btn btn-success">Oznacz jako zapłacone</button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
    document.getElementById('type-filter').addEventListener('change', function() {
        const type = this.value;
        window.location.href = type ? `/invoices?type=${type}` : '/invoices';
    });

    function exportInvoices() {
        const type = document.getElementById('type-filter').value;
        let url = '/api/invoices/export';
        if (type) {
            url += `?type=${type}`;
        }
        window.location.href = url;
    }

    async function syncEmails() {
        if (!confirm('Czy chcesz sprawdzić skrzynkę e-mail w poszukiwaniu nowych faktur?')) return;
        
        const btn = event.target;
        const originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Synchronizacja...';

        try {
            const response = await fetch('/api/sync/emails', { method: 'POST' });
            const result = await response.json();
            
            if (result.success) {
                alert(`Zakończono! Sprawdzono wiadomości: ${result.data.emails_checked}\nZałączników: ${result.data.attachments_found}\nPrzetworzono: ${result.data.invoices_processed}`);
                location.reload();
            } else {
                alert('Błąd: ' + result.error);
            }
        } catch (error) {
            alert('Błąd połączenia: ' + error.message);
        } finally {
            btn.disabled = false;
            btn.textContent = originalText;
        }
    }

    async function markAsPaid(id) {
        const paymentDate = prompt('Data zapłaty (YYYY-MM-DD):', new Date().toISOString().split('T')[0]);
        if (!paymentDate) return;

        try {
            const response = await fetch(`/api/invoices/${id}/mark-paid`, {
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
