<?php

namespace ITSS\Models;

use ITSS\Core\Database;

class Invoice
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne('SELECT * FROM invoices WHERE id = :id', ['id' => $id]);
    }

    public function getAll(?string $type = null, ?int $projectId = null): array
    {
        $sql = 'SELECT i.*, p.project_number, p.project_name FROM invoices i
                LEFT JOIN projects p ON i.project_id = p.id
                WHERE 1=1';
        $params = [];

        if ($type) {
            $sql .= ' AND i.invoice_type = :type';
            $params['type'] = $type;
        }

        if ($projectId) {
            $sql .= ' AND i.project_id = :project_id';
            $params['project_id'] = $projectId;
        }

        $sql .= ' ORDER BY i.invoice_date DESC';

        return $this->db->fetchAll($sql, $params);
    }

    public function getByProject(int $projectId, ?string $type = null): array
    {
        $sql = 'SELECT * FROM invoices WHERE project_id = :project_id';
        $params = ['project_id' => $projectId];

        if ($type) {
            $sql .= ' AND invoice_type = :type';
            $params['type'] = $type;
        }

        $sql .= ' ORDER BY invoice_date DESC';

        return $this->db->fetchAll($sql, $params);
    }

    public function create(array $data, int $userId): int
    {
        return $this->db->insert('invoices', [
            'invoice_number' => $data['invoice_number'],
            'invoice_type' => $data['invoice_type'],
            'project_id' => $data['project_id'] ?? null,
            'supplier_name' => $data['supplier_name'] ?? null,
            'client_name' => $data['client_name'] ?? null,
            'contractor' => $data['contractor'] ?? null,
            'invoice_date' => $data['invoice_date'],
            'due_date' => $data['due_date'] ?? null,
            'payment_deadline_date' => $data['payment_deadline_date'] ?? null,
            'payment_date' => $data['payment_date'] ?? null,
            'payment_received_date' => $data['payment_received_date'] ?? null,
            'net_amount' => $data['net_amount'],
            'vat_amount' => $data['vat_amount'],
            'gross_amount' => $data['gross_amount'],
            'currency' => $data['currency'] ?? 'PLN',
            'payment_status' => $data['payment_status'] ?? 'pending',
            'description' => $data['description'] ?? null,
            'business_type' => $data['business_type'] ?? null,
            'segment' => $data['segment'] ?? null,
            'sector' => $data['sector'] ?? null,
            'category' => $data['category'] ?? null,
            'mpk_dh1' => $data['mpk_dh1'] ?? null,
            'mpk_dh2' => $data['mpk_dh2'] ?? null,
            'mpk_gnp' => $data['mpk_gnp'] ?? null,
            'mpk_do' => $data['mpk_do'] ?? null,
            'mpk_og' => $data['mpk_og'] ?? null,
            'mpk_eu1' => $data['mpk_eu1'] ?? null,
            'mpk_eu2' => $data['mpk_eu2'] ?? null,
            'mpk_ono' => $data['mpk_ono'] ?? null,
            'mpk_ksdo' => $data['mpk_ksdo'] ?? null,
            'operator_client' => $data['operator_client'] ?? null,
            'uwagi' => $data['uwagi'] ?? null,
            'baza_licze' => $data['baza_licze'] ?? null,
            'mpt' => $data['mpt'] ?? null,
            'ksef_id' => $data['ksef_id'] ?? null,
            'created_by' => $userId
        ]);
    }

    public function update(int $id, array $data): void
    {
        $updateData = [];
        $allowedFields = [
            'invoice_number', 'project_id', 'supplier_name', 'client_name', 'contractor',
            'invoice_date', 'due_date', 'payment_deadline_date', 'payment_date', 'payment_received_date',
            'net_amount', 'vat_amount', 'gross_amount', 'currency', 'payment_status', 'description',
            'business_type', 'segment', 'sector', 'category',
            'mpk_dh1', 'mpk_dh2', 'mpk_gnp', 'mpk_do', 'mpk_og', 'mpk_eu1', 'mpk_eu2', 'mpk_ono', 'mpk_ksdo',
            'operator_client', 'uwagi', 'baza_licze', 'mpt', 'ksef_id'
        ];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        if (!empty($updateData)) {
            $this->db->update('invoices', $updateData, 'id = :id', ['id' => $id]);
        }
    }

    public function markAsPaid(int $id, string $paymentDate = null): void
    {
        $this->db->update('invoices', [
            'payment_status' => 'paid',
            'payment_date' => $paymentDate ?? date('Y-m-d')
        ], 'id = :id', ['id' => $id]);
    }

    public function delete(int $id): void
    {
        $this->db->delete('invoices', 'id = :id', ['id' => $id]);
    }

    public function getSummary(?int $projectId = null): array
    {
        $sql = '
            SELECT
                invoice_type,
                payment_status,
                currency,
                SUM(net_amount) as total_net,
                SUM(gross_amount) as total_gross,
                COUNT(*) as count
            FROM invoices
        ';

        $params = [];
        if ($projectId) {
            $sql .= ' WHERE project_id = :project_id';
            $params['project_id'] = $projectId;
        }

        $sql .= ' GROUP BY invoice_type, payment_status, currency';

        return $this->db->fetchAll($sql, $params);
    }

    public function getMonthlySummary(int $year): array
    {
        $sql = '
            SELECT
                MONTH(invoice_date) as month,
                invoice_type,
                SUM(net_amount) as total_net
            FROM invoices
            WHERE YEAR(invoice_date) = :year AND payment_status = "paid"
            GROUP BY MONTH(invoice_date), invoice_type
            ORDER BY month ASC
        ';
        
        return $this->db->fetchAll($sql, ['year' => $year]);
    }
}
