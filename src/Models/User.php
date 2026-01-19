<?php

namespace ITSS\Models;

use ITSS\Core\Database;

class User
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM users WHERE id = :id',
            ['id' => $id]
        );
    }

    public function findByEmail(string $email): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM users WHERE email = :email',
            ['email' => $email]
        );
    }

    public function findByM365Id(string $m365Id): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM users WHERE m365_id = :m365_id',
            ['m365_id' => $m365Id]
        );
    }

    public function createFromAzure(array $userData): int
    {
        return $this->db->insert('users', [
            'email' => $userData['email'],
            'first_name' => $userData['first_name'],
            'last_name' => $userData['last_name'],
            'm365_id' => $userData['m365_id'],
            'role' => 'employee',
            'is_active' => true
        ]);
    }

    public function updateAzureInfo(int $userId, array $userData): void
    {
        $this->db->update('users', [
            'm365_id' => $userData['m365_id'],
            'first_name' => $userData['first_name'],
            'last_name' => $userData['last_name']
        ], 'id = :id', ['id' => $userId]);
    }

    public function getAll(bool $activeOnly = true): array
    {
        $sql = 'SELECT * FROM users';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY last_name, first_name';

        return $this->db->fetchAll($sql);
    }

    public function getByRole(string $role): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM users WHERE role = :role AND is_active = 1 ORDER BY last_name, first_name',
            ['role' => $role]
        );
    }

    public function updateRole(int $userId, string $role): void
    {
        $this->db->update('users', ['role' => $role], 'id = :id', ['id' => $userId]);
    }

    public function setManager(int $userId, ?int $managerId): void
    {
        $this->db->update('users', ['manager_id' => $managerId], 'id = :id', ['id' => $userId]);
    }

    public function setTeamLeader(int $userId, ?int $teamLeaderId): void
    {
        $this->db->update('users', ['team_leader_id' => $teamLeaderId], 'id = :id', ['id' => $userId]);
    }

    public function deactivate(int $userId): void
    {
        $this->db->update('users', ['is_active' => false], 'id = :id', ['id' => $userId]);
    }

    public function activate(int $userId): void
    {
        $this->db->update('users', ['is_active' => true], 'id = :id', ['id' => $userId]);
    }

    public function getTeamMembers(int $teamLeaderId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM users WHERE team_leader_id = :team_leader_id AND is_active = 1',
            ['team_leader_id' => $teamLeaderId]
        );
    }

    public function getManagedUsers(int $managerId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM users WHERE manager_id = :manager_id AND is_active = 1',
            ['manager_id' => $managerId]
        );
    }
}
