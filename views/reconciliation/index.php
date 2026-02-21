<?php
use ITSS\Models\ServiceDeskContract;
use ITSS\Models\ServiceDeskProject;
use ITSS\Models\Project;

$contractModel = new ServiceDeskContract();
$sdProjectModel = new ServiceDeskProject();
$projectModel = new Project();

$sdContractsCount = $contractModel->getCount();
$sdContractsUnlinked = $contractModel->getUnlinkedCount();
$sdProjectsCount = $sdProjectModel->getCount();
$sdProjectsUnlinked = $sdProjectModel->getUnlinkedCount();
$allProjects = $projectModel->getAll();

$totalProjects = count($allProjects);
$linkedProjects = 0;
foreach ($allProjects as $p) {
    if (!empty($p['servicedesk_project_id']) || !empty($p['servicedesk_contract_id'])) {
        $linkedProjects++;
    }
}

$pageTitle = 'Uspójnianie danych - ITSS Project Management';
ob_start();
?>

<h2>Uspójnianie danych CRM &harr; ServiceDesk Plus</h2>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-value"><?php echo $totalProjects; ?></div>
        <div class="stat-label">Projekty CRM</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo $sdContractsCount; ?></div>
        <div class="stat-label">Umowy SD (<?php echo $sdContractsUnlinked; ?> niepowiązanych)</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo $sdProjectsCount; ?></div>
        <div class="stat-label">Projekty SD (<?php echo $sdProjectsUnlinked; ?> niepowiązanych)</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color: <?php echo $linkedProjects > 0 ? '#28a745' : '#6c757d'; ?>">
            <?php echo $linkedProjects; ?> / <?php echo $totalProjects; ?>
        </div>
        <div class="stat-label">Projekty uspójnione</div>
    </div>
</div>

<!-- Synchronizacja -->
<div class="card">
    <div class="card-header">
        1. Synchronizacja danych ze źródeł
    </div>
    <p style="margin-bottom: 1rem; color: #6c757d; font-size: 0.9rem;">
        Przed uspójnianiem upewnij się, że dane z obu systemów są aktualne.
    </p>
    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
        <button onclick="syncCRMData()" class="btn btn-primary" id="btn-sync-crm">
            Synchronizuj CRM
        </button>
        <button onclick="syncSDContracts()" class="btn btn-warning" id="btn-sync-contracts">
            Synchronizuj umowy SD
        </button>
        <button onclick="syncSDProjects()" class="btn btn-success" id="btn-sync-sd-projects">
            Synchronizuj projekty SD
        </button>
        <button onclick="syncAll()" class="btn btn-secondary" id="btn-sync-all">
            Synchronizuj wszystko
        </button>
    </div>
    <div id="sync-status-recon" style="margin-top: 1rem;"></div>
</div>

