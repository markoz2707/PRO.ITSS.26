<?php

namespace ITSS\Core;

class Request
{
    private array $query;
    private array $post;
    private array $files;
    private array $server;
    private array $cookies;
    private ?array $jsonData = null;

    public function __construct()
    {
        $this->query = $_GET;
        $this->post = $_POST;
        $this->files = $_FILES;
        $this->server = $_SERVER;
        $this->cookies = $_COOKIE;

        if ($this->isJson()) {
            $this->jsonData = json_decode(file_get_contents('php://input'), true) ?? [];
        }
    }

    public function method(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    public function uri(): string
    {
        return $this->server['REQUEST_URI'] ?? '/';
    }

    public function isJson(): bool
    {
        return str_contains($this->server['CONTENT_TYPE'] ?? '', 'application/json');
    }

    public function query(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->query;
        }
        return $this->query[$key] ?? $default;
    }

    public function post(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->post;
        }
        return $this->post[$key] ?? $default;
    }

    public function json(string $key = null, $default = null)
    {
        if ($this->jsonData === null) {
            return $default;
        }
        if ($key === null) {
            return $this->jsonData;
        }
        return $this->jsonData[$key] ?? $default;
    }

    public function input(string $key = null, $default = null)
    {
        if ($this->isJson()) {
            return $this->json($key, $default);
        }
        return $this->post($key, $default);
    }

    public function all(): array
    {
        return array_merge($this->query, $this->post, $this->jsonData ?? []);
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) && $this->files[$key]['error'] === UPLOAD_ERR_OK;
    }

    public function cookie(string $key, $default = null)
    {
        return $this->cookies[$key] ?? $default;
    }

    public function header(string $key, $default = null)
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        return $this->server[$key] ?? $default;
    }

    public function bearerToken(): ?string
    {
        $header = $this->header('Authorization');
        if ($header && preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return $matches[1];
        }
        return null;
    }

    public function ip(): string
    {
        return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function userAgent(): string
    {
        return $this->server['HTTP_USER_AGENT'] ?? '';
    }
}
