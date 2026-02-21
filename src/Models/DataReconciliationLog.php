<?php

namespace ITSS\Models;

use ITSS\Core\Database;

class DataReconciliationLog
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create(array $data): int
    {
        return $this->db->insert('data_reconciliation_log', [
            'reconciliation_type' => $data['reconciliation_type'],
            'project_id' => $data['project_id'] ?? null,
            'crm_id' => $data['crm_id'] ?? null,
            'sd_project_id' => $data['sd_project_id'] ?? null,
            'sd_contract_id' => $data['sd_contract_id'] ?? null,
            'match_confidence' => $data['match_confidence'] ?? null,
            'match_method' => $data['match_method'] ?? null,
            'fields_updated' => isset($data['fields_updated']) ? json_encode($data['fields_updated']) : null,
            'fields_before' => isset($data['fields_before']) ? json_encode($data['fields_before']) : null,
            'fields_after' => isset($data['fields_after']) ? json_encode($data['fields_after']) : null,
            'status' => $data['status'] ?? 'pending',
            'performed_by' => $data['performed_by'] ?? null,
            'performed_at' => $data['performed_at'] ?? null,
            'notes' => $data['notes'] ?? null
        ]);
    }

    public function updateStatus(int $id, string $status, ?int $userId = null): void
    {
        $updateData = ['status' => $status];
        if ($userId) {
            $updateData['performed_by'] = $userId;
            $updateData['performed_at'] = date('Y-m-d H:i:s');
        }

        $this->db->update('data_reconciliation_log', $updateData,
            'id = :id', ['id' => $id]);
    }

    public function getAll(?string $status = null, int $limit = 100): array
    {
        $sql = 'SELECT drl.*, p.project_number, p.project_name,
                       u.first_name as performed_by_first_name, u.last_name as performed_by_last_name
                FROM data_reconciliation_log drl
                LEFT JOIN projects p ON drl.project_id = p.id
                LEFT JOIN users u ON drl.performed_by = u.id';

        $params = [];
        if ($status) {
            $sql .= ' WHERE drl.status = :status';
            $params['status'] = $status;
        }

        $sql .= ' ORDER BY drl.created_at DESC LIMIT ' . (int)$limit;

        return $this->db->fetchAll($sql, $params);
    }

    public function getByProject(int $projectId): array
    {
        return $this->db->fetchAll(
            'SELECT drl.*, u.first_name as performed_by_first_name, u.last_name as performed_by_last_name
             FROM data_reconciliation_log drl
             LEFT JOIN users u ON drl.performed_by = u.id
             WHERE drl.project_id = :project_id
             ORDER BY drl.created_at DESC',
            ['project_id' => $projectId]
        );
    }

    public function getPending(): array
    {
        return $this->getAll('pending');
    }

    public function getStats(): array
    {
        $sql = 'SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = "applied" THEN 1 ELSE 0 END) as applied,
                    SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected,
                    SUM(CASE WHEN reconciliation_type = "auto_match" THEN 1 ELSE 0 END) as auto_matches,
                    SUM(CASE WHEN reconciliation_type = "manual_match" THEN 1 ELSE 0 END) as manual_matches,
                    AVG(match_confidence) as avg_confidence
                FROM data_reconciliation_log';

        return $this->db->fetchOne($sql) ?? [];
    }
}
