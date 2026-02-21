<?php

namespace ITSS\Services;

use ITSS\Core\Database;
use ITSS\Core\Logger;
use ITSS\Models\Project;
use ITSS\Models\ServiceDeskContract;
use ITSS\Models\ServiceDeskProject;
use ITSS\Models\DataReconciliationLog;

class DataReconciliationService
{
    private Project $projectModel;
    private ServiceDeskContract $contractModel;
    private ServiceDeskProject $sdProjectModel;
    private DataReconciliationLog $logModel;
    private Database $db;

    public function __construct()
    {
        $this->projectModel = new Project();
        $this->contractModel = new ServiceDeskContract();
        $this->sdProjectModel = new ServiceDeskProject();
        $this->logModel = new DataReconciliationLog();
        $this->db = Database::getInstance();
    }

    // =========================================================================
    // Podgląd dopasowań (Preview)
    // =========================================================================

    /**
     * Generuje podgląd proponowanych dopasowań między projektami CRM a danymi SD
     */
    public function getReconciliationPreview(): array
    {
        $crmProjects = $this->projectModel->getAll();
        $sdContracts = $this->contractModel->getAll();
        $sdProjects = $this->sdProjectModel->getAll();

        $matches = [];
        $unmatchedCrm = [];
        $unmatchedContracts = [];
        $unmatchedSdProjects = [];

        $matchedContractIds = [];
        $matchedSdProjectIds = [];

        foreach ($crmProjects as $project) {
            $contractMatch = $this->findBestContractMatch($project, $sdContracts);
            $sdProjectMatch = $this->findBestSDProjectMatch($project, $sdProjects);

            if ($contractMatch || $sdProjectMatch) {
                $match = [
                    'project' => $project,
                    'contract_match' => $contractMatch,
                    'sd_project_match' => $sdProjectMatch,
                    'mergeable_fields' => $this->getMergeableFields($project, $contractMatch, $sdProjectMatch)
                ];
                $matches[] = $match;

                if ($contractMatch) {
                    $matchedContractIds[] = $contractMatch['item']['id'];
                }
                if ($sdProjectMatch) {
                    $matchedSdProjectIds[] = $sdProjectMatch['item']['id'];
                }
            } else {
                $unmatchedCrm[] = $project;
            }
        }

        foreach ($sdContracts as $contract) {
            if (!in_array($contract['id'], $matchedContractIds) && $contract['project_id'] === null) {
                $unmatchedContracts[] = $contract;
            }
        }

        foreach ($sdProjects as $sdProject) {
            if (!in_array($sdProject['id'], $matchedSdProjectIds) && $sdProject['project_id'] === null) {
                $unmatchedSdProjects[] = $sdProject;
            }
        }

        return [
            'matches' => $matches,
            'unmatched_crm_projects' => $unmatchedCrm,
            'unmatched_sd_contracts' => $unmatchedContracts,
            'unmatched_sd_projects' => $unmatchedSdProjects,
            'stats' => [
                'total_crm_projects' => count($crmProjects),
                'total_sd_contracts' => count($sdContracts),
                'total_sd_projects' => count($sdProjects),
                'matched' => count($matches),
                'unmatched_crm' => count($unmatchedCrm),
                'unmatched_contracts' => count($unmatchedContracts),
                'unmatched_sd_projects' => count($unmatchedSdProjects)
            ]
        ];
    }

    // =========================================================================
    // Automatyczne dopasowywanie
    // =========================================================================

    private function findBestContractMatch(array $project, array $contracts): ?array
    {
        // Już powiązany
        if (!empty($project['servicedesk_contract_id'])) {
            foreach ($contracts as $c) {
                if ($c['sd_contract_id'] === $project['servicedesk_contract_id']) {
                    return ['item' => $c, 'confidence' => 100, 'method' => 'existing_link'];
                }
            }
        }

        $bestMatch = null;
        $bestScore = 0;

        foreach ($contracts as $contract) {
            // Pomiń już powiązane
            if ($contract['project_id'] !== null) {
                continue;
            }

            $score = $this->calculateMatchScore(
                $project['project_name'],
                $project['project_number'],
                $contract['contract_name'],
                $contract['contract_number'] ?? '',
                $contract['account_name'] ?? ''
            );

            if ($score > $bestScore && $score >= 40) {
                $bestScore = $score;
                $bestMatch = $contract;
            }
        }

        if ($bestMatch) {
            return [
                'item' => $bestMatch,
                'confidence' => $bestScore,
                'method' => $bestScore >= 80 ? 'name_exact' : ($bestScore >= 60 ? 'name_similar' : 'name_partial')
            ];
        }

        return null;
    }

