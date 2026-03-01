<?php

namespace ITSS\Services;

use ITSS\Core\Logger;
use ITSS\Models\Document;
use Exception;

class EmailImportService
{
    private array $config;
    private $imapStream;
    private string $uploadPath;

    public function __construct(array $config, string $uploadPath)
    {
        $this->config = $config;
        $this->uploadPath = $uploadPath;
    }

    /**
     * Główna metoda synchronizacji e-maili
     */
    public function syncInvoices(): array
    {
        if (!($this->config['enabled'] ?? false)) {
            return ['success' => false, 'error' => 'Moduł importu e-mail jest wyłączony w konfiguracji.'];
        }

        $results = [
            'emails_checked' => 0,
            'attachments_found' => 0,
            'invoices_processed' => 0,
            'errors' => []
        ];

        try {
            $this->connect();
            $emails = imap_search($this->imapStream, 'UNSEEN');

            if ($emails) {
                foreach ($emails as $emailNumber) {
                    $results['emails_checked']++;
                    $this->processEmail($emailNumber, $results);
                }
            }

            $this->disconnect();
        } catch (Exception $e) {
            Logger::error('Błąd synchronizacji e-maili', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }

        return ['success' => true, 'data' => $results];
    }

    private function connect(): void
    {
        $protocol = $this->config['encryption'] === 'ssl' ? '/imap/ssl' : '/imap';
        $flags = $protocol . '/novalidate-cert';
        $mailbox = '{' . $this->config['host'] . ':' . $this->config['port'] . $flags . '}' . $this->config['folder'];

        $this->imapStream = imap_open($mailbox, $this->config['user'], $this->config['password']);

        if (!$this->imapStream) {
            throw new Exception("Nie można połączyć się z serwerem IMAP: " . imap_last_error());
        }
    }

    private function disconnect(): void
    {
        if ($this->imapStream) {
            imap_close($this->imapStream, CL_EXPUNGE);
        }
    }

    /**
     * Procesuje pojedynczą wiadomość e-mail
     */
    private function processEmail(int $emailNumber, array &$results): void
    {
        $structure = imap_fetchstructure($this->imapStream, $emailNumber);
        $attachments = [];

        if (isset($structure->parts) && count($structure->parts)) {
            for ($i = 0; $i < count($structure->parts); $i++) {
                $attachments = array_merge($attachments, $this->findAttachments($emailNumber, $structure->parts[$i], $i + 1));
            }
        }

        foreach ($attachments as $attachment) {
            $results['attachments_found']++;
            $this->handleAttachment($attachment, $results);
        }

        // Oznacz jako przeczytane (imap_search UNSEEN już to robi, ale dla pewności)
        imap_setflag_full($this->imapStream, $emailNumber, "\Seen");
        
        // Opcjonalna archiwizacja
        if ($this->config['auto_archive'] ?? false) {
            $destFolder = $this->config['processed_folder'] ?? 'Processed';
            imap_mail_move($this->imapStream, $emailNumber, $destFolder);
        }
    }

    private function findAttachments(int $emailNumber, $part, int $partNum): array
    {
        $attachments = [];

        if ($part->ifdparameters) {
            foreach ($part->dparameters as $object) {
                if (strtolower($object->attribute) == 'filename') {
                    $attachments[] = $this->extractAttachment($emailNumber, $part, $partNum, $object->value);
                }
            }
        }

        if ($part->ifparameters) {
            foreach ($part->parameters as $object) {
                if (strtolower($object->attribute) == 'name') {
                    $attachments[] = $this->extractAttachment($emailNumber, $part, $partNum, $object->value);
                }
            }
        }

        if (isset($part->parts)) {
            foreach ($part->parts as $key => $subPart) {
                $attachments = array_merge($attachments, $this->findAttachments($emailNumber, $subPart, $partNum . "." . ($key + 1)));
            }
        }

        return $attachments;
    }

    private function extractAttachment(int $emailNumber, $part, string $partNum, string $filename): array
    {
        $content = imap_fetchbody($this->imapStream, $emailNumber, $partNum);
        
        if ($part->encoding == 3) { $content = base64_decode($content); }
        elseif ($part->encoding == 4) { $content = quoted_printable_decode($content); }

        return [
            'filename' => $filename,
            'content' => $content,
            'extension' => strtolower(pathinfo($filename, PATHINFO_EXTENSION))
        ];
    }

    /**
     * Decyduje co zrobić z załącznikiem (KSeF XML vs PDF)
     */
    private function handleAttachment(array $attachment, array &$results): void
    {
        $allowed = $this->config['allowed_extensions'] ?? ['pdf', 'xml'];
        if (!in_array($attachment['extension'], $allowed)) {
            return;
        }

        $filename = uniqid() . '_' . $attachment['filename'];
        $filepath = $this->uploadPath . '/' . $filename;

        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }

        file_put_contents($filepath, $attachment['content']);

        try {
            if ($attachment['extension'] === 'xml') {
                $this->processKsefXml($filepath, $attachment['filename'], $results);
            } else {
                $this->saveAsDocument($filepath, $attachment['filename'], $results);
            }
            $results['invoices_processed']++;
        } catch (Exception $e) {
            $results['errors'][] = "Błąd procesowania {$attachment['filename']}: {$e->getMessage()}";
            Logger::error("Błąd procesowania załącznika e-mail", ['file' => $attachment['filename'], 'error' => $e->getMessage()]);
        }
    }

    /**
     * Automatyczny import jeśli to XML z KSeF
     */
    private function processKsefXml(string $filepath, string $originalName, array &$results): void
    {
        global $config; // Użycie globalnej konfiguracji KSeF
        $ksefService = new KsefService($config['ksef'] ?? []);
        
        $xmlContent = file_get_contents($filepath);
        if (strpos($xmlContent, '<Faktura') !== false) {
             $parsedData = $ksefService->parseXmlContent($xmlContent);
             $userId = \ITSS\Core\Session::get('user_id') ?: 1; // Systemowy użytkownik lub aktualnie zalogowany
             $ksefService->importInvoice($parsedData, $userId, 'cost');
             Logger::info("Automatycznie zimportowano fakturę KSeF z e-mail", ['invoice' => $parsedData['invoice_number']]);
        } else {
            $this->saveAsDocument($filepath, $originalName, $results);
        }
    }

    /**
     * Zapisuje jako ogólny dokument (np. PDF) do ręcznej weryfikacji
     */
    private function saveAsDocument(string $filepath, string $originalName, array &$results): void
    {
        $docModel = new Document();
        $userId = \ITSS\Core\Session::get('user_id') ?: 1;

        $docModel->create([
            'document_name' => $originalName,
            'document_type' => 'other',
            'file_path' => $filepath,
            'file_size' => filesize($filepath),
            'description' => 'Automatyczny import z e-mail'
        ], $userId);
    }
}
