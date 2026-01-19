<?php

namespace ITSS\Models;

use ITSS\Core\Database;

class LeaveRequest
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
                lr.*,
                u.first_name as user_first_name,
                u.last_name as user_last_name,
                u.email as user_email,
                tl.first_name as team_leader_first_name,
                tl.last_name as team_leader_last_name,
                m.first_name as manager_first_name,
                m.last_name as manager_last_name
            FROM leave_requests lr
            LEFT JOIN users u ON lr.user_id = u.id
            LEFT JOIN users tl ON lr.team_leader_id = tl.id
            LEFT JOIN users m ON lr.manager_id = m.id
            WHERE lr.id = :id
        ';

        return $this->db->fetchOne($sql, ['id' => $id]);
    }

    public function getByUser(int $userId, ?string $status = null): array
    {
        $sql = 'SELECT * FROM leave_requests WHERE user_id = :user_id';
        $params = ['user_id' => $userId];

        if ($status) {
            $sql .= ' AND status = :status';
            $params['status'] = $status;
        }

        $sql .= ' ORDER BY created_at DESC';

        return $this->db->fetchAll($sql, $params);
    }

    public function getPendingForTeamLeader(int $teamLeaderId): array
    {
        $sql = '
            SELECT lr.*, u.first_name, u.last_name, u.email
            FROM leave_requests lr
            INNER JOIN users u ON lr.user_id = u.id
            WHERE u.team_leader_id = :team_leader_id
            AND lr.status = "pending_team_leader"
            ORDER BY lr.created_at ASC
        ';

        return $this->db->fetchAll($sql, ['team_leader_id' => $teamLeaderId]);
    }

    public function getPendingForManager(int $managerId): array
    {
        $sql = '
            SELECT lr.*, u.first_name, u.last_name, u.email
            FROM leave_requests lr
            INNER JOIN users u ON lr.user_id = u.id
            WHERE u.manager_id = :manager_id
            AND lr.status = "pending_manager"
            ORDER BY lr.created_at ASC
        ';

        return $this->db->fetchAll($sql, ['manager_id' => $managerId]);
    }

    public function getAll(?string $status = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $sql = '
            SELECT lr.*, u.first_name, u.last_name, u.email
            FROM leave_requests lr
            INNER JOIN users u ON lr.user_id = u.id
            WHERE 1=1
        ';

        $params = [];

        if ($status) {
            $sql .= ' AND lr.status = :status';
            $params['status'] = $status;
        }

        if ($startDate) {
            $sql .= ' AND lr.start_date >= :start_date';
            $params['start_date'] = $startDate;
        }

        if ($endDate) {
            $sql .= ' AND lr.end_date <= :end_date';
            $params['end_date'] = $endDate;
        }

        $sql .= ' ORDER BY lr.created_at DESC';

        return $this->db->fetchAll($sql, $params);
    }

    public function create(array $data, int $userId): int
    {
        return $this->db->insert('leave_requests', [
            'user_id' => $userId,
            'leave_type' => $data['leave_type'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'days_count' => $data['days_count'],
            'reason' => $data['reason'] ?? null,
            'status' => 'pending_team_leader'
        ]);
    }

    public function update(int $id, array $data): void
    {
        $updateData = [];
        $allowedFields = ['leave_type', 'start_date', 'end_date', 'days_count', 'reason'];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        if (!empty($updateData)) {
            $this->db->update('leave_requests', $updateData, 'id = :id', ['id' => $id]);
        }
    }

    public function approveByTeamLeader(int $id, int $teamLeaderId, ?string $comment = null): void
    {
        $this->db->update('leave_requests', [
            'status' => 'pending_manager',
            'team_leader_id' => $teamLeaderId,
            'team_leader_approved_at' => date('Y-m-d H:i:s'),
            'team_leader_comment' => $comment
        ], 'id = :id', ['id' => $id]);

        $this->addHistory($id, 'pending_manager', $teamLeaderId, $comment);
    }

    public function approveByManager(int $id, int $managerId, ?string $comment = null): void
    {
        $this->db->update('leave_requests', [
            'status' => 'approved',
            'manager_id' => $managerId,
            'manager_approved_at' => date('Y-m-d H:i:s'),
            'manager_comment' => $comment
        ], 'id = :id', ['id' => $id]);

        $this->addHistory($id, 'approved', $managerId, $comment);
    }

    public function reject(int $id, int $approverId, ?string $comment = null): void
    {
        $this->db->update('leave_requests', [
            'status' => 'rejected'
        ], 'id = :id', ['id' => $id]);

        $this->addHistory($id, 'rejected', $approverId, $comment);
    }

    public function cancel(int $id, int $userId): void
    {
        $this->db->update('leave_requests', [
            'status' => 'cancelled'
        ], 'id = :id', ['id' => $id]);

        $this->addHistory($id, 'cancelled', $userId, 'Cancelled by user');
    }

    private function addHistory(int $leaveRequestId, string $status, int $changedBy, ?string $comment): void
    {
        $this->db->insert('leave_request_history', [
            'leave_request_id' => $leaveRequestId,
            'status' => $status,
            'changed_by' => $changedBy,
            'comment' => $comment
        ]);
    }

    public function getHistory(int $leaveRequestId): array
    {
        $sql = '
            SELECT lrh.*, u.first_name, u.last_name
            FROM leave_request_history lrh
            LEFT JOIN users u ON lrh.changed_by = u.id
            WHERE lrh.leave_request_id = :leave_request_id
            ORDER BY lrh.created_at ASC
        ';

        return $this->db->fetchAll($sql, ['leave_request_id' => $leaveRequestId]);
    }

    public function delete(int $id): void
    {
        $this->db->delete('leave_requests', 'id = :id', ['id' => $id]);
    }

    public function getUserLeaveSummary(int $userId, int $year): array
    {
        $sql = '
            SELECT
                leave_type,
                SUM(days_count) as total_days,
                COUNT(*) as request_count
            FROM leave_requests
            WHERE user_id = :user_id
            AND YEAR(start_date) = :year
            AND status IN ("approved", "pending_team_leader", "pending_manager")
            GROUP BY leave_type
        ';

        return $this->db->fetchAll($sql, ['user_id' => $userId, 'year' => $year]);
    }
}
