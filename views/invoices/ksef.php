<?php
$pageTitle = 'Import faktur z KSeF - ITSS Project Management';
ob_start();
?>

<div class="container-fluid px-4">
    <h2 class="mt-4">Import z KSeF</h2>
    
    <div style="margin-bottom: 20px;">
        <a href="/invoices" class="btn btn-secondary">← Powrót do listy faktur</a>
    </div>

    <div style="display: flex; gap: 20px;">
        <div class="card" style="flex: 1;">
            <div class="card-header">
                Import z pliku XML (FA2)
            </div>
            <div style="padding: 15px;">
                <form id="ksefImportForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="ksef_file">Wybierz plik XML z KSeF</label>
                        <input class="form-control" type="file" id="ksef_file" name="ksef_file" accept=".xml" required>
                        <small style="color: #666;">Wybierz plik XML w formacie FA(2) pobrany z aplikacji podatnika KSeF.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="invoice_type">Typ faktury</label>
                        <select class="form-control" id="invoice_type" name="invoice_type">
                            <option value="cost">Kosztowa (Zakup)</option>
                            <option value="revenue">Przychodowa (Sprzedaż)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="project_id">Przypisz do projektu (opcjonalnie)</label>
                        <select class="form-control" id="project_id" name="project_id">
                            <option value="">-- Wybierz projekt --</option>
                            <!-- Opcje zostaną załadowane przez JS -->
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary" id="importBtn">
                        Importuj plik
                    </button>
                </form>

                <div id="importResult" style="display: none; margin-top: 15px;"></div>
            </div>
        </div>
        
        <div class="card" style="flex: 1;">
            <div class="card-header">
                Pobierz z API KSeF (Bezpośrednio)
            </div>
            <div style="padding: 15px;">
                <p style="color: #666; margin-bottom: 15px;">Funkcja bezpośredniego pobierania z API KSeF na podstawie tokena autoryzacyjnego z config.php.</p>
                
                <form id="ksefApiForm">
                    <div class="form-group">
                        <label for="date_from">Data od</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" required>
                    </div>
                    <div class="form-group">
                        <label for="date_to">Data do</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" required>
                    </div>
                    
                    <button type="submit" class="btn btn-secondary" id="apiSyncBtn">
                        Szukaj faktur w KSeF
                    </button>
                </form>

                <div id="apiResult" style="display: none; margin-top: 15px;"></div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    fetch('/api/projects?status=active')
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const select = document.getElementById('project_id');
                result.data.forEach(project => {
                    const option = document.createElement('option');
                    option.value = project.id;
                    option.textContent = `${project.project_number} - ${project.project_name}`;
                    select.appendChild(option);
                });
            }
        });

    document.getElementById('ksefApiForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const submitBtn = document.getElementById('apiSyncBtn');
        const resultDiv = document.getElementById('apiResult');

        submitBtn.disabled = true;
        submitBtn.textContent = 'Szukanie...';
        resultDiv.style.display = 'none';

        fetch('/api/invoices/ksef/api-sync', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Szukaj faktur w KSeF';
            resultDiv.style.display = 'block';

            if (result.success) {
                let html = `<div style="padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; color: #155724; border-radius: 4px;">`;
                html += `<h4 style="margin-bottom: 10px;">Znaleziono faktur: ${result.invoices.length}</h4>`;
                
                if (result.invoices.length > 0) {
                    html += '<table class="table table-sm mt-3"><thead><tr><th>Data</th><th>Numer KSeF</th><th>Sprzedawca</th><th>Brutto</th><th>Akcja</th></tr></thead><tbody>';
                    result.invoices.forEach(inv => {
                        html += `<tr>
                            <td>${inv.invoicingDate}</td>
                            <td><small>${inv.ksefReferenceNumber}</small></td>
                            <td>${inv.subjectBy.name}</td>
                            <td>${inv.grossAmount}</td>
                            <td><button onclick="downloadKsef('${inv.ksefReferenceNumber}')" class="btn btn-sm btn-primary">Pobierz</button></td>
                        </tr>`;
                    });
                    html += '</tbody></table>';
                }
                
                html += `</div>`;
                resultDiv.innerHTML = html;
            } else {
                resultDiv.innerHTML = `
                    <div style="padding: 15px; background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; border-radius: 4px;">
                        <strong>Błąd:</strong> ${result.error}
                    </div>
                `;
            }
        })
        .catch(error => {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Szukaj faktur w KSeF';
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = `
                <div style="padding: 15px; background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; border-radius: 4px;">
                    <strong>Błąd sieci:</strong> Problem z połączeniem do serwera.
                </div>
            `;
        });
    });

    document.getElementById('ksefImportForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const fileInput = document.getElementById('ksef_file');
        if (!fileInput.files.length) {
            alert('Wybierz plik XML');
            return;
        }

        const formData = new FormData(this);
        const submitBtn = document.getElementById('importBtn');
        const resultDiv = document.getElementById('importResult');
        
        submitBtn.disabled = true;
        submitBtn.textContent = 'Importowanie...';
        resultDiv.style.display = 'none';

        fetch('/api/invoices/ksef/import-xml', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Importuj plik';
            
            resultDiv.style.display = 'block';
            if (result.success) {
                resultDiv.innerHTML = `
                    <div style="padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; color: #155724; border-radius: 4px;">
                        <h4 style="margin-bottom: 10px;">Sukces!</h4>
                        <p>Zimportowano fakturę: <strong>${result.invoice.invoice_number}</strong></p>
                        <ul style="margin: 10px 0; padding-left: 20px;">
                            <li>Sprzedawca: ${result.invoice.supplier_name}</li>
                            <li>Data: ${result.invoice.invoice_date}</li>
                            <li>Kwota brutto: ${result.invoice.gross_amount} ${result.invoice.currency}</li>
                        </ul>
                        <a href="/invoices/${result.invoice_id}" class="btn btn-success" style="font-size: 0.85rem;">Przejdź do faktury</a>
                    </div>
                `;
                this.reset();
            } else {
                resultDiv.innerHTML = `
                    <div style="padding: 15px; background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; border-radius: 4px;">
                        <strong>Błąd:</strong> ${result.error}
                    </div>
                `;
            }
        })
        .catch(error => {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Importuj plik';
            
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = `
                <div style="padding: 15px; background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; border-radius: 4px;">
                    <strong>Błąd serwera:</strong> Wystąpił problem podczas komunikacji.
                </div>
            `;
            console.error('Error:', error);
        });
    });
});
</script>

<script>
async function downloadKsef(ksefId) {
    if (!confirm(`Czy pobrać fakturę o numerze ${ksefId} bezpośrednio z KSeF?`)) return;
    
    try {
        const response = await fetch('/api/invoices/ksef/download', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ksef_id: ksefId })
        });
        
        const result = await response.json();
        if (result.success) {
            alert('Faktura została pomyślnie pobrana i zimportowana.');
            window.location.href = '/invoices/' + result.invoice_id;
        } else {
            alert('Błąd: ' + result.error);
        }
    } catch (error) {
        alert('Błąd sieci: ' + error.message);
    }
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