<!-- Zakładki -->
<div class="card">
    <div class="card-header">
        2. Przeglądanie i scalanie danych
    </div>

    <div class="tabs">
        <button class="tab active" onclick="switchTab('tab-preview')">Podgląd dopasowań</button>
        <button class="tab" onclick="switchTab('tab-unmatched')">Niepowiązane elementy</button>
        <button class="tab" onclick="switchTab('tab-manual')">Ręczne powiązanie</button>
        <button class="tab" onclick="switchTab('tab-history')">Historia</button>
    </div>

    <!-- TAB: Podgląd dopasowań -->
    <div id="tab-preview" class="tab-content active">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <p style="color: #6c757d; font-size: 0.9rem; margin: 0;">
                System automatycznie wyszukuje dopasowania między projektami CRM a umowami/projektami w ServiceDesk.
            </p>
            <div style="display: flex; gap: 0.5rem;">
                <button onclick="loadPreview()" class="btn btn-primary btn-sm">Odśwież podgląd</button>
                <button onclick="executeAutoReconciliation()" class="btn btn-success btn-sm" id="btn-auto-recon">
                    Scal automatycznie (pewność &ge;70%)
                </button>
            </div>
        </div>
        <div id="preview-results">
            <div class="alert alert-info">
                <span class="loading-spinner"></span> Ładowanie podglądu dopasowań...
            </div>
        </div>
    </div>

    <!-- TAB: Niepowiązane -->
    <div id="tab-unmatched" class="tab-content">
        <div id="unmatched-results">
            <div class="alert alert-info">Kliknij "Odśwież podgląd" aby załadować dane.</div>
        </div>
    </div>

    <!-- TAB: Ręczne powiązanie -->
    <div id="tab-manual" class="tab-content">
        <div class="alert alert-info">
            Wybierz projekt CRM i ręcznie powiąż go z umową lub projektem z ServiceDesk.
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
            <div>
                <div class="form-group">
                    <label>Projekt CRM:</label>
                    <select id="manual-project-id" class="form-control" onchange="loadManualProjectDetails()">
                        <option value="">-- Wybierz projekt --</option>
                        <?php foreach ($allProjects as $p): ?>
                        <option value="<?php echo $p['id']; ?>">
                            <?php echo htmlspecialchars($p['project_number'] . ' - ' . $p['project_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="manual-project-info" style="display: none;"></div>
            </div>
            <div>
                <div class="form-group">
                    <label>Umowa ServiceDesk (opcjonalnie):</label>
                    <select id="manual-contract-id" class="form-control">
                        <option value="">-- Brak --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Projekt ServiceDesk (opcjonalnie):</label>
                    <select id="manual-sd-project-id" class="form-control">
                        <option value="">-- Brak --</option>
                    </select>
                </div>
                <button onclick="executeManualLink()" class="btn btn-primary" id="btn-manual-link">
                    Powiąż i scal
                </button>
            </div>
        </div>
        <div id="manual-result" style="margin-top: 1rem;"></div>
    </div>

    <!-- TAB: Historia -->
    <div id="tab-history" class="tab-content">
        <div id="history-results">
            <div class="alert alert-info">Ładowanie historii...</div>
        </div>
    </div>
</div>

<script>
// =========================================================================
// Zarządzanie zakładkami
// =========================================================================
function switchTab(tabId) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));

    document.getElementById(tabId).classList.add('active');
    event.target.classList.add('active');

    if (tabId === 'tab-history') loadHistory();
    if (tabId === 'tab-manual') loadManualDropdowns();
}

// =========================================================================
// Synchronizacja
// =========================================================================
async function syncCRMData() {
    const btn = document.getElementById('btn-sync-crm');
    const statusDiv = document.getElementById('sync-status-recon');
    btn.disabled = true;
    statusDiv.innerHTML = '<span class="loading-spinner"></span> Synchronizacja CRM...';

    try {
        const r = await fetch('/api/sync/crm', { method: 'POST' });
        const d = await r.json();
        statusDiv.innerHTML = d.success
            ? `<span style="color:green;">Zsynchronizowano ${d.synced_count} projektów z CRM</span>`
            : `<span style="color:red;">Błąd CRM: ${d.error}</span>`;
    } catch(e) {
        statusDiv.innerHTML = `<span style="color:red;">Błąd: ${e.message}</span>`;
    }
    btn.disabled = false;
}

async function syncSDContracts() {
    const btn = document.getElementById('btn-sync-contracts');
    const statusDiv = document.getElementById('sync-status-recon');
    btn.disabled = true;
    statusDiv.innerHTML = '<span class="loading-spinner"></span> Synchronizacja umów SD...';

    try {
        const r = await fetch('/api/sync/servicedesk-contracts', { method: 'POST' });
        const d = await r.json();
        statusDiv.innerHTML = d.success
            ? `<span style="color:green;">Zsynchronizowano ${d.synced_count} umów z ServiceDesk</span>`
            : `<span style="color:red;">Błąd: ${d.error}</span>`;
    } catch(e) {
        statusDiv.innerHTML = `<span style="color:red;">Błąd: ${e.message}</span>`;
    }
    btn.disabled = false;
}

async function syncSDProjects() {
    const btn = document.getElementById('btn-sync-sd-projects');
    const statusDiv = document.getElementById('sync-status-recon');
    btn.disabled = true;
    statusDiv.innerHTML = '<span class="loading-spinner"></span> Synchronizacja projektów SD...';

    try {
        const r = await fetch('/api/sync/servicedesk-projects', { method: 'POST' });
        const d = await r.json();
        statusDiv.innerHTML = d.success
            ? `<span style="color:green;">Zsynchronizowano ${d.synced_count} projektów z ServiceDesk</span>`
            : `<span style="color:red;">Błąd: ${d.error}</span>`;
    } catch(e) {
        statusDiv.innerHTML = `<span style="color:red;">Błąd: ${e.message}</span>`;
    }
    btn.disabled = false;
}

