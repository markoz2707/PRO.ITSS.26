<?php
$pageTitle = 'Import faktur z CSV - ITSS Project Management';
ob_start();
?>

<h2>Import faktur z pliku CSV</h2>

<div class="card">
    <div class="card-header">Importuj faktury</div>

    <form id="import-form" enctype="multipart/form-data">
        <div class="form-group">
            <label>Plik CSV</label>
            <input type="file" name="csv_file" accept=".csv" class="form-control" required>
            <small>Format: CSV z separatorem średnika (;)</small>
        </div>

        <div class="form-group">
            <label>Typ faktury</label>
            <select name="invoice_type" class="form-control" required>
                <option value="revenue">Przychód</option>
                <option value="cost">Koszt</option>
            </select>
        </div>

        <div class="form-group">
            <label>Separator</label>
            <select name="delimiter" class="form-control">
                <option value=";">Średnik (;)</option>
                <option value=",">Przecinek (,)</option>
                <option value="|">Kreska pionowa (|)</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Importuj</button>
        <a href="/invoices" class="btn btn-secondary">Anuluj</a>
    </form>
</div>

<div id="progress" style="display: none; margin-top: 2rem;">
    <div class="card">
        <div class="card-header">Postęp importu</div>
        <div id="progress-content">
            <p>Importowanie faktur...</p>
        </div>
    </div>
</div>

<div class="card" style="margin-top: 2rem;">
    <div class="card-header">Format pliku CSV</div>

    <p>Plik CSV powinien zawierać następujące kolumny:</p>

    <h4>Kolumny obowiązkowe:</h4>
    <ul>
        <li><strong>NUMER</strong> lub <strong>Nazwa</strong> - Numer faktury</li>
        <li><strong>KONTRAHENT</strong> - Nazwa kontrahenta</li>
        <li><strong>DATA WYSTAWIENIA</strong> lub <strong>DATA SPRZEDAŻY</strong> - Data faktury</li>
        <li><strong>NETTO</strong> - Kwota netto</li>
        <li><strong>BRUTTO</strong> - Kwota brutto</li>
    </ul>

    <h4>Kolumny opcjonalne:</h4>
    <ul>
        <li><strong>PROJECT</strong> lub <strong>PROJEKT</strong> - Numer projektu</li>
        <li><strong>DATA PŁATNOŚCI</strong> - Termin płatności</li>
        <li><strong>DATA ZAPŁATY</strong> - Data zapłaty</li>
        <li><strong>KATEGORIA</strong> - Kategoria faktury</li>
        <li><strong>OPIS DOKUMENTU</strong> - Opis</li>
        <li><strong>Business Type</strong> - Typ biznesowy</li>
        <li><strong>Segment</strong> - Segment</li>
        <li><strong>Sector</strong> - Sektor</li>
        <li><strong>MPK-DH1</strong>, <strong>MPK-DH2</strong>, <strong>MPK-GNP</strong>, itp. - Kody MPK</li>
        <li><strong>OPER.KU/KLIENTA</strong> lub <strong>OPIEKUN HANDLOWY</strong> - Operator/opiekun</li>
        <li><strong>UWAGI</strong> - Uwagi</li>
        <li><strong>Baza Licze</strong> - Baza licencji</li>
    </ul>

    <h4>Przykład formatu (nagłówek CSV):</h4>
    <pre style="background: #f5f5f5; padding: 1rem; border-radius: 4px; overflow-x: auto;">Nazwa;KONTRAHENT;DATA WYSTAWIENIA;DATA PŁATNOŚCI;NETTO;BRUTTO;KATEGORIA;OPIS DOKUMENTU;Business Type;Segment;MPK-DH1</pre>
</div>

<script>
    document.getElementById('import-form').addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const progressDiv = document.getElementById('progress');
        const progressContent = document.getElementById('progress-content');

        progressDiv.style.display = 'block';
        progressContent.innerHTML = '<p>Importowanie faktur... Proszę czekać.</p>';

        document.querySelector('button[type="submit"]').disabled = true;

        try {
            const response = await fetch('/api/invoices/import', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                let html = '<div style="color: green;">';
                html += `<h3>✓ Import zakończony pomyślnie</h3>`;
                html += `<p><strong>Zaimportowano:</strong> ${result.imported} faktur</p>`;

                if (result.skipped > 0) {
                    html += `<p><strong>Pominięto:</strong> ${result.skipped} wierszy</p>`;
                }

                if (result.errors && result.errors.length > 0) {
                    html += `<h4>Błędy (${result.errors.length}):</h4><ul>`;
                    result.errors.slice(0, 20).forEach(error => {
                        html += `<li>${error}</li>`;
                    });
                    if (result.errors.length > 20) {
                        html += `<li><em>... i ${result.errors.length - 20} więcej</em></li>`;
                    }
                    html += '</ul>';
                }

                html += '</div>';
                html += '<p style="margin-top: 1rem;"><a href="/invoices" class="btn btn-primary">Przejdź do listy faktur</a></p>';

                progressContent.innerHTML = html;
            } else {
                progressContent.innerHTML = `
                    <div style="color: red;">
                        <h3>✗ Błąd importu</h3>
                        <p>${result.error}</p>
                        ${result.errors && result.errors.length > 0 ? `
                            <h4>Szczegóły błędów:</h4>
                            <ul>
                                ${result.errors.slice(0, 10).map(e => `<li>${e}</li>`).join('')}
                            </ul>
                        ` : ''}
                    </div>
                    <p style="margin-top: 1rem;"><button onclick="location.reload()" class="btn btn-secondary">Spróbuj ponownie</button></p>
                `;
            }
        } catch (error) {
            progressContent.innerHTML = `
                <div style="color: red;">
                    <h3>✗ Błąd</h3>
                    <p>${error.message}</p>
                </div>
                <p style="margin-top: 1rem;"><button onclick="location.reload()" class="btn btn-secondary">Spróbuj ponownie</button></p>
            `;
        } finally {
            document.querySelector('button[type="submit"]').disabled = false;
        }
    });
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
