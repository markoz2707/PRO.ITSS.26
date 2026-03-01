<?php
use ITSS\Models\Document;

$documentModel = new Document();
$documents = $documentModel->getAll();

$pageTitle = 'Dokumenty - ITSS Project Management';
ob_start();
?>

<h2>Zarządzanie dokumentami</h2>

<div class="card">
    <div class="card-header">
        Lista dokumentów
        <div style="float: right;">
            <button class="btn btn-primary" onclick="showUploadForm()">Dodaj dokument</button>
        </div>
    </div>

    <div id="uploadForm" class="card" style="display: none; margin: 1.5rem; background: #f8f9fa;">
        <div class="card-header">Wgraj nowy dokument</div>
        <div style="padding: 1rem;">
            <form id="docUploadForm" enctype="multipart/form-data">
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <div class="form-group" style="flex: 1; min-width: 250px;">
                        <label for="document">Plik</label>
                        <input type="file" name="document" class="form-control" required>
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 200px;">
                        <label for="document_type">Typ dokumentu</label>
                        <select name="document_type" class="form-control" required>
                            <option value="contract">Kontrakt / Umowa</option>
                            <option value="invoice_attachment">Załącznik do faktury</option>
                            <option value="acceptance_protocol">Protokół odbioru</option>
                            <option value="other">Inny</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="description">Opis</label>
                    <input type="text" name="description" class="form-control" placeholder="Krótki opis dokumentu">
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="btn btn-success" id="uploadBtn">Wyślij plik</button>
                    <button type="button" class="btn btn-secondary" onclick="showUploadForm()">Anuluj</button>
                </div>
            </form>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Nazwa pliku</th>
                <th>Typ</th>
                <th>Powiązanie</th>
                <th>Rozmiar</th>
                <th>Data dodania</th>
                <th>Dodane przez</th>
                <th>Akcje</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($documents)): ?>
                <tr><td colspan="7" style="text-align: center;">Brak wgranych dokumentów.</td></tr>
            <?php endif; ?>
            <?php foreach ($documents as $doc): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($doc['document_name']); ?></strong></td>
                <td>
                    <span class="badge badge-info">
                        <?php echo match($doc['document_type']) {
                            'contract' => 'Umowa',
                            'invoice_attachment' => 'Załącznik FV',
                            'acceptance_protocol' => 'Protokół',
                            'other' => 'Inny',
                            default => $doc['document_type']
                        }; ?>
                    </span>
                </td>
                <td>
                    <?php if ($doc['project_number']): ?>
                        P: <?php echo htmlspecialchars($doc['project_number']); ?>
                    <?php elseif ($doc['invoice_number']): ?>
                        FV: <?php echo htmlspecialchars($doc['invoice_number']); ?>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td><?php echo round($doc['file_size'] / 1024 / 1024, 2); ?> MB</td>
                <td><?php echo date('Y-m-d H:i', strtotime($doc['created_at'])); ?></td>
                <td><?php echo htmlspecialchars($doc['user_name'] ?? 'System'); ?></td>
                <td>
                    <a href="/api/documents/download/<?php echo $doc['id']; ?>" class="btn btn-sm btn-primary">Pobierz</a>
                    <button class="btn btn-sm btn-danger" onclick="deleteDoc(<?php echo $doc['id']; ?>)">Usuń</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
    function showUploadForm() {
        const form = document.getElementById('uploadForm');
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }

    document.getElementById('docUploadForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('uploadBtn');
        btn.disabled = true;
        btn.textContent = 'Przesyłanie...';

        const formData = new FormData(this);

        try {
            const response = await fetch('/api/documents/upload', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                alert('Dokument został wgrany.');
                location.reload();
            } else {
                alert('Błąd: ' + result.error);
                btn.disabled = false;
                btn.textContent = 'Wyślij plik';
            }
        } catch (error) {
            alert('Błąd: ' + error.message);
            btn.disabled = false;
            btn.textContent = 'Wyślij plik';
        }
    });

    function deleteDoc(id) {
        if (!confirm('Czy na pewno chcesz usunąć ten dokument?')) return;
        alert('Funkcja usuwania w przygotowaniu.');
    }
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
