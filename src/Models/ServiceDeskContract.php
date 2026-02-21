<?php

namespace ITSS\Models;

use ITSS\Core\Database;

class ServiceDeskContract
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            'SELECT sc.*, p.project_number, p.project_name as local_project_name
             FROM servicedesk_contracts sc
             LEFT JOIN projects p ON sc.project_id = p.id
             WHERE sc.id = :id',
            ['id' => $id]
        );
    }

    public function findBySdContractId(string $sdContractId): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM servicedesk_contracts WHERE sd_contract_id = :sd_contract_id',
            ['sd_contract_id' => $sdContractId]
        );
    }

    public function getAll(?string $status = null): array
    {
        $sql = 'SELECT sc.*, p.project_number, p.project_name as local_project_name
                FROM servicedesk_contracts sc
                LEFT JOIN projects p ON sc.project_id = p.id';

        $params = [];
        if ($status) {
            $sql .= ' WHERE sc.status = :status';
            $params['status'] = $status;
        }

        $sql .= ' ORDER BY sc.updated_at DESC';

        return $this->db->fetchAll($sql, $params);
    }

    public function getUnlinked(): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM servicedesk_contracts WHERE project_id IS NULL ORDER BY contract_name ASC'
        );
    }

    public function getLinked(): array
    {
        return $this->db->fetchAll(
            'SELECT sc.*, p.project_number, p.project_name as local_project_name
             FROM servicedesk_contracts sc
             INNER JOIN projects p ON sc.project_id = p.id
             ORDER BY sc.contract_name ASC'
        );
    }

    public function upsertFromSD(string $sdContractId, array $data): int
    {
        $existing = $this->findBySdContractId($sdContractId);

        $contractData = [
            'sd_contract_id' => $sdContractId,
            'contract_name' => $data['contract_name'],
            'contract_number' => $data['contract_number'] ?? null,
            'account_name' => $data['account_name'] ?? null,
            'contract_type' => $data['contract_type'] ?? null,
            'status' => $data['status'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'cost' => $data['cost'] ?? null,
            'currency' => $data['currency'] ?? 'PLN',
            'description' => $data['description'] ?? null,
            'vendor_name' => $data['vendor_name'] ?? null,
            'support_type' => $data['support_type'] ?? null,
            'sla_name' => $data['sla_name'] ?? null,
            'notification_before_days' => $data['notification_before_days'] ?? null,
            'sd_raw_data' => isset($data['raw_data']) ? json_encode($data['raw_data']) : null,
            'last_sync_at' => date('Y-m-d H:i:s')
        ];

        if ($existing) {
            $this->db->update('servicedesk_contracts', $contractData,
                'id = :id', ['id' => $existing['id']]);
            return $existing['id'];
        }

        return $this->db->insert('servicedesk_contracts', $contractData);
    }

    public function linkToProject(int $id, int $projectId): void
    {
        $this->db->update('servicedesk_contracts',
            ['project_id' => $projectId],
            'id = :id', ['id' => $id]);
    }

    public function unlinkFromProject(int $id): void
    {
        $this->db->update('servicedesk_contracts',
            ['project_id' => null],
            'id = :id', ['id' => $id]);
    }

    public function searchByName(string $query): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM servicedesk_contracts
             WHERE contract_name LIKE :query OR account_name LIKE :query2
             ORDER BY contract_name ASC',
            ['query' => "%{$query}%", 'query2' => "%{$query}%"]
        );
    }

    public function getCount(): int
    {
        $result = $this->db->fetchOne('SELECT COUNT(*) as cnt FROM servicedesk_contracts');
        return (int)($result['cnt'] ?? 0);
    }

    public function getUnlinkedCount(): int
    {
        $result = $this->db->fetchOne(
            'SELECT COUNT(*) as cnt FROM servicedesk_contracts WHERE project_id IS NULL'
        );
        return (int)($result['cnt'] ?? 0);
    }
}
