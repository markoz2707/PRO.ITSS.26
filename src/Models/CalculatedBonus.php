<?php

namespace ITSS\Models;

use ITSS\Core\Database;

class CalculatedBonus
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        $sql = '
            SELECT cb.*, u.first_name, u.last_name, u.email,
                   p.project_number, p.project_name,
                   bs.bonus_type
            FROM calculated_bonuses cb
            LEFT JOIN users u ON cb.user_id = u.id
            LEFT JOIN projects p ON cb.project_id = p.id
            LEFT JOIN bonus_schemes bs ON cb.bonus_scheme_id = bs.id
            WHERE cb.id = :id
        ';

        return $this->db->fetchOne($sql, ['id' => $id]);
    }

    public function getByUser(int $userId, ?string $status = null): array
    {
        $sql = '
            SELECT cb.*, p.project_number, p.project_name, bs.bonus_type
            FROM calculated_bonuses cb
            LEFT JOIN projects p ON cb.project_id = p.id
            LEFT JOIN bonus_schemes bs ON cb.bonus_scheme_id = bs.id
            WHERE cb.user_id = :user_id
        ';

        $params = ['user_id' => $userId];

        if ($status) {
            $sql .= ' AND cb.status = :status';
            $params['status'] = $status;
        }

        $sql .= ' ORDER BY cb.period_end DESC';

        return $this->db->fetchAll($sql, $params);
    }

    public function getByProject(int $projectId, ?string $status = null): array
    {
        $sql = '
            SELECT cb.*, u.first_name, u.last_name, u.email, bs.bonus_type
            FROM calculated_bonuses cb
            LEFT JOIN users u ON cb.user_id = u.id
            LEFT JOIN bonus_schemes bs ON cb.bonus_scheme_id = bs.id
            WHERE cb.project_id = :project_id
        ';

        $params = ['project_id' => $projectId];

        if ($status) {
            $sql .= ' AND cb.status = :status';
            $params['status'] = $status;
        }

        $sql .= ' ORDER BY cb.period_end DESC';

        return $this->db->fetchAll($sql, $params);
    }

    public function create(array $data): int
    {
        return $this->db->insert('calculated_bonuses', [
            'user_id' => $data['user_id'],
            'project_id' => $data['project_id'] ?? null,
            'period_start' => $data['period_start'],
            'period_end' => $data['period_end'],
            'bonus_scheme_id' => $data['bonus_scheme_id'],
            'calculation_base' => $data['calculation_base'],
            'bonus_amount' => $data['bonus_amount'],
            'status' => $data['status'] ?? 'draft',
            'calculation_details' => json_encode($data['calculation_details'] ?? [])
        ]);
    }

    public function update(int $id, array $data): void
    {
        $updateData = [];
        $allowedFields = ['calculation_base', 'bonus_amount', 'status', 'calculation_details'];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                if ($field === 'calculation_details') {
                    $updateData[$field] = json_encode($data[$field]);
                } else {
                    $updateData[$field] = $data[$field];
                }
            }
        }

        if (!empty($updateData)) {
            $this->db->update('calculated_bonuses', $updateData, 'id = :id', ['id' => $id]);
        }
    }

    public function approve(int $id, int $approvedBy): void
    {
        $this->db->update('calculated_bonuses', [
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $id]);
    }

    public function markAsPaid(int $id): void
    {
        $this->db->update('calculated_bonuses', [
            'status' => 'paid',
            'paid_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $id]);
    }

    public function delete(int $id): void
    {
        $this->db->delete('calculated_bonuses', 'id = :id', ['id' => $id]);
    }

    public function getAll(?string $status = null, ?string $periodStart = null, ?string $periodEnd = null): array
    {
        $sql = '
            SELECT cb.*, u.first_name, u.last_name, u.email,
                   p.project_number, p.project_name, bs.bonus_type
            FROM calculated_bonuses cb
            LEFT JOIN users u ON cb.user_id = u.id
            LEFT JOIN projects p ON cb.project_id = p.id
            LEFT JOIN bonus_schemes bs ON cb.bonus_scheme_id = bs.id
            WHERE 1=1
        ';

        $params = [];

        if ($status) {
            $sql .= ' AND cb.status = :status';
            $params['status'] = $status;
        }

        if ($periodStart) {
            $sql .= ' AND cb.period_start >= :period_start';
            $params['period_start'] = $periodStart;
        }

        if ($periodEnd) {
            $sql .= ' AND cb.period_end <= :period_end';
            $params['period_end'] = $periodEnd;
        }

        $sql .= ' ORDER BY cb.period_end DESC, u.last_name, u.first_name';

        return $this->db->fetchAll($sql, $params);
    }

    public function getSummary(?int $userId = null, ?string $periodStart = null, ?string $periodEnd = null): array
    {
        $sql = '
            SELECT
                cb.user_id,
                u.first_name,
                u.last_name,
                cb.status,
                COUNT(*) as count,
                SUM(cb.bonus_amount) as total_amount
            FROM calculated_bonuses cb
            LEFT JOIN users u ON cb.user_id = u.id
            WHERE 1=1
        ';

        $params = [];

        if ($userId) {
            $sql .= ' AND cb.user_id = :user_id';
            $params['user_id'] = $userId;
        }

        if ($periodStart) {
            $sql .= ' AND cb.period_start >= :period_start';
            $params['period_start'] = $periodStart;
        }

        if ($periodEnd) {
            $sql .= ' AND cb.period_end <= :period_end';
            $params['period_end'] = $periodEnd;
        }

        $sql .= ' GROUP BY cb.user_id, cb.status';

        return $this->db->fetchAll($sql, $params);
    }
}
