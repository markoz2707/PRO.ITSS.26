<?php

namespace ITSS\Services;

use ITSS\Core\Logger;
use ITSS\Models\Invoice;
use ITSS\Models\InvoiceItem;
use Exception;
use SimpleXMLElement;

class KsefService
{
    private array $config;
    private string $baseUrl;
    private ?string $sessionToken = null;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $env = $config['environment'] ?? 'demo';
        
        switch ($env) {
            case 'prod':
                $this->baseUrl = 'https://ksef.mf.gov.pl/api/online';
                break;
            case 'test':
                $this->baseUrl = 'https://ksef-test.mf.gov.pl/api/online';
                break;
            case 'demo':
            default:
                $this->baseUrl = 'https://ksef-demo.mf.gov.pl/api/online';
                break;
        }
    }

    /**
     * Wyszukuje faktury w zadanym okresie w KSeF z użyciem API
     */
    public function queryInvoices(string $dateFrom, string $dateTo): array
    {
        if (empty($this->config['token']) || empty($this->config['nip'])) {
            throw new Exception("Brak skonfigurowanego NIP lub Tokena w config.php do komunikacji z KSeF.");
        }

        Logger::info('Querying KSeF invoices API', [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'env' => $this->config['environment'] ?? 'demo'
        ]);

        // Autoryzacja i uzyskanie SessionToken (wymaga modułu kryptograficznego)
        $sessionToken = $this->authenticate();

        $queryData = [
            'queryCriteria' => [
                'subjectType' => 'subject2', // subject2 = Nabywca
                'type' => 'range',
                'invoicingDateFrom' => $dateFrom . 'T00:00:00.000Z',
                'invoicingDateTo' => $dateTo . 'T23:59:59.999Z'
            ]
        ];

        $response = $this->request('POST', '/Query/Invoice/Sync?PageSize=100&PageOffset=0', $queryData, [
            'SessionToken: ' . $sessionToken
        ]);

        return [
            'success' => true,
            'invoices' => $response['invoiceHeaderList'] ?? []
        ];
    }

    /**
     * Proces autoryzacji w KSeF z użyciem szyfrowania RSA
     */
    private function authenticate(): string
    {
        if ($this->sessionToken) {
            return $this->sessionToken;
        }

        $nip = $this->config['nip'];
        $token = $this->config['token'];

        // 1. Authorisation Challenge
        $challengeRes = $this->request('POST', '/Session/AuthorisationChallenge', [
            'contextIdentifier' => [
                'type' => 'onip',
                'identifier' => $nip
            ]
        ]);

        $challenge = $challengeRes['challenge'] ?? null;
        $timestamp = $challengeRes['timestamp'] ?? null;

        if (!$challenge || !$timestamp) {
            throw new Exception("Nie otrzymano danych wyzwania (challenge/timestamp) z KSeF.");
        }

        // 2. Przygotowanie pakietu autoryzacyjnego (Challenge + Token + Timestamp)
        $authData = $token . '|' . $timestamp;
        
        // Pobranie klucza publicznego KSeF (zależnie od środowiska)
        $publicKey = $this->getKsefPublicKey();
        
        $encryptedToken = '';
        if (!openssl_public_encrypt($authData, $encryptedToken, $publicKey, OPENSSL_PKCS1_PADDING)) {
            throw new Exception("Błąd szyfrowania tokenu KSeF: " . openssl_error_string());
        }

        // 3. Init Token Session
        $initRes = $this->request('POST', '/Session/InitToken', [
            'contextIdentifier' => [
                'type' => 'onip',
                'identifier' => $nip
            ],
            'identifier' => $nip,
            'token' => base64_encode($encryptedToken),
            'challenge' => $challenge
        ]);

        $this->sessionToken = $initRes['sessionToken']['token'] ?? null;

        if (!$this->sessionToken) {
            throw new Exception("Nie udało się uzyskać SessionToken po InitToken.");
        }

        return $this->sessionToken;
    }

    /**
     * Pobiera XML faktury z KSeF i importuje go
     */
    public function downloadAndImport(string $ksefReferenceNumber, int $userId): int
    {
        $sessionToken = $this->authenticate();
        
        // Pobranie faktury w formacie XML
        $url = "/Common/Invoice/" . $ksefReferenceNumber;
        
        $ch = curl_init($this->baseUrl . $url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'SessionToken: ' . $sessionToken,
                'Accept: application/octet-stream' // KSeF zwraca binarny XML
            ]
        ]);
        
        $xmlContent = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
             throw new Exception("Błąd pobierania XML faktury ($ksefReferenceNumber) z KSeF API: Kod $httpCode");
        }

        $parsedData = $this->parseXmlContent($xmlContent);
        // Upewnij się, że ksef_id jest ustawiony na numer referencyjny
        $parsedData['ksef_id'] = $ksefReferenceNumber;
        
        return $this->importInvoice($parsedData, $userId, 'cost');
    }

    /**
     * Zwraca klucz publiczny KSeF dla wybranego środowiska
     * W produkcji klucze powinny być w plikach .pem
     */
    private function getKsefPublicKey(): string
    {
        // Klucz publiczny dla środowiska TEST (uproszczony przykład - w realu ładujemy z pliku)
        // Ministerstwo Finansów udostępnia te klucze na swojej stronie
        $env = $this->config['environment'] ?? 'demo';
        
        $keyPath = __DIR__ . "/../../config/ksef_{$env}_pub.pem";
        
        if (file_exists($keyPath)) {
            return file_get_contents($keyPath);
        }

        // Fallback do wbudowanego klucza testowego (jeśli dostępny)
        throw new Exception("Brak pliku klucza publicznego KSeF: $keyPath. Pobierz klucz .pem ze stron MF i umieść w katalogu config.");
    }

    /**
     * Pomocnicza metoda do wykonywania żądań HTTP do API KSeF
     */
    private function request(string $method, string $endpoint, array $data = [], array $headers = []): array
    {
        $url = $this->baseUrl . $endpoint;
        $ch = curl_init($url);
        
        $defaultHeaders = [
            'Accept: application/json',
            'Content-Type: application/json'
        ];
        
        $headers = array_merge($defaultHeaders, $headers);
        
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
        ];
        
        if ($method === 'POST' || $method === 'PUT') {
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }
        
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception("Błąd połączenia z KSeF: " . $error);
        }
        
        curl_close($ch);
        
        $decoded = json_decode($response, true) ?: [];
        
        if ($httpCode >= 400) {
            $message = $decoded['exception']['message'] ?? 'Nieznany błąd KSeF API';
            throw new Exception("Błąd KSeF ($httpCode): $message");
        }
        
        return $decoded;
    }

    /**
     * Parsuje plik XML FA(2) KSeF i przygotowuje tablicę danych do importu
     */
    public function parseXmlFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new Exception("Plik XML nie istnieje: $filePath");
        }

        $xmlContent = file_get_contents($filePath);
        return $this->parseXmlContent($xmlContent);
    }

    /**
     * Parsuje zawartość XML FA(2)
     */
    public function parseXmlContent(string $xmlContent): array
    {
        // Usuń przestrzenie nazw do łatwiejszego parsowania
        $xmlContent = preg_replace('/xmlns[^=]*="[^"]*"/i', '', $xmlContent);
        
        try {
            $xml = new SimpleXMLElement($xmlContent);
        } catch (Exception $e) {
            Logger::error('Błąd parsowania XML KSeF', ['error' => $e->getMessage()]);
            throw new Exception("Nieprawidłowy format pliku XML KSeF");
        }

        if (!isset($xml->Faktura)) {
            throw new Exception("Plik XML nie jest prawidłową fakturą KSeF FA(2)");
        }

        $naglowek = $xml->Naglowek;
        $faktura = $xml->Faktura;
        $podmiotSprzedajacy = $xml->Podmiot1;
        $podmiotKupujacy = $xml->Podmiot2;

        $invoiceData = [
            'ksef_id' => isset($naglowek->NrKSeF) ? (string)$naglowek->NrKSeF : null,
            'invoice_number' => (string)$faktura->P_2A,
            'invoice_date' => (string)$faktura->P_1,
            'due_date' => isset($faktura->TerminyPlatnosci->TerminPlatnosci) ? (string)$faktura->TerminyPlatnosci->TerminPlatnosci->Termin : null,
            'supplier_name' => (string)$podmiotSprzedajacy->DaneIdentyfikacyjne->Nazwa,
            'supplier_nip' => (string)$podmiotSprzedajacy->DaneIdentyfikacyjne->NIP,
            'client_name' => (string)$podmiotKupujacy->DaneIdentyfikacyjne->Nazwa,
            'client_nip' => (string)$podmiotKupujacy->DaneIdentyfikacyjne->NIP,
            'net_amount' => (float)$faktura->P_13_1 + (float)($faktura->P_13_2 ?? 0),
            'vat_amount' => (float)$faktura->P_14_1 + (float)($faktura->P_14_2 ?? 0),
            'gross_amount' => (float)$faktura->P_15,
            'currency' => (string)$faktura->KodWaluty ?? 'PLN',
            'items' => []
        ];

        // Fallback kwotowy, jeśli P_13/P_14 nie są jawne
        if ($invoiceData['net_amount'] == 0 && isset($faktura->WartoscNetto)) {
             $invoiceData['net_amount'] = (float)$faktura->WartoscNetto;
        }

        if (isset($faktura->FaWiersz)) {
            foreach ($faktura->FaWiersz as $wiersz) {
                $invoiceData['items'][] = [
                    'name' => (string)$wiersz->P_7,
                    'quantity' => (float)$wiersz->P_8A,
                    'unit' => (string)$wiersz->P_8B,
                    'unit_price' => (float)$wiersz->P_9A,
                    'net_amount' => (float)$wiersz->P_11,
                    'vat_rate' => (string)$wiersz->P_12,
                    'vat_amount' => isset($wiersz->P_11V) ? (float)$wiersz->P_11V : ((float)$wiersz->P_11 * ((float)str_replace('%', '', (string)$wiersz->P_12)) / 100),
                ];
            }
        }

        return $invoiceData;
    }

    /**
     * Importuje sparsowane dane KSeF do bazy
     */
    public function importInvoice(array $ksefData, int $userId, string $type = 'cost', ?int $projectId = null): int
    {
        $db = \ITSS\Core\Database::getInstance();
        $invoiceModel = new Invoice();
        $itemModel = new InvoiceItem();

        // Przygotowanie danych do modelu Invoice
        $invoiceData = [
            'invoice_number' => $ksefData['invoice_number'],
            'invoice_type' => $type,
            'project_id' => $projectId,
            'supplier_name' => $ksefData['supplier_name'],
            'client_name' => $ksefData['client_name'],
            'contractor' => $type === 'cost' ? $ksefData['supplier_name'] : $ksefData['client_name'],
            'invoice_date' => $ksefData['invoice_date'],
            'due_date' => $ksefData['due_date'],
            'net_amount' => $ksefData['net_amount'],
            'vat_amount' => $ksefData['vat_amount'],
            'gross_amount' => $ksefData['gross_amount'],
            'currency' => $ksefData['currency'],
            'payment_status' => 'pending',
            'ksef_id' => $ksefData['ksef_id'],
            'description' => 'Import z KSeF'
        ];

        try {
            $db->beginTransaction();

            $invoiceId = $invoiceModel->create($invoiceData, $userId);

            // Import pozycji
            foreach ($ksefData['items'] as $index => $item) {
                $gross = $item['net_amount'] + $item['vat_amount'];
                
                // Uproszczone mapowanie stawki VAT
                $vatRate = (float)str_replace('%', '', $item['vat_rate']);
                
                $itemModel->create([
                    'invoice_id' => $invoiceId,
                    'item_name' => $item['name'],
                    'item_description' => 'Pozycja KSeF',
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'] ?: 'szt',
                    'unit_price_net' => $item['unit_price'],
                    'net_amount' => $item['net_amount'],
                    'vat_rate' => $vatRate,
                    'vat_amount' => $item['vat_amount'],
                    'gross_amount' => $gross,
                    'order_index' => $index + 1
                ]);
            }

            $db->commit();
            Logger::info('Zimportowano fakturę z KSeF', ['invoice_id' => $invoiceId, 'ksef_id' => $ksefData['ksef_id']]);
            
            return $invoiceId;

        } catch (Exception $e) {
            $db->rollBack();
            Logger::error('Błąd importu faktury z KSeF', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
