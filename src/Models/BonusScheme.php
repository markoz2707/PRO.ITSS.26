<?php

namespace ITSS\Models;

use ITSS\Core\Database;

class BonusScheme
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        $sql = '
            SELECT bs.*, u.first_name, u.last_name, u.email,
                   p.project_number, p.project_name
            FROM bonus_schemes bs
            LEFT JOIN users u ON bs.user_id = u.id
            LEFT JOIN projects p ON bs.project_id = p.id
            WHERE bs.id = :id
        ';

        return $this->db->fetchOne($sql, ['id' => $id]);
    }

    public function getByUser(int $userId, bool $activeOnly = true): array
    {
        $sql = '
            SELECT bs.*, p.project_number, p.project_name
            FROM bonus_schemes bs
            LEFT JOIN projects p ON bs.project_id = p.id
            WHERE bs.user_id = :user_id
        ';

        $params = ['user_id' => $userId];

        if ($activeOnly) {
            $sql .= ' AND bs.is_active = 1';
            $sql .= ' AND (bs.valid_to IS NULL OR bs.valid_to >= CURDATE())';
        }

        $sql .= ' ORDER BY bs.created_at DESC';

        return $this->db->fetchAll($sql, $params);
    }

    public function getByProject(int $projectId, bool $activeOnly = true): array
    {
        $sql = '
            SELECT bs.*, u.first_name, u.last_name, u.email
            FROM bonus_schemes bs
            LEFT JOIN users u ON bs.user_id = u.id
            WHERE bs.project_id = :project_id
        ';

        $params = ['project_id' => $projectId];

        if ($activeOnly) {
            $sql .= ' AND bs.is_active = 1';
            $sql .= ' AND (bs.valid_to IS NULL OR bs.valid_to >= CURDATE())';
        }

        $sql .= ' ORDER BY bs.created_at DESC';

        return $this->db->fetchAll($sql, $params);
    }

    public function getActive(int $userId, ?int $projectId = null, ?string $date = null): array
    {
        $sql = '
            SELECT bs.*, p.project_number, p.project_name
            FROM bonus_schemes bs
            LEFT JOIN projects p ON bs.project_id = p.id
            WHERE bs.user_id = :user_id
            AND bs.is_active = 1
        ';

        $params = ['user_id' => $userId];
        $checkDate = $date ?? date('Y-m-d');

        $sql .= ' AND bs.valid_from <= :check_date';
        $params['check_date'] = $checkDate;

        $sql .= ' AND (bs.valid_to IS NULL OR bs.valid_to >= :check_date2)';
        $params['check_date2'] = $checkDate;

        if ($projectId !== null) {
            $sql .= ' AND bs.project_id = :project_id';
            $params['project_id'] = $projectId;
        }

        return $this->db->fetchAll($sql, $params);
    }

    public function create(array $data): int
    {
        return $this->db->insert('bonus_schemes', [
            'user_id' => $data['user_id'],
            'project_id' => $data['project_id'] ?? null,
            'bonus_type' => $data['bonus_type'],
            'percentage' => $data['percentage'] ?? null,
            'fixed_amount' => $data['fixed_amount'] ?? null,
            'hourly_rate' => $data['hourly_rate'] ?? null,
            'tickets_pool' => $data['tickets_pool'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'valid_from' => $data['valid_from'],
            'valid_to' => $data['valid_to'] ?? null,
            'description' => $data['description'] ?? null
        ]);
    }

    public function update(int $id, array $data): void
    {
        $updateData = [];
        $allowedFields = ['percentage', 'fixed_amount', 'hourly_rate', 'tickets_pool',
                          'is_active', 'valid_from', 'valid_to', 'description'];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        if (!empty($updateData)) {
            $this->db->update('bonus_schemes', $updateData, 'id = :id', ['id' => $id]);
        }
    }

    public function deactivate(int $id): void
    {
        $this->db->update('bonus_schemes', ['is_active' => false], 'id = :id', ['id' => $id]);
    }

    public function activate(int $id): void
    {
        $this->db->update('bonus_schemes', ['is_active' => true], 'id = :id', ['id' => $id]);
    }

    public function delete(int $id): void
    {
        $this->db->delete('bonus_schemes', 'id = :id', ['id' => $id]);
    }

    public function getAll(?string $bonusType = null): array
    {
        $sql = '
            SELECT bs.*, u.first_name, u.last_name, u.email,
                   p.project_number, p.project_name
            FROM bonus_schemes bs
            LEFT JOIN users u ON bs.user_id = u.id
            LEFT JOIN projects p ON bs.project_id = p.id
            WHERE 1=1
        ';

        $params = [];

        if ($bonusType) {
            $sql .= ' AND bs.bonus_type = :bonus_type';
            $params['bonus_type'] = $bonusType;
        }

        $sql .= ' ORDER BY bs.created_at DESC';

        return $this->db->fetchAll($sql, $params);
    }
}
