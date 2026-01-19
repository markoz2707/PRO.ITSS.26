<?php

namespace ITSS\Models;

use ITSS\Core\Database;

class ProjectRevenue
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne('SELECT * FROM project_revenues WHERE id = :id', ['id' => $id]);
    }

    public function getByProject(int $projectId, ?string $revenueType = null): array
    {
        $sql = '
            SELECT pr.*, i.invoice_number, ii.item_name
            FROM project_revenues pr
            LEFT JOIN invoices i ON pr.invoice_id = i.id
            LEFT JOIN invoice_items ii ON pr.invoice_item_id = ii.id
            WHERE pr.project_id = :project_id
        ';

        $params = ['project_id' => $projectId];

        if ($revenueType) {
            $sql .= ' AND pr.revenue_type = :revenue_type';
            $params['revenue_type'] = $revenueType;
        }

        $sql .= ' ORDER BY pr.revenue_date DESC';

        return $this->db->fetchAll($sql, $params);
    }

    public function create(array $data, int $userId): int
    {
        return $this->db->insert('project_revenues', [
            'project_id' => $data['project_id'],
            'revenue_type' => $data['revenue_type'],
            'revenue_category' => $data['revenue_category'] ?? null,
            'revenue_name' => $data['revenue_name'],
            'revenue_description' => $data['revenue_description'] ?? null,
            'invoice_id' => $data['invoice_id'] ?? null,
            'invoice_item_id' => $data['invoice_item_id'] ?? null,
            'net_amount' => $data['net_amount'],
            'vat_amount' => $data['vat_amount'] ?? 0,
            'gross_amount' => $data['gross_amount'],
            'currency' => $data['currency'] ?? 'PLN',
            'revenue_date' => $data['revenue_date'],
            'client_name' => $data['client_name'] ?? null,
            'business_type' => $data['business_type'] ?? null,
            'segment' => $data['segment'] ?? null,
            'sector' => $data['sector'] ?? null,
            'mpk_code' => $data['mpk_code'] ?? null,
            'przelewy_wyksztalci' => $data['przelewy_wyksztalci'] ?? null,
            'created_by' => $userId
        ]);
    }

    public function update(int $id, array $data): void
    {
        $updateData = [];
        $allowedFields = [
            'revenue_category', 'revenue_name', 'revenue_description', 'net_amount', 'vat_amount',
            'gross_amount', 'currency', 'revenue_date', 'client_name', 'business_type',
            'segment', 'sector', 'mpk_code', 'przelewy_wyksztalci'
        ];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        if (!empty($updateData)) {
            $this->db->update('project_revenues', $updateData, 'id = :id', ['id' => $id]);
        }
    }

    public function delete(int $id): void
    {
        $this->db->delete('project_revenues', 'id = :id', ['id' => $id]);
    }

    public function getTotalByProject(int $projectId, ?string $revenueType = null): array
    {
        $sql = '
            SELECT
                revenue_type,
                business_type,
                SUM(net_amount) as total_net,
                SUM(vat_amount) as total_vat,
                SUM(gross_amount) as total_gross,
                COUNT(*) as items_count
            FROM project_revenues
            WHERE project_id = :project_id
        ';

        $params = ['project_id' => $projectId];

        if ($revenueType) {
            $sql .= ' AND revenue_type = :revenue_type';
            $params['revenue_type'] = $revenueType;
        }

        $sql .= ' GROUP BY revenue_type, business_type';

        return $this->db->fetchAll($sql, $params);
    }

    public function getByDateRange(int $projectId, string $startDate, string $endDate): array
    {
        $sql = '
            SELECT * FROM project_revenues
            WHERE project_id = :project_id
            AND revenue_date BETWEEN :start_date AND :end_date
            ORDER BY revenue_date DESC
        ';

        return $this->db->fetchAll($sql, [
            'project_id' => $projectId,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
    }

    public function getSummaryByBusinessType(int $projectId): array
    {
        $sql = '
            SELECT
                business_type,
                segment,
                sector,
                SUM(net_amount) as total_net,
                COUNT(*) as items_count
            FROM project_revenues
            WHERE project_id = :project_id
            GROUP BY business_type, segment, sector
            ORDER BY total_net DESC
        ';

        return $this->db->fetchAll($sql, ['project_id' => $projectId]);
    }

    public function getBySegment(string $segment): array
    {
        $sql = '
            SELECT pr.*, p.project_number, p.project_name
            FROM project_revenues pr
            LEFT JOIN projects p ON pr.project_id = p.id
            WHERE pr.segment = :segment
            ORDER BY pr.revenue_date DESC
        ';

        return $this->db->fetchAll($sql, ['segment' => $segment]);
    }
}