    private function findBestSDProjectMatch(array $project, array $sdProjects): ?array
    {
        // Już powiązany
        if (!empty($project['servicedesk_project_id'])) {
            foreach ($sdProjects as $sp) {
                if ($sp['sd_project_id'] === $project['servicedesk_project_id']) {
                    return ['item' => $sp, 'confidence' => 100, 'method' => 'existing_link'];
                }
            }
        }

        $bestMatch = null;
        $bestScore = 0;

        foreach ($sdProjects as $sdProject) {
            if ($sdProject['project_id'] !== null) {
                continue;
            }

            $score = $this->calculateMatchScore(
                $project['project_name'],
                $project['project_number'],
                $sdProject['project_name'],
                $sdProject['project_code'] ?? '',
                $sdProject['owner_name'] ?? ''
            );

            if ($score > $bestScore && $score >= 40) {
                $bestScore = $score;
                $bestMatch = $sdProject;
            }
        }

        if ($bestMatch) {
            return [
                'item' => $bestMatch,
                'confidence' => $bestScore,
                'method' => $bestScore >= 80 ? 'name_exact' : ($bestScore >= 60 ? 'name_similar' : 'name_partial')
            ];
        }

        return null;
    }

    /**
     * Oblicza score dopasowania 0-100
     */
    private function calculateMatchScore(
        string $projectName,
        string $projectNumber,
        string $targetName,
        string $targetCode,
        string $targetExtra
    ): float {
        $score = 0;

        $normProjectName = $this->normalize($projectName);
        $normTargetName = $this->normalize($targetName);
        $normProjectNumber = $this->normalize($projectNumber);
        $normTargetCode = $this->normalize($targetCode);

        // Dokładne dopasowanie numeru projektu do kodu
        if ($normProjectNumber && $normTargetCode && $normProjectNumber === $normTargetCode) {
            return 95;
        }

        // Numer projektu zawarty w nazwie
        if ($normProjectNumber && str_contains($normTargetName, $normProjectNumber)) {
            return 90;
        }

        // Kod zawarty w nazwie projektu
        if ($normTargetCode && str_contains($normProjectName, $normTargetCode)) {
            return 85;
        }

        // Dokładne dopasowanie nazw
        if ($normProjectName === $normTargetName) {
            return 95;
        }

        // Porównanie podobieństwa tekstu
        similar_text($normProjectName, $normTargetName, $percent);
        $score = max($score, $percent * 0.8);

        // Dopasowanie tokenów (słów)
        $projectTokens = $this->tokenize($projectName);
        $targetTokens = array_merge($this->tokenize($targetName), $this->tokenize($targetExtra));

        if (!empty($projectTokens) && !empty($targetTokens)) {
            $commonTokens = array_intersect($projectTokens, $targetTokens);
            $tokenScore = (count($commonTokens) / max(count($projectTokens), 1)) * 100;
            $score = max($score, $tokenScore * 0.7);
        }

        // Dopasowanie Levenshtein dla krótkich nazw
        if (strlen($normProjectName) < 100 && strlen($normTargetName) < 100) {
            $maxLen = max(strlen($normProjectName), strlen($normTargetName), 1);
            $distance = levenshtein($normProjectName, $normTargetName);
            $levScore = (1 - $distance / $maxLen) * 100;
            $score = max($score, $levScore * 0.6);
        }

        return round($score, 2);
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^a-z0-9ąćęłńóśźż\s]/', '', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    private function tokenize(string $text): array
    {
        $text = $this->normalize($text);
        $tokens = explode(' ', $text);
        // Odrzuć krótkie tokeny (spójniki, przyimki)
        return array_values(array_filter($tokens, fn($t) => strlen($t) > 2));
    }

    // =========================================================================
    // Pola do scalenia (Mergeable fields)
    // =========================================================================

