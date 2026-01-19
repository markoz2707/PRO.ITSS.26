<?php

namespace ITSS\Models;

use ITSS\Core\Database;

class Document
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne('SELECT * FROM documents WHERE id = :id', ['id' => $id]);
    }

    public function getByProject(int $projectId): array
    {
        $sql = '
            SELECT d.*, u.first_name, u.last_name
            FROM documents d
            LEFT JOIN users u ON d.uploaded_by = u.id
            WHERE d.project_id = :project_id
            ORDER BY d.created_at DESC
        ';

        return $this->db->fetchAll($sql, ['project_id' => $projectId]);
    }

    public function getByInvoice(int $invoiceId): array
    {
        $sql = '
            SELECT d.*, u.first_name, u.last_name
            FROM documents d
            LEFT JOIN users u ON d.uploaded_by = u.id
            WHERE d.invoice_id = :invoice_id
            ORDER BY d.created_at DESC
        ';

        return $this->db->fetchAll($sql, ['invoice_id' => $invoiceId]);
    }

    public function getByType(string $type, ?int $projectId = null): array
    {
        $sql = '
            SELECT d.*, u.first_name, u.last_name
            FROM documents d
            LEFT JOIN users u ON d.uploaded_by = u.id
            WHERE d.document_type = :type
        ';

        $params = ['type' => $type];

        if ($projectId) {
            $sql .= ' AND d.project_id = :project_id';
            $params['project_id'] = $projectId;
        }

        $sql .= ' ORDER BY d.created_at DESC';

        return $this->db->fetchAll($sql, $params);
    }

    public function create(array $data, int $userId): int
    {
        return $this->db->insert('documents', [
            'document_name' => $data['document_name'],
            'document_type' => $data['document_type'],
            'file_path' => $data['file_path'],
            'file_size' => $data['file_size'],
            'mime_type' => $data['mime_type'] ?? null,
            'project_id' => $data['project_id'] ?? null,
            'invoice_id' => $data['invoice_id'] ?? null,
            'description' => $data['description'] ?? null,
            'uploaded_by' => $userId
        ]);
    }

    public function update(int $id, array $data): void
    {
        $updateData = [];
        $allowedFields = ['document_name', 'description'];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        if (!empty($updateData)) {
            $this->db->update('documents', $updateData, 'id = :id', ['id' => $id]);
        }
    }

    public function delete(int $id): void
    {
        $document = $this->findById($id);
        if ($document && file_exists($document['file_path'])) {
            unlink($document['file_path']);
        }
        $this->db->delete('documents', 'id = :id', ['id' => $id]);
    }

    public function getStorageUsage(?int $projectId = null): array
    {
        $sql = 'SELECT
                    document_type,
                    COUNT(*) as count,
                    SUM(file_size) as total_size
                FROM documents';

        $params = [];
        if ($projectId) {
            $sql .= ' WHERE project_id = :project_id';
            $params['project_id'] = $projectId;
        }

        $sql .= ' GROUP BY document_type';

        return $this->db->fetchAll($sql, $params);
    }
}
