<?php

namespace ITSS\Core;

class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private string $body = '';

    public function status(int $code): self
    {
        $this->statusCode = $code;
        http_response_code($code);
        return $this;
    }

    public function header(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        header("$key: $value");
        return $this;
    }

    public function json(array $data, int $status = null): void
    {
        if ($status !== null) {
            $this->status($status);
        }

        $this->header('Content-Type', 'application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    public function html(string $content, int $status = null): void
    {
        if ($status !== null) {
            $this->status($status);
        }

        $this->header('Content-Type', 'text/html; charset=UTF-8');
        echo $content;
        exit;
    }

    public function text(string $content, int $status = null): void
    {
        if ($status !== null) {
            $this->status($status);
        }

        $this->header('Content-Type', 'text/plain; charset=UTF-8');
        echo $content;
        exit;
    }

    public function redirect(string $url, int $status = 302): void
    {
        $this->status($status);
        $this->header('Location', $url);
        exit;
    }

    public function download(string $filePath, string $filename = null): void
    {
        if (!file_exists($filePath)) {
            $this->status(404)->json(['error' => 'File not found']);
            return;
        }

        $filename = $filename ?? basename($filePath);
        $this->header('Content-Type', 'application/octet-stream');
        $this->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $this->header('Content-Length', (string)filesize($filePath));

        readfile($filePath);
        exit;
    }
}
