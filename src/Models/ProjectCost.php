<?php

namespace ITSS\Models;

use ITSS\Core\Database;

class ProjectCost
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne('SELECT * FROM project_costs WHERE id = :id', ['id' => $id]);
    }

    public function getByProject(int $projectId, ?string $costType = null): array
    {
        $sql = '
            SELECT pc.*, i.invoice_number, ii.item_name
            FROM project_costs pc
            LEFT JOIN invoices i ON pc.invoice_id = i.id
            LEFT JOIN invoice_items ii ON pc.invoice_item_id = ii.id
            WHERE pc.project_id = :project_id
        ';

        $params = ['project_id' => $projectId];

        if ($costType) {
            $sql .= ' AND pc.cost_type = :cost_type';
            $params['cost_type'] = $costType;
        }

        $sql .= ' ORDER BY pc.cost_date DESC';

        return $this->db->fetchAll($sql, $params);
    }

    public function create(array $data, int $userId): int
    {
        return $this->db->insert('project_costs', [
            'project_id' => $data['project_id'],
            'cost_type' => $data['cost_type'],
            'cost_category' => $data['cost_category'] ?? null,
            'cost_name' => $data['cost_name'],
            'cost_description' => $data['cost_description'] ?? null,
            'invoice_id' => $data['invoice_id'] ?? null,
            'invoice_item_id' => $data['invoice_item_id'] ?? null,
            'net_amount' => $data['net_amount'],
            'vat_amount' => $data['vat_amount'] ?? 0,
            'gross_amount' => $data['gross_amount'],
            'currency' => $data['currency'] ?? 'PLN',
            'cost_date' => $data['cost_date'],
            'contractor' => $data['contractor'] ?? null,
            'mpk_code' => $data['mpk_code'] ?? null,
            'przelewy_wyksztalci' => $data['przelewy_wyksztalci'] ?? null,
            'created_by' => $userId
        ]);
    }

    public function update(int $id, array $data): void
    {
        $updateData = [];
        $allowedFields = [
            'cost_category', 'cost_name', 'cost_description', 'net_amount', 'vat_amount',
            'gross_amount', 'currency', 'cost_date', 'contractor', 'mpk_code', 'przelewy_wyksztalci'
        ];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        if (!empty($updateData)) {
            $this->db->update('project_costs', $updateData, 'id = :id', ['id' => $id]);
        }
    }

    public function delete(int $id): void
    {
        $this->db->delete('project_costs', 'id = :id', ['id' => $id]);
    }

    public function getTotalByProject(int $projectId, ?string $costType = null): array
    {
        $sql = '
            SELECT
                cost_type,
                SUM(net_amount) as total_net,
                SUM(vat_amount) as total_vat,
                SUM(gross_amount) as total_gross,
                COUNT(*) as items_count
            FROM project_costs
            WHERE project_id = :project_id
        ';

        $params = ['project_id' => $projectId];

        if ($costType) {
            $sql .= ' AND cost_type = :cost_type';
            $params['cost_type'] = $costType;
        }

        $sql .= ' GROUP BY cost_type';

        return $this->db->fetchAll($sql, $params);
    }

    public function getByDateRange(int $projectId, string $startDate, string $endDate): array
    {
        $sql = '
            SELECT * FROM project_costs
            WHERE project_id = :project_id
            AND cost_date BETWEEN :start_date AND :end_date
            ORDER BY cost_date DESC
        ';

        return $this->db->fetchAll($sql, [
            'project_id' => $projectId,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
    }

    public function getSummaryByCategory(int $projectId): array
    {
        $sql = '
            SELECT
                cost_category,
                cost_type,
                SUM(net_amount) as total_net,
                COUNT(*) as items_count
            FROM project_costs
            WHERE project_id = :project_id
            GROUP BY cost_category, cost_type
            ORDER BY total_net DESC
        ';

        return $this->db->fetchAll($sql, ['project_id' => $projectId]);
    }
}
