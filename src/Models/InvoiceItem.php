<?php

namespace ITSS\Models;

use ITSS\Core\Database;

class InvoiceItem
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne('SELECT * FROM invoice_items WHERE id = :id', ['id' => $id]);
    }

    public function getByInvoice(int $invoiceId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM invoice_items WHERE invoice_id = :invoice_id ORDER BY item_number',
            ['invoice_id' => $invoiceId]
        );
    }

    public function create(array $data): int
    {
        return $this->db->insert('invoice_items', [
            'invoice_id' => $data['invoice_id'],
            'item_number' => $data['item_number'] ?? 1,
            'item_name' => $data['item_name'] ?? null,
            'item_description' => $data['item_description'] ?? null,
            'category' => $data['category'] ?? null,
            'business_type' => $data['business_type'] ?? null,
            'quantity' => $data['quantity'] ?? 1,
            'unit' => $data['unit'] ?? 'szt',
            'unit_net_price' => $data['unit_net_price'] ?? null,
            'net_amount' => $data['net_amount'],
            'vat_rate' => $data['vat_rate'] ?? 23.00,
            'vat_amount' => $data['vat_amount'],
            'gross_amount' => $data['gross_amount'],
            'mpk_dh1' => $data['mpk_dh1'] ?? null,
            'mpk_dh2' => $data['mpk_dh2'] ?? null,
            'mpk_gnp' => $data['mpk_gnp'] ?? null,
            'mpk_do' => $data['mpk_do'] ?? null,
            'mpk_og' => $data['mpk_og'] ?? null,
            'notes' => $data['notes'] ?? null
        ]);
    }

    public function update(int $id, array $data): void
    {
        $updateData = [];
        $allowedFields = [
            'item_number', 'item_name', 'item_description', 'category', 'business_type',
            'quantity', 'unit', 'unit_net_price', 'net_amount', 'vat_rate', 'vat_amount',
            'gross_amount', 'mpk_dh1', 'mpk_dh2', 'mpk_gnp', 'mpk_do', 'mpk_og', 'notes'
        ];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        if (!empty($updateData)) {
            $this->db->update('invoice_items', $updateData, 'id = :id', ['id' => $id]);
        }
    }

    public function delete(int $id): void
    {
        $this->db->delete('invoice_items', 'id = :id', ['id' => $id]);
    }

    public function deleteByInvoice(int $invoiceId): void
    {
        $this->db->delete('invoice_items', 'invoice_id = :invoice_id', ['invoice_id' => $invoiceId]);
    }

    public function getInvoiceTotal(int $invoiceId): array
    {
        $sql = '
            SELECT
                SUM(net_amount) as total_net,
                SUM(vat_amount) as total_vat,
                SUM(gross_amount) as total_gross,
                COUNT(*) as items_count
            FROM invoice_items
            WHERE invoice_id = :invoice_id
        ';

        return $this->db->fetchOne($sql, ['invoice_id' => $invoiceId]) ?? [
            'total_net' => 0,
            'total_vat' => 0,
            'total_gross' => 0,
            'items_count' => 0
        ];
    }

    public function getByBusinessType(string $businessType, ?int $invoiceId = null): array
    {
        $sql = 'SELECT * FROM invoice_items WHERE business_type = :business_type';
        $params = ['business_type' => $businessType];

        if ($invoiceId) {
            $sql .= ' AND invoice_id = :invoice_id';
            $params['invoice_id'] = $invoiceId;
        }

        $sql .= ' ORDER BY created_at DESC';

        return $this->db->fetchAll($sql, $params);
    }

    public function getSummaryByCategory(int $invoiceId): array
    {
        $sql = '
            SELECT
                category,
                business_type,
                SUM(net_amount) as total_net,
                SUM(gross_amount) as total_gross,
                COUNT(*) as items_count
            FROM invoice_items
            WHERE invoice_id = :invoice_id
            GROUP BY category, business_type
        ';

        return $this->db->fetchAll($sql, ['invoice_id' => $invoiceId]);
    }
}