async function syncAll() {
    const statusDiv = document.getElementById('sync-status-recon');
    const btn = document.getElementById('btn-sync-all');
    btn.disabled = true;
    statusDiv.innerHTML = '<span class="loading-spinner"></span> Synchronizacja wszystkich źródeł...';

    const results = [];
    try {
        const [crm, contracts, sdProjects] = await Promise.all([
            fetch('/api/sync/crm', { method: 'POST' }).then(r => r.json()),
            fetch('/api/sync/servicedesk-contracts', { method: 'POST' }).then(r => r.json()),
            fetch('/api/sync/servicedesk-projects', { method: 'POST' }).then(r => r.json())
        ]);

        const msgs = [];
        if (crm.success) msgs.push(`CRM: ${crm.synced_count} projektów`);
        else msgs.push(`CRM: błąd`);
        if (contracts.success) msgs.push(`Umowy SD: ${contracts.synced_count}`);
        else msgs.push(`Umowy SD: błąd`);
        if (sdProjects.success) msgs.push(`Projekty SD: ${sdProjects.synced_count}`);
        else msgs.push(`Projekty SD: błąd`);

        statusDiv.innerHTML = `<span style="color:green;">Synchronizacja zakończona: ${msgs.join(' | ')}</span>`;
        loadPreview();
    } catch(e) {
        statusDiv.innerHTML = `<span style="color:red;">Błąd synchronizacji: ${e.message}</span>`;
    }
    btn.disabled = false;
}

// =========================================================================
// Podgląd dopasowań
// =========================================================================
let previewData = null;

async function loadPreview() {
    const container = document.getElementById('preview-results');
    container.innerHTML = '<div class="alert alert-info"><span class="loading-spinner"></span> Analizowanie dopasowań...</div>';

    try {
        const r = await fetch('/api/reconciliation/preview');
        const d = await r.json();

        if (!d.success) {
            container.innerHTML = `<div class="alert alert-warning">Błąd: ${d.error}</div>`;
            return;
        }

        previewData = d.data;
        renderPreview(d.data);
        renderUnmatched(d.data);
    } catch(e) {
        container.innerHTML = `<div class="alert alert-warning">Błąd ładowania: ${e.message}</div>`;
    }
}

