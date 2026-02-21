<?php

namespace ITSS\Models;

use ITSS\Core\Database;

class Project
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        $sql = '
            SELECT
                p.*,
                s.first_name as salesperson_first_name,
                s.last_name as salesperson_last_name,
                a.first_name as architect_first_name,
                a.last_name as architect_last_name
            FROM projects p
            LEFT JOIN users s ON p.salesperson_id = s.id
            LEFT JOIN users a ON p.architect_id = a.id
            WHERE p.id = :id
        ';

        return $this->db->fetchOne($sql, ['id' => $id]);
    }

    public function findByProjectNumber(string $projectNumber): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM projects WHERE project_number = :project_number',
            ['project_number' => $projectNumber]
        );
    }

    public function findByCrmId(string $crmId): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM projects WHERE crm_id = :crm_id',
            ['crm_id' => $crmId]
        );
    }

    public function getAll(?string $status = null): array
    {
        $sql = '
            SELECT
                p.*,
                s.first_name as salesperson_first_name,
                s.last_name as salesperson_last_name,
                a.first_name as architect_first_name,
                a.last_name as architect_last_name
            FROM projects p
            LEFT JOIN users s ON p.salesperson_id = s.id
            LEFT JOIN users a ON p.architect_id = a.id
        ';

        $params = [];
        if ($status) {
            $sql .= ' WHERE p.status = :status';
            $params['status'] = $status;
        }

        $sql .= ' ORDER BY p.created_at DESC';

        return $this->db->fetchAll($sql, $params);
    }

    public function create(array $data): int
    {
        return $this->db->insert('projects', [
            'project_number' => $data['project_number'],
            'project_name' => $data['project_name'],
            'crm_id' => $data['crm_id'] ?? null,
            'salesperson_id' => $data['salesperson_id'] ?? null,
            'architect_id' => $data['architect_id'] ?? null,
            'status' => $data['status'] ?? 'planning',
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'description' => $data['description'] ?? null
        ]);
    }

    public function update(int $id, array $data): void
    {
        $updateData = [];
        $allowedFields = ['project_name', 'salesperson_id', 'architect_id', 'status',
                          'start_date', 'end_date', 'description', 'last_sync_at',
                          'servicedesk_project_id', 'servicedesk_contract_id',
                          'sd_contract_value', 'sd_contract_type', 'sd_sla_name',
                          'sd_support_type', 'sd_scheduled_hours', 'sd_actual_hours',
                          'sd_completion_percent', 'sd_last_sync_at', 'data_source'];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        if (!empty($updateData)) {
            $this->db->update('projects', $updateData, 'id = :id', ['id' => $id]);
        }
    }

    public function updateFromCRM(string $crmId, array $data): void
    {
        $project = $this->findByCrmId($crmId);

        if ($project) {
            $this->update($project['id'], array_merge($data, ['last_sync_at' => date('Y-m-d H:i:s')]));
        } else {
            $this->create(array_merge($data, [
                'crm_id' => $crmId,
                'last_sync_at' => date('Y-m-d H:i:s')
            ]));
        }
    }

    public function getProjectFinancials(int $projectId): array
    {
        $sql = '
            SELECT
                SUM(CASE WHEN invoice_type = "revenue" AND payment_status = "paid" THEN net_amount ELSE 0 END) as total_revenue,
                SUM(CASE WHEN invoice_type = "cost" AND payment_status = "paid" THEN net_amount ELSE 0 END) as total_costs,
                SUM(CASE WHEN invoice_type = "revenue" AND payment_status = "pending" THEN net_amount ELSE 0 END) as pending_revenue,
                SUM(CASE WHEN invoice_type = "cost" AND payment_status = "pending" THEN net_amount ELSE 0 END) as pending_costs
            FROM invoices
            WHERE project_id = :project_id
        ';

        $result = $this->db->fetchOne($sql, ['project_id' => $projectId]);

        $totalRevenue = floatval($result['total_revenue'] ?? 0);
        $totalCosts = floatval($result['total_costs'] ?? 0);
        $margin1 = $totalRevenue - $totalCosts;

        $laborCosts = $this->getProjectLaborCosts($projectId);
        $margin2 = $margin1 - $laborCosts;

        return [
            'total_revenue' => $totalRevenue,
            'total_costs' => $totalCosts,
            'pending_revenue' => floatval($result['pending_revenue'] ?? 0),
            'pending_costs' => floatval($result['pending_costs'] ?? 0),
            'margin_1' => $margin1,
            'labor_costs' => $laborCosts,
            'margin_2' => $margin2,
            'margin_1_percent' => $totalRevenue > 0 ? ($margin1 / $totalRevenue) * 100 : 0,
            'margin_2_percent' => $totalRevenue > 0 ? ($margin2 / $totalRevenue) * 100 : 0
        ];
    }

    public function getProjectLaborCosts(int $projectId): float
    {
        // This would calculate labor costs based on hours worked
        // For now, returning 0 - to be implemented with actual labor cost calculation
        return 0.0;
    }

    public function getProjectWorkHours(int $projectId): array
    {
        $sql = '
            SELECT
                work_type,
                SUM(hours) as total_hours,
                COUNT(DISTINCT user_id) as unique_users
            FROM work_hours
            WHERE project_id = :project_id
            GROUP BY work_type
        ';

        return $this->db->fetchAll($sql, ['project_id' => $projectId]);
    }

    public function delete(int $id): void
    {
        $this->db->delete('projects', 'id = :id', ['id' => $id]);
    }
}
