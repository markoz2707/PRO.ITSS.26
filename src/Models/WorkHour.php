<?php

namespace ITSS\Models;

use ITSS\Core\Database;

class WorkHour
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne('SELECT * FROM work_hours WHERE id = :id', ['id' => $id]);
    }

    public function getByProject(int $projectId, ?string $workType = null): array
    {
        $sql = '
            SELECT wh.*, u.first_name, u.last_name, u.email
            FROM work_hours wh
            LEFT JOIN users u ON wh.user_id = u.id
            WHERE wh.project_id = :project_id
        ';

        $params = ['project_id' => $projectId];

        if ($workType) {
            $sql .= ' AND wh.work_type = :work_type';
            $params['work_type'] = $workType;
        }

        $sql .= ' ORDER BY wh.work_date DESC';

        return $this->db->fetchAll($sql, $params);
    }

    public function getByUser(int $userId, ?int $projectId = null): array
    {
        $sql = '
            SELECT wh.*, p.project_number, p.project_name
            FROM work_hours wh
            LEFT JOIN projects p ON wh.project_id = p.id
            WHERE wh.user_id = :user_id
        ';

        $params = ['user_id' => $userId];

        if ($projectId) {
            $sql .= ' AND wh.project_id = :project_id';
            $params['project_id'] = $projectId;
        }

        $sql .= ' ORDER BY wh.work_date DESC';

        return $this->db->fetchAll($sql, $params);
    }

    public function create(array $data): int
    {
        return $this->db->insert('work_hours', [
            'project_id' => $data['project_id'],
            'user_id' => $data['user_id'],
            'work_type' => $data['work_type'],
            'hours' => $data['hours'],
            'work_date' => $data['work_date'],
            'description' => $data['description'] ?? null,
            'servicedesk_ticket_id' => $data['servicedesk_ticket_id'] ?? null
        ]);
    }

    public function updateFromServiceDesk(array $data): void
    {
        $existing = $this->db->fetchOne(
            'SELECT id FROM work_hours WHERE servicedesk_ticket_id = :ticket_id',
            ['ticket_id' => $data['servicedesk_ticket_id']]
        );

        if ($existing) {
            $this->update($existing['id'], $data);
        } else {
            $this->create(array_merge($data, ['last_sync_at' => date('Y-m-d H:i:s')]));
        }
    }

    public function update(int $id, array $data): void
    {
        $updateData = [];
        $allowedFields = ['hours', 'work_date', 'description', 'work_type', 'last_sync_at'];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        if (!empty($updateData)) {
            $this->db->update('work_hours', $updateData, 'id = :id', ['id' => $id]);
        }
    }

    public function delete(int $id): void
    {
        $this->db->delete('work_hours', 'id = :id', ['id' => $id]);
    }

    public function getSummaryByProject(int $projectId): array
    {
        $sql = '
            SELECT
                wh.work_type,
                wh.user_id,
                u.first_name,
                u.last_name,
                SUM(wh.hours) as total_hours
            FROM work_hours wh
            LEFT JOIN users u ON wh.user_id = u.id
            WHERE wh.project_id = :project_id
            GROUP BY wh.work_type, wh.user_id
            ORDER BY wh.work_type, total_hours DESC
        ';

        return $this->db->fetchAll($sql, ['project_id' => $projectId]);
    }

    public function getSummaryByUser(int $userId, ?string $startDate = null, ?string $endDate = null): array
    {
        $sql = '
            SELECT
                p.project_number,
                p.project_name,
                wh.work_type,
                SUM(wh.hours) as total_hours
            FROM work_hours wh
            LEFT JOIN projects p ON wh.project_id = p.id
            WHERE wh.user_id = :user_id
        ';

        $params = ['user_id' => $userId];

        if ($startDate) {
            $sql .= ' AND wh.work_date >= :start_date';
            $params['start_date'] = $startDate;
        }

        if ($endDate) {
            $sql .= ' AND wh.work_date <= :end_date';
            $params['end_date'] = $endDate;
        }

        $sql .= ' GROUP BY p.id, wh.work_type ORDER BY total_hours DESC';

        return $this->db->fetchAll($sql, $params);
    }
}