    private function getMergeableFields(array $project, ?array $contractMatch, ?array $sdProjectMatch): array
    {
        $fields = [];

        if ($contractMatch) {
            $contract = $contractMatch['item'];

            if (empty($project['sd_contract_value']) && !empty($contract['cost'])) {
                $fields[] = [
                    'field' => 'sd_contract_value',
                    'label' => 'Wartość umowy',
                    'source' => 'contract',
                    'current_value' => $project['sd_contract_value'] ?? null,
                    'new_value' => $contract['cost']
                ];
            }
            if (empty($project['sd_contract_type']) && !empty($contract['contract_type'])) {
                $fields[] = [
                    'field' => 'sd_contract_type',
                    'label' => 'Typ umowy',
                    'source' => 'contract',
                    'current_value' => $project['sd_contract_type'] ?? null,
                    'new_value' => $contract['contract_type']
                ];
            }
            if (empty($project['sd_sla_name']) && !empty($contract['sla_name'])) {
                $fields[] = [
                    'field' => 'sd_sla_name',
                    'label' => 'SLA',
                    'source' => 'contract',
                    'current_value' => $project['sd_sla_name'] ?? null,
                    'new_value' => $contract['sla_name']
                ];
            }
            if (empty($project['sd_support_type']) && !empty($contract['support_type'])) {
                $fields[] = [
                    'field' => 'sd_support_type',
                    'label' => 'Typ wsparcia',
                    'source' => 'contract',
                    'current_value' => $project['sd_support_type'] ?? null,
                    'new_value' => $contract['support_type']
                ];
            }
            if (empty($project['start_date']) && !empty($contract['start_date'])) {
                $fields[] = [
                    'field' => 'start_date',
                    'label' => 'Data rozpoczęcia',
                    'source' => 'contract',
                    'current_value' => $project['start_date'],
                    'new_value' => $contract['start_date']
                ];
            }
            if (empty($project['end_date']) && !empty($contract['end_date'])) {
                $fields[] = [
                    'field' => 'end_date',
                    'label' => 'Data zakończenia',
                    'source' => 'contract',
                    'current_value' => $project['end_date'],
                    'new_value' => $contract['end_date']
                ];
            }
            if (empty($project['description']) && !empty($contract['description'])) {
                $fields[] = [
                    'field' => 'description',
                    'label' => 'Opis',
                    'source' => 'contract',
                    'current_value' => $project['description'],
                    'new_value' => $contract['description']
                ];
            }
        }

        if ($sdProjectMatch) {
            $sdProject = $sdProjectMatch['item'];

            if (!empty($sdProject['scheduled_hours'])) {
                $fields[] = [
                    'field' => 'sd_scheduled_hours',
                    'label' => 'Godziny zaplanowane (SD)',
                    'source' => 'sd_project',
                    'current_value' => $project['sd_scheduled_hours'] ?? null,
                    'new_value' => $sdProject['scheduled_hours']
                ];
            }
            if (!empty($sdProject['actual_hours'])) {
                $fields[] = [
                    'field' => 'sd_actual_hours',
                    'label' => 'Godziny zrealizowane (SD)',
                    'source' => 'sd_project',
                    'current_value' => $project['sd_actual_hours'] ?? null,
                    'new_value' => $sdProject['actual_hours']
                ];
            }
            if (!empty($sdProject['percentage_completion'])) {
                $fields[] = [
                    'field' => 'sd_completion_percent',
                    'label' => 'Procent ukończenia (SD)',
                    'source' => 'sd_project',
                    'current_value' => $project['sd_completion_percent'] ?? null,
                    'new_value' => $sdProject['percentage_completion']
                ];
            }
            if (empty($project['start_date']) && !empty($sdProject['start_date'])) {
                // Tylko jeśli nie ustawiono z kontraktu
                $alreadySet = false;
                foreach ($fields as $f) {
                    if ($f['field'] === 'start_date') {
                        $alreadySet = true;
                        break;
                    }
                }
                if (!$alreadySet) {
                    $fields[] = [
                        'field' => 'start_date',
                        'label' => 'Data rozpoczęcia',
                        'source' => 'sd_project',
                        'current_value' => $project['start_date'],
                        'new_value' => $sdProject['start_date']
                    ];
                }
            }
            if (empty($project['end_date']) && !empty($sdProject['end_date'])) {
                $alreadySet = false;
                foreach ($fields as $f) {
                    if ($f['field'] === 'end_date') {
                        $alreadySet = true;
                        break;
                    }
                }
                if (!$alreadySet) {
                    $fields[] = [
                        'field' => 'end_date',
                        'label' => 'Data zakończenia',
                        'source' => 'sd_project',
                        'current_value' => $project['end_date'],
                        'new_value' => $sdProject['end_date']
                    ];
                }
            }
        }

        return $fields;
    }

