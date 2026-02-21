<?php

namespace ITSS\Models;

use ITSS\Core\Database;

class ServiceDeskProject
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            'SELECT sp.*, p.project_number, p.project_name as local_project_name
             FROM servicedesk_projects sp
             LEFT JOIN projects p ON sp.project_id = p.id
             WHERE sp.id = :id',
            ['id' => $id]
        );
    }

    public function findBySdProjectId(string $sdProjectId): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM servicedesk_projects WHERE sd_project_id = :sd_project_id',
            ['sd_project_id' => $sdProjectId]
        );
    }

    public function getAll(?string $status = null): array
    {
        $sql = 'SELECT sp.*, p.project_number, p.project_name as local_project_name
                FROM servicedesk_projects sp
                LEFT JOIN projects p ON sp.project_id = p.id';

        $params = [];
        if ($status) {
            $sql .= ' WHERE sp.status = :status';
            $params['status'] = $status;
        }

        $sql .= ' ORDER BY sp.updated_at DESC';

        return $this->db->fetchAll($sql, $params);
    }

    public function getUnlinked(): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM servicedesk_projects WHERE project_id IS NULL ORDER BY project_name ASC'
        );
    }

    public function getLinked(): array
    {
        return $this->db->fetchAll(
            'SELECT sp.*, p.project_number, p.project_name as local_project_name
             FROM servicedesk_projects sp
             INNER JOIN projects p ON sp.project_id = p.id
             ORDER BY sp.project_name ASC'
        );
    }

    public function upsertFromSD(string $sdProjectId, array $data): int
    {
        $existing = $this->findBySdProjectId($sdProjectId);

        $projectData = [
            'sd_project_id' => $sdProjectId,
            'project_name' => $data['project_name'],
            'project_code' => $data['project_code'] ?? null,
            'owner_name' => $data['owner_name'] ?? null,
            'owner_email' => $data['owner_email'] ?? null,
            'status' => $data['status'] ?? null,
            'priority' => $data['priority'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'actual_start_date' => $data['actual_start_date'] ?? null,
            'actual_end_date' => $data['actual_end_date'] ?? null,
            'scheduled_hours' => $data['scheduled_hours'] ?? null,
            'actual_hours' => $data['actual_hours'] ?? null,
            'description' => $data['description'] ?? null,
            'percentage_completion' => $data['percentage_completion'] ?? null,
            'sd_raw_data' => isset($data['raw_data']) ? json_encode($data['raw_data']) : null,
            'last_sync_at' => date('Y-m-d H:i:s')
        ];

        if ($existing) {
            $this->db->update('servicedesk_projects', $projectData,
                'id = :id', ['id' => $existing['id']]);
            return $existing['id'];
        }

        return $this->db->insert('servicedesk_projects', $projectData);
    }

    public function linkToProject(int $id, int $projectId): void
    {
        $this->db->update('servicedesk_projects',
            ['project_id' => $projectId],
            'id = :id', ['id' => $id]);
    }

    public function unlinkFromProject(int $id): void
    {
        $this->db->update('servicedesk_projects',
            ['project_id' => null],
            'id = :id', ['id' => $id]);
    }

    public function searchByName(string $query): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM servicedesk_projects
             WHERE project_name LIKE :query OR project_code LIKE :query2
             ORDER BY project_name ASC',
            ['query' => "%{$query}%", 'query2' => "%{$query}%"]
        );
    }

    public function getCount(): int
    {
        $result = $this->db->fetchOne('SELECT COUNT(*) as cnt FROM servicedesk_projects');
        return (int)($result['cnt'] ?? 0);
    }

    public function getUnlinkedCount(): int
    {
        $result = $this->db->fetchOne(
            'SELECT COUNT(*) as cnt FROM servicedesk_projects WHERE project_id IS NULL'
        );
        return (int)($result['cnt'] ?? 0);
    }
}
