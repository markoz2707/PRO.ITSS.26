<?php

namespace ITSS\Services;

use ITSS\Core\Database;
use ITSS\Core\Logger;
use ITSS\Models\Invoice;
use ITSS\Models\InvoiceItem;
use ITSS\Models\Project;
use ITSS\Models\ProjectCost;
use ITSS\Models\ProjectRevenue;

class InvoiceImportService
{
    private Database $db;
    private Invoice $invoiceModel;
    private InvoiceItem $invoiceItemModel;
    private Project $projectModel;
    private ProjectCost $projectCostModel;
    private ProjectRevenue $projectRevenueModel;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->invoiceModel = new Invoice();
        $this->invoiceItemModel = new InvoiceItem();
        $this->projectModel = new Project();
        $this->projectCostModel = new ProjectCost();
        $this->projectRevenueModel = new ProjectRevenue();
    }

    /**
     * Import faktur z pliku CSV
     *
     * @param string $filePath Ścieżka do pliku CSV
     * @param int $userId ID użytkownika importującego
     * @param string $invoiceType 'cost' lub 'revenue'
     * @param string $delimiter Separator CSV (domyślnie ';')
     * @return array Wyniki importu
     */
    public function importFromCSV(string $filePath, int $userId, string $invoiceType = 'revenue', string $delimiter = ';'): array
    {
        $imported = 0;
        $errors = [];
        $skipped = 0;

        Logger::info("Starting CSV import", [
            'file' => $filePath,
            'user_id' => $userId,
            'invoice_type' => $invoiceType
        ]);

        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'error' => 'File not found',
                'imported' => 0,
                'skipped' => 0,
                'errors' => []
            ];
        }

        try {
            if (($handle = fopen($filePath, 'r')) !== false) {
                // Odczytaj nagłówek
                $header = fgetcsv($handle, 0, $delimiter);

                if (!$header) {
                    fclose($handle);
                    return [
                        'success' => false,
                        'error' => 'Invalid CSV format - no header',
                        'imported' => 0,
                        'skipped' => 0,
                        'errors' => []
                    ];
                }

                // Normalizuj nagłówki (usuń BOM, trim)
                $header = array_map(function($h) {
                    return trim(str_replace("\xEF\xBB\xBF", '', $h));
                }, $header);

                $rowNumber = 1;

                while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                    $rowNumber++;

                    try {
                        // Pomiń puste wiersze
                        if (empty(array_filter($row))) {
                            continue;
                        }

                        // Połącz nagłówki z danymi
                        $data = array_combine($header, $row);

                        if (!$data) {
                            $errors[] = "Row $rowNumber: Invalid data format";
                            $skipped++;
                            continue;
                        }

                        // Importuj fakturę
                        $this->importSingleInvoice($data, $userId, $invoiceType);
                        $imported++;

                    } catch (\Exception $e) {
                        $errors[] = "Row $rowNumber: " . $e->getMessage();
                        $skipped++;
                        Logger::error("CSV import row error", [
                            'row' => $rowNumber,
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                fclose($handle);
            }

            Logger::info("CSV import completed", [
                'imported' => $imported,
                'skipped' => $skipped,
                'errors_count' => count($errors)
            ]);

            return [
                'success' => true,
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => $errors
            ];

        } catch (\Exception $e) {
            Logger::error("CSV import failed", ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => $errors
            ];
        }
    }

    /**
     * Importuj pojedynczą fakturę
     */
    private function importSingleInvoice(array $data, int $userId, string $invoiceType): void
    {
        // Mapowanie kolumn z CSV
        $invoiceNumber = $this->getValue($data, ['NUMER', 'Nazwa', 'invoice_number']);
        $contractorName = $this->getValue($data, ['KONTRAHENT', 'contractor', 'supplier_name', 'client_name']);
        $invoiceDate = $this->parseDate($this->getValue($data, ['DATA WYSTAWIENIA', 'DATA SPRZEDAŻY', 'invoice_date', 'DATA_WYSTAWIENIA']));
        $paymentDeadline = $this->parseDate($this->getValue($data, ['DATA PŁATNOŚCI', 'payment_deadline', 'DATA_PLATNOSCI']));
        $paymentDate = $this->parseDate($this->getValue($data, ['DATA ZAPŁATY', 'payment_date', 'DATA_ZAPLATY']));

        $netAmount = $this->parseAmount($this->getValue($data, ['NETTO', 'net_amount']));
        $grossAmount = $this->parseAmount($this->getValue($data, ['BRUTTO', 'gross_amount']));
        $vatAmount = $grossAmount - $netAmount;

        $category = $this->getValue($data, ['KATEGORIA', 'category']);
        $description = $this->getValue($data, ['OPIS DOKUMENTU', 'OPIS DOKUMENTU/POZYCJI/TOWARU', 'description']);
        $businessType = $this->getValue($data, ['Business Type', 'business_type']);
        $segment = $this->getValue($data, ['Segment', 'segment']);
        $sector = $this->getValue($data, ['Sector', 'sector']);

        // MPK kody
        $mpkDh1 = $this->getValue($data, ['MPK-DH1', 'mpk_dh1']);
        $mpkDh2 = $this->getValue($data, ['MPK-DH2', 'mpk_dh2']);
        $mpkGnp = $this->getValue($data, ['MPK-GNP', 'mpk_gnp']);
        $mpkDo = $this->getValue($data, ['MPK-DO', 'mpk_do']);
        $mpkOg = $this->getValue($data, ['MPK-OG', 'mpk_og']);
        $mpkEu1 = $this->getValue($data, ['MPK-EU1', 'mpk_eu1']);
        $mpkEu2 = $this->getValue($data, ['MPK-EU2', 'mpk_eu2']);
        $mpkOno = $this->getValue($data, ['MPK-ONO', 'mpk_ono']);
        $mpkKsdo = $this->getValue($data, ['MPK-KSDO', 'mpk_ksdo']);

        $operatorClient = $this->getValue($data, ['OPER.KU/KLIENTA', 'OPIEKUN HANDLOWY', 'operator_client']);
        $uwagi = $this->getValue($data, ['UWAGI', 'uwagi', 'notes']);
        $bazaLicze = $this->getValue($data, ['Baza Licze', 'baza_licze']);
        $mpt = $this->getValue($data, ['MPT', 'mpt']);

        // Sprawdź czy faktura już istnieje
        $existingInvoice = $this->db->fetchOne(
            'SELECT id FROM invoices WHERE invoice_number = :invoice_number',
            ['invoice_number' => $invoiceNumber]
        );

        if ($existingInvoice) {
            Logger::info("Invoice already exists, skipping", ['invoice_number' => $invoiceNumber]);
            throw new \Exception("Invoice $invoiceNumber already exists");
        }

        // Znajdź projekt (jeśli podany)
        $projectId = null;
        $projectNumber = $this->getValue($data, ['PROJECT', 'project_number', 'PROJEKT']);
        if ($projectNumber) {
            $project = $this->projectModel->findByProjectNumber($projectNumber);
            if ($project) {
                $projectId = $project['id'];
            }
        }

        // Określ status płatności
        $paymentStatus = 'pending';
        if ($paymentDate) {
            $paymentStatus = 'paid';
        } elseif ($paymentDeadline && strtotime($paymentDeadline) < time()) {
            $paymentStatus = 'overdue';
        }

        // Utwórz fakturę
        $invoiceData = [
            'invoice_number' => $invoiceNumber,
            'invoice_type' => $invoiceType,
            'project_id' => $projectId,
            'contractor' => $contractorName,
            'supplier_name' => $invoiceType === 'cost' ? $contractorName : null,
            'client_name' => $invoiceType === 'revenue' ? $contractorName : null,
            'invoice_date' => $invoiceDate ?: date('Y-m-d'),
            'due_date' => $paymentDeadline,
            'payment_deadline_date' => $paymentDeadline,
            'payment_date' => $paymentDate,
            'payment_received_date' => $paymentDate,
            'net_amount' => $netAmount,
            'vat_amount' => $vatAmount,
            'gross_amount' => $grossAmount,
            'currency' => 'PLN',
            'payment_status' => $paymentStatus,
            'description' => $description,
            'category' => $category,
            'business_type' => $businessType,
            'segment' => $segment,
            'sector' => $sector,
            'mpk_dh1' => $mpkDh1,
            'mpk_dh2' => $mpkDh2,
            'mpk_gnp' => $mpkGnp,
            'mpk_do' => $mpkDo,
            'mpk_og' => $mpkOg,
            'mpk_eu1' => $mpkEu1,
            'mpk_eu2' => $mpkEu2,
            'mpk_ono' => $mpkOno,
            'mpk_ksdo' => $mpkKsdo,
            'operator_client' => $operatorClient,
            'uwagi' => $uwagi,
            'baza_licze' => $bazaLicze,
            'mpt' => $mpt
        ];

        $this->db->beginTransaction();

        try {
            $invoiceId = $this->invoiceModel->create($invoiceData, $userId);

            // Utwórz pozycję faktury
            if ($invoiceId) {
                $this->invoiceItemModel->create([
                    'invoice_id' => $invoiceId,
                    'item_number' => 1,
                    'item_name' => $category,
                    'item_description' => $description,
                    'category' => $category,
                    'business_type' => $businessType,
                    'quantity' => 1,
                    'unit' => 'szt',
                    'net_amount' => $netAmount,
                    'vat_amount' => $vatAmount,
                    'gross_amount' => $grossAmount,
                    'mpk_dh1' => $mpkDh1,
                    'mpk_dh2' => $mpkDh2,
                    'mpk_gnp' => $mpkGnp,
                    'mpk_do' => $mpkDo,
                    'mpk_og' => $mpkOg
                ]);

                // Jeśli jest przypisany projekt, utwórz również wpis w project_costs/revenues
                if ($projectId) {
                    if ($invoiceType === 'cost') {
                        $this->projectCostModel->create([
                            'project_id' => $projectId,
                            'cost_type' => 'invoice',
                            'cost_category' => $category,
                            'cost_name' => $description ?: $category,
                            'cost_description' => $description,
                            'invoice_id' => $invoiceId,
                            'net_amount' => $netAmount,
                            'vat_amount' => $vatAmount,
                            'gross_amount' => $grossAmount,
                            'currency' => 'PLN',
                            'cost_date' => $invoiceDate ?: date('Y-m-d'),
                            'contractor' => $contractorName,
                            'mpk_code' => $mpkDh1 ?: $mpkDh2
                        ], $userId);
                    } else {
                        $this->projectRevenueModel->create([
                            'project_id' => $projectId,
                            'revenue_type' => 'invoice',
                            'revenue_category' => $category,
                            'revenue_name' => $description ?: $category,
                            'revenue_description' => $description,
                            'invoice_id' => $invoiceId,
                            'net_amount' => $netAmount,
                            'vat_amount' => $vatAmount,
                            'gross_amount' => $grossAmount,
                            'currency' => 'PLN',
                            'revenue_date' => $invoiceDate ?: date('Y-m-d'),
                            'client_name' => $contractorName,
                            'business_type' => $businessType,
                            'segment' => $segment,
                            'sector' => $sector,
                            'mpk_code' => $mpkDh1 ?: $mpkDh2
                        ], $userId);
                    }
                }
            }

            $this->db->commit();

            Logger::info("Invoice imported successfully", [
                'invoice_id' => $invoiceId,
                'invoice_number' => $invoiceNumber
            ]);

        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Pobierz wartość z różnych możliwych kluczy
     */
    private function getValue(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (isset($data[$key]) && $data[$key] !== '' && $data[$key] !== '########') {
                return trim($data[$key]);
            }
        }
        return null;
    }

    /**
     * Parsuj kwotę (usuń spacje, zamień przecinek na kropkę)
     */
    private function parseAmount(?string $amount): float
    {
        if ($amount === null || $amount === '' || $amount === '########') {
            return 0.0;
        }

        $amount = str_replace([' ', ','], ['', '.'], $amount);
        return floatval($amount);
    }

    /**
     * Parsuj datę z różnych formatów
     */
    private function parseDate(?string $date): ?string
    {
        if ($date === null || $date === '' || $date === '########') {
            return null;
        }

        // Spróbuj różne formaty
        $formats = ['d.m.Y', 'Y-m-d', 'd/m/Y', 'd-m-Y'];

        foreach ($formats as $format) {
            $parsed = \DateTime::createFromFormat($format, trim($date));
            if ($parsed !== false) {
                return $parsed->format('Y-m-d');
            }
        }

        // Fallback - spróbuj strtotime
        $timestamp = strtotime($date);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        return null;
    }
}