    // =========================================================================
    // Wykonanie scalenia (Execute merge)
    // =========================================================================

    /**
     * Wykonuje scalenie automatycznie zaproponowanych dopasowań
     */
    public function executeAutoReconciliation(int $userId): array
    {
        $preview = $this->getReconciliationPreview();
        $results = ['merged' => 0, 'skipped' => 0, 'errors' => []];

        foreach ($preview['matches'] as $match) {
            $contractMatch = $match['contract_match'];
            $sdProjectMatch = $match['sd_project_match'];

            // Scalaj tylko wysokopewne dopasowania
            $contractConfidence = $contractMatch ? $contractMatch['confidence'] : 0;
            $sdProjectConfidence = $sdProjectMatch ? $sdProjectMatch['confidence'] : 0;
            $maxConfidence = max($contractConfidence, $sdProjectConfidence);

            if ($maxConfidence < 70) {
                $results['skipped']++;
                continue;
            }

            try {
                $this->mergeProjectData(
                    $match['project']['id'],
                    $contractMatch ? $contractMatch['item'] : null,
                    $sdProjectMatch ? $sdProjectMatch['item'] : null,
                    $match['mergeable_fields'],
                    $userId,
                    'auto_match',
                    $maxConfidence
                );
                $results['merged']++;
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'project_id' => $match['project']['id'],
                    'project_name' => $match['project']['project_name'],
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Ręczne powiązanie projektu z kontraktem/projektem SD
     */
    public function manualLink(
        int $projectId,
        ?int $sdContractLocalId,
        ?int $sdProjectLocalId,
        array $selectedFields,
        int $userId
    ): void {
        $project = $this->projectModel->findById($projectId);
        if (!$project) {
            throw new \RuntimeException('Projekt nie istnieje');
        }

        $contract = null;
        $sdProject = null;

        if ($sdContractLocalId) {
            $contract = $this->contractModel->findById($sdContractLocalId);
            if (!$contract) {
                throw new \RuntimeException('Kontrakt ServiceDesk nie istnieje');
            }
        }

        if ($sdProjectLocalId) {
            $sdProject = $this->sdProjectModel->findById($sdProjectLocalId);
            if (!$sdProject) {
                throw new \RuntimeException('Projekt ServiceDesk nie istnieje');
            }
        }

        $this->mergeProjectData(
            $projectId,
            $contract,
            $sdProject,
            $selectedFields,
            $userId,
            'manual_match',
            100
        );
    }

    /**
     * Rozłącza projekt od kontraktu/projektu SD
     */
    public function unlinkProject(int $projectId, string $unlinkType, int $userId): void
    {
        $project = $this->projectModel->findById($projectId);
        if (!$project) {
            throw new \RuntimeException('Projekt nie istnieje');
        }

        $this->db->beginTransaction();

        try {
            $updateData = [];

            if ($unlinkType === 'contract' || $unlinkType === 'all') {
                if ($project['servicedesk_contract_id']) {
                    $contract = $this->contractModel->findBySdContractId($project['servicedesk_contract_id']);
                    if ($contract) {
                        $this->contractModel->unlinkFromProject($contract['id']);
                    }
                    $updateData['servicedesk_contract_id'] = null;
                    $updateData['sd_contract_value'] = null;
                    $updateData['sd_contract_type'] = null;
                    $updateData['sd_sla_name'] = null;
                    $updateData['sd_support_type'] = null;
                }
            }

            if ($unlinkType === 'sd_project' || $unlinkType === 'all') {
                if ($project['servicedesk_project_id']) {
                    $sdProject = $this->sdProjectModel->findBySdProjectId($project['servicedesk_project_id']);
                    if ($sdProject) {
                        $this->sdProjectModel->unlinkFromProject($sdProject['id']);
                    }
                    $updateData['servicedesk_project_id'] = null;
                    $updateData['sd_scheduled_hours'] = null;
                    $updateData['sd_actual_hours'] = null;
                    $updateData['sd_completion_percent'] = null;
                }
            }

            if (!empty($updateData)) {
                $updateData['data_source'] = 'crm';
                $updateData['sd_last_sync_at'] = null;
                $this->projectModel->update($projectId, $updateData);
            }

            $this->logModel->create([
                'reconciliation_type' => 'unlink',
                'project_id' => $projectId,
                'crm_id' => $project['crm_id'],
                'sd_project_id' => $project['servicedesk_project_id'],
                'sd_contract_id' => $project['servicedesk_contract_id'],
                'status' => 'applied',
                'performed_by' => $userId,
                'performed_at' => date('Y-m-d H:i:s'),
                'notes' => "Rozłączono: {$unlinkType}"
            ]);

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    // =========================================================================
    // Wewnętrzna logika scalania
    // =========================================================================

    private function mergeProjectData(
        int $projectId,
        ?array $contract,
        ?array $sdProject,
        array $mergeableFields,
        int $userId,
        string $reconciliationType,
        float $confidence
    ): void {
        $project = $this->projectModel->findById($projectId);
        $this->db->beginTransaction();

        try {
            $updateData = [];
            $fieldsBefore = [];
            $fieldsAfter = [];
            $fieldsUpdated = [];

            // Powiąż kontrakt
            if ($contract) {
                $updateData['servicedesk_contract_id'] = $contract['sd_contract_id'];
                $this->contractModel->linkToProject($contract['id'], $projectId);
            }

            // Powiąż projekt SD
            if ($sdProject) {
                $updateData['servicedesk_project_id'] = $sdProject['sd_project_id'];
                $this->sdProjectModel->linkToProject($sdProject['id'], $projectId);
            }

            // Aktualizuj pola
            foreach ($mergeableFields as $field) {
                $fieldName = $field['field'];
                $newValue = $field['new_value'];

                $fieldsBefore[$fieldName] = $project[$fieldName] ?? null;
                $fieldsAfter[$fieldName] = $newValue;
                $fieldsUpdated[] = $fieldName;
                $updateData[$fieldName] = $newValue;
            }

            $updateData['data_source'] = 'reconciled';
            $updateData['sd_last_sync_at'] = date('Y-m-d H:i:s');

            $this->projectModel->update($projectId, $updateData);

            // Log
            $this->logModel->create([
                'reconciliation_type' => $reconciliationType,
                'project_id' => $projectId,
                'crm_id' => $project['crm_id'] ?? null,
                'sd_project_id' => $sdProject['sd_project_id'] ?? null,
                'sd_contract_id' => $contract['sd_contract_id'] ?? null,
                'match_confidence' => $confidence,
                'match_method' => $reconciliationType,
                'fields_updated' => $fieldsUpdated,
                'fields_before' => $fieldsBefore,
                'fields_after' => $fieldsAfter,
                'status' => 'applied',
                'performed_by' => $userId,
                'performed_at' => date('Y-m-d H:i:s')
            ]);

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            Logger::error('Reconciliation merge failed', [
                'project_id' => $projectId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    // =========================================================================
    // Historia i statystyki
    // =========================================================================

    public function getReconciliationHistory(int $limit = 100): array
    {
        return $this->logModel->getAll(null, $limit);
    }

    public function getReconciliationStats(): array
    {
        $logStats = $this->logModel->getStats();

        $linkedProjects = $this->db->fetchOne(
            'SELECT COUNT(*) as cnt FROM projects
             WHERE servicedesk_project_id IS NOT NULL OR servicedesk_contract_id IS NOT NULL'
        );

        $totalProjects = $this->db->fetchOne('SELECT COUNT(*) as cnt FROM projects');

        return array_merge($logStats, [
            'linked_projects' => (int)($linkedProjects['cnt'] ?? 0),
            'total_projects' => (int)($totalProjects['cnt'] ?? 0),
            'sd_contracts_total' => $this->contractModel->getCount(),
            'sd_contracts_unlinked' => $this->contractModel->getUnlinkedCount(),
            'sd_projects_total' => $this->sdProjectModel->getCount(),
            'sd_projects_unlinked' => $this->sdProjectModel->getUnlinkedCount()
        ]);
    }
}