function renderPreview(data) {
    const container = document.getElementById('preview-results');
    const stats = data.stats;

    let html = `
        <div class="alert alert-info" style="display: flex; gap: 2rem; flex-wrap: wrap;">
            <span><strong>${stats.matched}</strong> dopasowań znalezionych</span>
            <span><strong>${stats.unmatched_crm}</strong> projektów CRM bez dopasowania</span>
            <span><strong>${stats.unmatched_contracts}</strong> umów SD niepowiązanych</span>
            <span><strong>${stats.unmatched_sd_projects}</strong> projektów SD niepowiązanych</span>
        </div>
    `;

    if (data.matches.length === 0) {
        html += '<p style="color: #6c757d;">Brak dopasowań do wyświetlenia. Zsynchronizuj dane ze źródeł.</p>';
    }

    data.matches.forEach((match, idx) => {
        const project = match.project;
        const cm = match.contract_match;
        const spm = match.sd_project_match;
        const maxConf = Math.max(cm ? cm.confidence : 0, spm ? spm.confidence : 0);
        const confColor = maxConf >= 80 ? '#28a745' : maxConf >= 60 ? '#ff9800' : '#dc3545';

        html += `<div class="match-card" id="match-${idx}">`;
        html += `<div class="match-header">`;
        html += `<strong>${esc(project.project_number)} - ${esc(project.project_name)}</strong>`;
        html += `<div class="confidence-bar">
            <span style="font-size:0.8rem;">Pewność: <strong style="color:${confColor}">${maxConf.toFixed(0)}%</strong></span>
            <div class="confidence-fill">
                <div class="confidence-fill-inner" style="width:${maxConf}%; background:${confColor};"></div>
            </div>
        </div>`;
        html += `</div>`;

        html += `<div class="match-columns">`;

        // CRM
        html += `<div class="match-column crm">
            <h4>CRM (Dynamics 365)</h4>
            <div><strong>${esc(project.project_number)}</strong></div>
            <div>${esc(project.project_name)}</div>
            <div>Status: ${esc(project.status || '-')}</div>
            <div>Od: ${project.start_date || '-'} Do: ${project.end_date || '-'}</div>
        </div>`;

        // Kontrakt
        if (cm) {
            html += `<div class="match-column contract">
                <h4>Umowa SD (${cm.confidence.toFixed(0)}%)</h4>
                <div><strong>${esc(cm.item.contract_name)}</strong></div>
                <div>Klient: ${esc(cm.item.account_name || '-')}</div>
                <div>Typ: ${esc(cm.item.contract_type || '-')}</div>
                <div>Wartość: ${cm.item.cost ? parseFloat(cm.item.cost).toLocaleString('pl-PL') + ' PLN' : '-'}</div>
                <div>SLA: ${esc(cm.item.sla_name || '-')}</div>
            </div>`;
        } else {
            html += `<div class="match-column contract" style="opacity:0.5;">
                <h4>Umowa SD</h4><div>Brak dopasowania</div>
            </div>`;
        }

        // Projekt SD
        if (spm) {
            html += `<div class="match-column sd-project">
                <h4>Projekt SD (${spm.confidence.toFixed(0)}%)</h4>
                <div><strong>${esc(spm.item.project_name)}</strong></div>
                <div>Kod: ${esc(spm.item.project_code || '-')}</div>
                <div>Właściciel: ${esc(spm.item.owner_name || '-')}</div>
                <div>Godziny: ${spm.item.scheduled_hours || '-'}h plan / ${spm.item.actual_hours || '-'}h real</div>
                <div>Ukończenie: ${spm.item.percentage_completion || '-'}%</div>
            </div>`;
        } else {
            html += `<div class="match-column sd-project" style="opacity:0.5;">
                <h4>Projekt SD</h4><div>Brak dopasowania</div>
            </div>`;
        }

        html += `</div>`;

        // Pola do scalenia
        if (match.mergeable_fields && match.mergeable_fields.length > 0) {
            html += `<div class="merge-fields">
                <strong style="font-size:0.85rem;">Pola do uzupełnienia:</strong>`;
            match.mergeable_fields.forEach(f => {
                const sourceLabel = f.source === 'contract' ? 'Umowa SD' : 'Projekt SD';
                html += `<div class="merge-field-row">
                    <span class="field-label">${esc(f.label)}</span>
                    <span class="field-current">${f.current_value || '(brak)'}</span>
                    <span class="field-arrow">&rarr;</span>
                    <span class="field-new">${esc(String(f.new_value))}</span>
                    <span class="badge badge-info" style="font-size:0.7rem;">${sourceLabel}</span>
                </div>`;
            });
            html += `</div>`;
        }

        html += `</div>`;
    });

    container.innerHTML = html;
}

function renderUnmatched(data) {
    const container = document.getElementById('unmatched-results');
    let html = '';

    if (data.unmatched_crm_projects.length > 0) {
        html += `<h4 style="margin-bottom:0.5rem;">Projekty CRM bez dopasowania (${data.unmatched_crm_projects.length})</h4>`;
        html += '<table><thead><tr><th>Numer</th><th>Nazwa</th><th>Status</th><th>Data od</th><th>Data do</th></tr></thead><tbody>';
        data.unmatched_crm_projects.forEach(p => {
            html += `<tr>
                <td>${esc(p.project_number)}</td>
                <td>${esc(p.project_name)}</td>
                <td>${esc(p.status || '-')}</td>
                <td>${p.start_date || '-'}</td>
                <td>${p.end_date || '-'}</td>
            </tr>`;
        });
        html += '</tbody></table><br>';
    }

    if (data.unmatched_sd_contracts.length > 0) {
        html += `<h4 style="margin-bottom:0.5rem;">Umowy SD niepowiązane (${data.unmatched_sd_contracts.length})</h4>`;
        html += '<table><thead><tr><th>Nazwa umowy</th><th>Klient</th><th>Typ</th><th>Wartość</th><th>Data od</th><th>Data do</th></tr></thead><tbody>';
        data.unmatched_sd_contracts.forEach(c => {
            html += `<tr>
                <td>${esc(c.contract_name)}</td>
                <td>${esc(c.account_name || '-')}</td>
                <td>${esc(c.contract_type || '-')}</td>
                <td>${c.cost ? parseFloat(c.cost).toLocaleString('pl-PL') + ' PLN' : '-'}</td>
                <td>${c.start_date || '-'}</td>
                <td>${c.end_date || '-'}</td>
            </tr>`;
        });
        html += '</tbody></table><br>';
    }

    if (data.unmatched_sd_projects.length > 0) {
        html += `<h4 style="margin-bottom:0.5rem;">Projekty SD niepowiązane (${data.unmatched_sd_projects.length})</h4>`;
        html += '<table><thead><tr><th>Nazwa projektu</th><th>Kod</th><th>Właściciel</th><th>Status</th><th>Godziny</th></tr></thead><tbody>';
        data.unmatched_sd_projects.forEach(sp => {
            html += `<tr>
                <td>${esc(sp.project_name)}</td>
                <td>${esc(sp.project_code || '-')}</td>
                <td>${esc(sp.owner_name || '-')}</td>
                <td>${esc(sp.status || '-')}</td>
                <td>${sp.scheduled_hours || '-'}h</td>
            </tr>`;
        });
        html += '</tbody></table>';
    }

    if (!html) {
        html = '<div class="alert alert-success">Wszystkie elementy zostały dopasowane.</div>';
    }

    container.innerHTML = html;
}

