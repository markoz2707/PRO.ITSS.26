<?php

namespace ITSS\Services;

class ExportService
{
    /**
     * Eksportuje tablicę danych do formatu CSV
     */
    public function exportToCSV(array $data, string $filename, array $headers = []): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');
        
        // BOM dla Excela (UTF-8)
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        if (!empty($headers)) {
            fputcsv($output, $headers, ';');
        }

        foreach ($data as $row) {
            fputcsv($output, $row, ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Przygotowuje dane faktur do eksportu
     */
    public function prepareInvoicesData(array $invoices): array
    {
        $exportData = [];
        foreach ($invoices as $inv) {
            $exportData[] = [
                $inv['invoice_number'],
                $inv['invoice_type'] === 'revenue' ? 'Przychód' : 'Koszt',
                $inv['contractor'] ?: ($inv['supplier_name'] ?: $inv['client_name']),
                $inv['project_number'] ?? '-',
                $inv['invoice_date'],
                $inv['net_amount'],
                $inv['vat_amount'],
                $inv['gross_amount'],
                $inv['currency'],
                $inv['payment_status'],
                $inv['business_type'],
                $inv['category']
            ];
        }
        return $exportData;
    }
}
