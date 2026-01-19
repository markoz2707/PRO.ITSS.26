<?php
use ITSS\Models\Project;

$projectModel = new Project();
$projects = $projectModel->getAll();

$pageTitle = 'Dodaj fakturę - ITSS Project Management';
ob_start();
?>

<h2>Dodaj nową fakturę</h2>

<div class="card">
    <div class="card-header">Formularz faktury</div>

    <form id="invoice-form">
        <h3>Podstawowe informacje</h3>

        <div class="form-group">
            <label>Typ faktury <span style="color: red;">*</span></label>
            <select name="invoice_type" class="form-control" required>
                <option value="">Wybierz typ</option>
                <option value="revenue">Przychód (faktura sprzedażowa)</option>
                <option value="cost">Koszt (faktura zakupowa)</option>
            </select>
        </div>

        <div class="form-group">
            <label>Numer faktury <span style="color: red;">*</span></label>
            <input type="text" name="invoice_number" class="form-control" required
                   placeholder="np. FS/064/1/2024">
        </div>

        <div class="form-group">
            <label>Kontrahent <span style="color: red;">*</span></label>
            <input type="text" name="contractor" class="form-control" required
                   placeholder="Nazwa kontrahenta">
        </div>

        <div class="form-group">
            <label>Projekt</label>
            <select name="project_id" class="form-control">
                <option value="">Brak przypisania do projektu</option>
                <?php foreach ($projects as $project): ?>
                <option value="<?php echo $project['id']; ?>">
                    <?php echo htmlspecialchars($project['project_number'] . ' - ' . $project['project_name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <h3>Daty</h3>

        <div class="form-group">
            <label>Data wystawienia <span style="color: red;">*</span></label>
            <input type="date" name="invoice_date" class="form-control" required
                   value="<?php echo date('Y-m-d'); ?>">
        </div>

        <div class="form-group">
            <label>Termin płatności</label>
            <input type="date" name="due_date" class="form-control">
        </div>

        <div class="form-group">
            <label>Data płatności (jeśli już zapłacono)</label>
            <input type="date" name="payment_date" class="form-control">
        </div>

        <h3>Kwoty</h3>

        <div class="form-group">
            <label>Kwota netto <span style="color: red;">*</span></label>
            <input type="number" name="net_amount" class="form-control" required
                   step="0.01" min="0" placeholder="0.00" id="net_amount">
        </div>

        <div class="form-group">
            <label>Stawka VAT (%)</label>
            <select name="vat_rate" class="form-control" id="vat_rate">
                <option value="23">23%</option>
                <option value="8">8%</option>
                <option value="5">5%</option>
                <option value="0">0% (zwolnione)</option>
            </select>
        </div>

        <div class="form-group">
            <label>Kwota VAT (obliczana automatycznie)</label>
            <input type="number" name="vat_amount" class="form-control" required
                   step="0.01" min="0" placeholder="0.00" id="vat_amount" readonly>
        </div>

        <div class="form-group">
            <label>Kwota brutto (obliczana automatycznie)</label>
            <input type="number" name="gross_amount" class="form-control" required
                   step="0.01" min="0" placeholder="0.00" id="gross_amount" readonly>
        </div>

        <div class="form-group">
            <label>Waluta</label>
            <select name="currency" class="form-control">
                <option value="PLN" selected>PLN</option>
                <option value="EUR">EUR</option>
                <option value="USD">USD</option>
            </select>
        </div>

        <h3>Klasyfikacja biznesowa</h3>

        <div class="form-group">
            <label>Business Type</label>
            <select name="business_type" class="form-control">
                <option value="">Wybierz</option>
                <option value="2.2- Bundled contracts">2.2- Bundled contracts</option>
                <option value="4.1- Hardware sales">4.1- Hardware sales</option>
                <option value="5- Commercial">5- Commercial</option>
                <option value="2.1- Managed and support services">2.1- Managed and support services</option>
            </select>
            <small>Typ umowy/transakcji biznesowej</small>
        </div>

        <div class="form-group">
            <label>Segment</label>
            <select name="segment" class="form-control">
                <option value="">Wybierz</option>
                <option value="2- Large-owned company">2- Large-owned company</option>
                <option value="3- state-owned company">3- state-owned company</option>
                <option value="5- commercial">5- commercial</option>
            </select>
            <small>Segment klienta</small>
        </div>

        <div class="form-group">
            <label>Sektor</label>
            <input type="text" name="sector" class="form-control"
                   placeholder="np. Finanse, Produkcja, IT">
        </div>

        <div class="form-group">
            <label>Kategoria</label>
            <select name="category" class="form-control">
                <option value="">Wybierz</option>
                <option value="0-20.00 USŁUGA ZGODNIE Z UMOWĄ ZAMÓWIENIEM">0-20.00 USŁUGA ZGODNIE Z UMOWĄ ZAMÓWIENIEM</option>
                <option value="6.00.10 KONSULTACJE">6.00.10 KONSULTACJE</option>
                <option value="6.00.00 INNE USŁUGI">6.00.00 INNE USŁUGI</option>
            </select>
        </div>

        <h3>Kody MPK (Miejsca Powstawania Kosztów)</h3>

        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
            <div class="form-group">
                <label>MPK-DH1</label>
                <input type="text" name="mpk_dh1" class="form-control">
            </div>

            <div class="form-group">
                <label>MPK-DH2</label>
                <input type="text" name="mpk_dh2" class="form-control">
            </div>

            <div class="form-group">
                <label>MPK-GNP</label>
                <input type="text" name="mpk_gnp" class="form-control">
            </div>

            <div class="form-group">
                <label>MPK-DO</label>
                <input type="text" name="mpk_do" class="form-control">
            </div>

            <div class="form-group">
                <label>MPK-OG</label>
                <input type="text" name="mpk_og" class="form-control">
            </div>

            <div class="form-group">
                <label>MPK-EU1</label>
                <input type="text" name="mpk_eu1" class="form-control">
            </div>

            <div class="form-group">
                <label>MPK-EU2</label>
                <input type="text" name="mpk_eu2" class="form-control">
            </div>

            <div class="form-group">
                <label>MPK-ONO</label>
                <input type="text" name="mpk_ono" class="form-control">
            </div>

            <div class="form-group">
                <label>MPK-KSDO</label>
                <input type="text" name="mpk_ksdo" class="form-control">
            </div>
        </div>

        <h3>Dodatkowe informacje</h3>

        <div class="form-group">
            <label>Opiekun handlowy</label>
            <input type="text" name="operator_client" class="form-control"
                   placeholder="Imię i nazwisko opiekuna">
        </div>

        <div class="form-group">
            <label>Baza licencji</label>
            <input type="text" name="baza_licze" class="form-control">
        </div>

        <div class="form-group">
            <label>MPT</label>
            <input type="text" name="mpt" class="form-control">
        </div>

        <div class="form-group">
            <label>Opis faktury</label>
            <textarea name="description" class="form-control" rows="3"
                      placeholder="Szczegółowy opis faktury"></textarea>
        </div>

        <div class="form-group">
            <label>Uwagi</label>
            <textarea name="uwagi" class="form-control" rows="3"
                      placeholder="Dodatkowe uwagi, np. informacje o przelewach"></textarea>
        </div>

        <div style="margin-top: 2rem;">
            <button type="submit" class="btn btn-primary">Zapisz fakturę</button>
            <a href="/invoices" class="btn btn-secondary">Anuluj</a>
        </div>
    </form>
</div>

<script>
    // Automatyczne obliczanie VAT i kwoty brutto
    function calculateAmounts() {
        const netAmount = parseFloat(document.getElementById('net_amount').value) || 0;
        const vatRate = parseFloat(document.getElementById('vat_rate').value) || 0;

        const vatAmount = netAmount * (vatRate / 100);
        const grossAmount = netAmount + vatAmount;

        document.getElementById('vat_amount').value = vatAmount.toFixed(2);
        document.getElementById('gross_amount').value = grossAmount.toFixed(2);
    }

    document.getElementById('net_amount').addEventListener('input', calculateAmounts);
    document.getElementById('vat_rate').addEventListener('change', calculateAmounts);

    // Obsługa formularza
    document.getElementById('invoice-form').addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());

        // Usuń puste wartości
        Object.keys(data).forEach(key => {
            if (data[key] === '' || data[key] === null) {
                delete data[key];
            }
        });

        // Konwertuj liczby
        ['net_amount', 'vat_amount', 'gross_amount'].forEach(field => {
            if (data[field]) {
                data[field] = parseFloat(data[field]);
            }
        });

        // Ustaw status płatności
        if (data.payment_date) {
            data.payment_status = 'paid';
        } else {
            data.payment_status = 'pending';
        }

        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Zapisywanie...';

        try {
            const response = await fetch('/api/invoices', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                alert('Faktura została zapisana pomyślnie');
                window.location.href = '/invoices/' + result.id;
            } else {
                alert('Błąd: ' + result.error);
                submitBtn.disabled = false;
                submitBtn.textContent = 'Zapisz fakturę';
            }
        } catch (error) {
            alert('Błąd: ' + error.message);
            submitBtn.disabled = false;
            submitBtn.textContent = 'Zapisz fakturę';
        }
    });
</script>

<style>
    h3 {
        margin-top: 2rem;
        margin-bottom: 1rem;
        padding-top: 1rem;
        border-top: 2px solid #e1e1e1;
        color: #0078d4;
    }
    h3:first-of-type {
        margin-top: 1rem;
        border-top: none;
    }
</style>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