// =========================================================================
// Automatyczne scalanie
// =========================================================================
async function executeAutoReconciliation() {
    if (!confirm('Czy na pewno chcesz automatycznie scalić wszystkie dopasowania z pewnością >= 70%?')) {
        return;
    }

    const btn = document.getElementById('btn-auto-recon');
    btn.disabled = true;
    btn.innerHTML = '<span class="loading-spinner"></span> Scalanie...';

    try {
        const r = await fetch('/api/reconciliation/auto', { method: 'POST' });
        const d = await r.json();

        if (d.success) {
            const data = d.data;
            let msg = `Scalono: ${data.merged}, Pominięto: ${data.skipped}`;
            if (data.errors && data.errors.length > 0) {
                msg += `, Błędy: ${data.errors.length}`;
            }
            alert(msg);
            loadPreview();
            location.reload();
        } else {
            alert('Błąd: ' + d.error);
        }
    } catch(e) {
        alert('Błąd: ' + e.message);
    }

    btn.disabled = false;
    btn.innerHTML = 'Scal automatycznie (pewność &ge;70%)';
}

// =========================================================================
// Ręczne powiązanie
// =========================================================================
async function loadManualDropdowns() {
    try {
        const [contractsRes, projectsRes] = await Promise.all([
            fetch('/api/servicedesk/contracts').then(r => r.json()),
            fetch('/api/servicedesk/projects').then(r => r.json())
        ]);

        const contractSelect = document.getElementById('manual-contract-id');
        const sdProjectSelect = document.getElementById('manual-sd-project-id');

        contractSelect.innerHTML = '<option value="">-- Brak --</option>';
        if (contractsRes.success) {
            contractsRes.data.forEach(c => {
                const linked = c.local_project_name ? ` [powiązane: ${c.local_project_name}]` : '';
                contractSelect.innerHTML += `<option value="${c.id}">${esc(c.contract_name)}${linked}</option>`;
            });
        }

        sdProjectSelect.innerHTML = '<option value="">-- Brak --</option>';
        if (projectsRes.success) {
            projectsRes.data.forEach(p => {
                const linked = p.local_project_name ? ` [powiązane: ${p.local_project_name}]` : '';
                sdProjectSelect.innerHTML += `<option value="${p.id}">${esc(p.project_name)}${linked}</option>`;
            });
        }
    } catch(e) {
        console.error('Error loading dropdowns:', e);
    }
}

function loadManualProjectDetails() {
    const projectId = document.getElementById('manual-project-id').value;
    const infoDiv = document.getElementById('manual-project-info');

    if (!projectId) {
        infoDiv.style.display = 'none';
        return;
    }

    fetch(`/api/projects/${projectId}`)
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                const p = d.data;
                infoDiv.style.display = 'block';
                infoDiv.innerHTML = `
                    <div class="match-column crm" style="margin-top:0.5rem;">
                        <h4>Dane projektu CRM</h4>
                        <div>Numer: <strong>${esc(p.project_number)}</strong></div>
                        <div>Status: ${esc(p.status || '-')}</div>
                        <div>Data od: ${p.start_date || '-'}</div>
                        <div>Data do: ${p.end_date || '-'}</div>
                        <div>SD Kontrakt: ${p.servicedesk_contract_id || '<em>brak</em>'}</div>
                        <div>SD Projekt: ${p.servicedesk_project_id || '<em>brak</em>'}</div>
                    </div>`;
            }
        });
}

async function executeManualLink() {
    const projectId = document.getElementById('manual-project-id').value;
    const contractId = document.getElementById('manual-contract-id').value;
    const sdProjectId = document.getElementById('manual-sd-project-id').value;
    const resultDiv = document.getElementById('manual-result');

    if (!projectId) {
        resultDiv.innerHTML = '<div class="alert alert-warning">Wybierz projekt CRM.</div>';
        return;
    }
    if (!contractId && !sdProjectId) {
        resultDiv.innerHTML = '<div class="alert alert-warning">Wybierz umowę SD lub projekt SD do powiązania.</div>';
        return;
    }

    const btn = document.getElementById('btn-manual-link');
    btn.disabled = true;

    try {
        const r = await fetch('/api/reconciliation/link', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                project_id: projectId,
                sd_contract_id: contractId || null,
                sd_project_id: sdProjectId || null,
                selected_fields: []
            })
        });
        const d = await r.json();

        if (d.success) {
            resultDiv.innerHTML = '<div class="alert alert-success">Powiązanie utworzone pomyślnie.</div>';
            loadPreview();
        } else {
            resultDiv.innerHTML = `<div class="alert alert-warning">Błąd: ${d.error}</div>`;
        }
    } catch(e) {
        resultDiv.innerHTML = `<div class="alert alert-warning">Błąd: ${e.message}</div>`;
    }

    btn.disabled = false;
}

// =========================================================================
// Historia
// =========================================================================
async function loadHistory() {
    const container = document.getElementById('history-results');
    container.innerHTML = '<div class="alert alert-info"><span class="loading-spinner"></span> Ładowanie historii...</div>';

    try {
        const r = await fetch('/api/reconciliation/history?limit=50');
        const d = await r.json();

        if (!d.success || !d.data.length) {
            container.innerHTML = '<p style="color:#6c757d;">Brak wpisów w historii uspójniania.</p>';
            return;
        }

        let html = '<table><thead><tr><th>Data</th><th>Typ</th><th>Projekt</th><th>Pewność</th><th>Pola</th><th>Status</th><th>Wykonał</th></tr></thead><tbody>';

        d.data.forEach(entry => {
            const typeLabel = {
                'auto_match': 'Automatyczne',
                'manual_match': 'Ręczne',
                'merge': 'Scalenie',
                'unlink': 'Rozłączenie'
            }[entry.reconciliation_type] || entry.reconciliation_type;

            const typeBadge = {
                'auto_match': 'badge-info',
                'manual_match': 'badge-purple',
                'merge': 'badge-success',
                'unlink': 'badge-danger'
            }[entry.reconciliation_type] || 'badge-secondary';

            const statusBadge = {
                'applied': 'badge-success',
                'pending': 'badge-warning',
                'rejected': 'badge-danger',
                'reverted': 'badge-secondary'
            }[entry.status] || 'badge-info';

            const fields = entry.fields_updated ? JSON.parse(entry.fields_updated) : [];
            const performer = entry.performed_by_first_name
                ? `${entry.performed_by_first_name} ${entry.performed_by_last_name}`
                : '-';

            html += `<tr>
                <td>${entry.created_at ? new Date(entry.created_at).toLocaleString('pl-PL') : '-'}</td>
                <td><span class="badge ${typeBadge}">${typeLabel}</span></td>
                <td>${esc(entry.project_name || '-')}</td>
                <td>${entry.match_confidence ? entry.match_confidence + '%' : '-'}</td>
                <td>${fields.length > 0 ? fields.join(', ') : '-'}</td>
                <td><span class="badge ${statusBadge}">${entry.status}</span></td>
                <td>${esc(performer)}</td>
            </tr>`;
        });

        html += '</tbody></table>';
        container.innerHTML = html;
    } catch(e) {
        container.innerHTML = `<div class="alert alert-warning">Błąd: ${e.message}</div>`;
    }
}

// =========================================================================
// Narzędzia
// =========================================================================
function esc(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

// Załaduj podgląd na start
document.addEventListener('DOMContentLoaded', function() {
    loadPreview();
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
